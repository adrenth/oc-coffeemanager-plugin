<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Classes;

use Adrenth\CoffeeManager\Classes\Exceptions\OngoingRound;
use Adrenth\CoffeeManager\Models;
use Carbon\Carbon;
use Illuminate\Contracts\Logging\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Pusher\Pusher;
use Pusher\PusherException;

/**
 * Class CoffeeManager
 *
 * @package Adrenth\CoffeeManager\Classes
 */
class RoundHelper
{
    /**
     * @var Pusher
     */
    private $pusher;

    /**
     * @var Log
     */
    private $log;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->pusher = resolve(Pusher::class);
        $this->log = resolve(Log::class);
    }

    /**
     * Initiate a new coffee round.
     *
     * @param int $participantId
     * @param int $expireInMinutes
     * @param int $beverageId
     * @throws ModelNotFoundException
     * @throws OngoingRound
     */
    public function initiate(int $participantId, int $expireInMinutes, int $beverageId): void
    {
        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        if ($participant->group->round !== null) {
            throw new OngoingRound('There\'s currently a round ongoing!');
        }

        $round = Models\Round::create([
            'group_id' => $participant->group->getKey(),
            'initiating_participant_id' => $participant->getKey(),
            'expires_at' => Carbon::parse(date('Y-m-d H:i:0'))
                ->addMinutes(abs($expireInMinutes))
                ->toDateTimeString(),
        ]);

        $participant->group->update([
            'current_round_id' => $round->getKey(),
        ]);

        try {
            $this->triggerPusherEvent(
                'participant-initiates-new-round',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }

        if ($beverageId > 0) {
            $this->join($round->getKey(), $participant->getKey(), $beverageId);
        }
    }

    /**
     * Join a coffee round.
     *
     * @param int $roundId
     * @param int $participantId
     * @param int $beverageId
     * @throws ModelNotFoundException
     */
    public function join(int $roundId, int $participantId, int $beverageId): void
    {
        /** @var Models\Round $round */
        $round = Models\Round::query()->findOrFail($roundId);

        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        if ($round->participants->contains($participant)) {
            return;
        }

        $round->participants()->add(
            $participant,
            null,
            [
                'beverage_id' => $beverageId,
            ]
        );

        $participant->update([
            'last_beverage_id' => $beverageId
        ]);

        try {
            $this->triggerPusherEvent(
                'participant-joined-round',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }
    }

    /**
     * Serve a round immediately.
     *
     * @param int $roundId
     * @param int $participantId
     * @throws ModelNotFoundException
     */
    public function serve(int $roundId, int $participantId): void
    {
        /** @var Models\Round $round */
        $round = Models\Round::query()
            ->where('id', '=', $roundId)
            ->whereNull('designated_participant_id')
            ->firstOrFail();

        if ($round->getAttribute('designated_participant_id') !== null) {
            return;
        }

        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        $round->update([
            'designated_participant_id' => $participant->getKey(),
        ]);

        try {
            $this->triggerPusherEvent(
                'participant-chosen',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }
    }

    /**
     * @param int $roundId
     * @param int $participantId
     * @throws ModelNotFoundException
     * @return void
     */
    public function leave(int $roundId, int $participantId): void
    {
        /** @var Models\Round $round */
        $round = Models\Round::query()->findOrFail($roundId);

        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        $round->participants()->remove($participant);

        try {
            $this->triggerPusherEvent(
                'participant-left-round',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }
    }

    /**
     * @param int $roundId
     * @param int $participantId
     * @throws ModelNotFoundException
     * @return void
     */
    public function cancel(int $roundId, int $participantId): void
    {
        /** @var Models\Round $round */
        $round = Models\Round::query()->findOrFail($roundId);

        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        if ($round->initiatingParticipant->getKey() !== $participant->getKey()) {
            return;
        }

        try {
            $round->delete();
        } catch (\Exception $e) {
            $this->log->error('Cannot delete round: ' . $e->getMessage());
        }

        $participant->group->update(['current_round_id' => null]);

        try {
            $this->triggerPusherEvent(
                'round-cancelled',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }
    }

    /**
     * @param int $roundId
     * @param int $participantId
     * @throws ModelNotFoundException
     */
    public function finish(int $roundId, int $participantId): void
    {
        /** @var Models\Round $round */
        $round = Models\Round::query()
            ->where('id', $roundId)
            ->where('is_finished', '=', false)
            ->firstOrFail();

        /** @var Models\Participant $participant */
        $participant = Models\Participant::findOrFail($participantId);

        if ($round->designatedParticipant->getKey() !== $participant->getKey()) {
            return;
        }

        $round->update([
            'is_finished' => true,
        ]);

        $participant->group->update(['current_round_id' => null]);

        $participant->update([
            'score' => $participant->getAttribute('score') + 1,
        ]);

        /** @var Models\Participant $roundParticipant */
        foreach ($round->participants as $roundParticipant) {
            $roundParticipant->update([
                'last_beverage_id' => $roundParticipant->pivot->beverage_id
            ]);
        }

        try {
            $this->triggerPusherEvent(
                'round-finished',
                $participant,
                $round
            );
        } catch (PusherException $e) {
            $this->log->error($e);
        }
    }

    /**
     * @param string $eventName
     * @param Models\Participant $participant
     * @param Models\Round $round
     * @throws PusherException
     */
    private function triggerPusherEvent(
        string $eventName,
        Models\Participant $participant,
        Models\Round $round
    ): void {
        $data = [
            'participant' => $participant->getAttribute('name'),
            'participant_id' => $participant->getKey(),
            'participants' => $round->participants->pluck('id'),
            'round_id' => $round->getKey(),
        ];

        $this->pusher->trigger(
            'group-' . $participant->group->getKey(),
            $eventName,
            $data
        );
    }
}
