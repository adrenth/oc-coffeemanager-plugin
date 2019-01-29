<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Console;

use Adrenth\CoffeeManager\Models;
use Carbon\Carbon;
use Illuminate\Console\Command;
use InvalidArgumentException;
use Pusher\Pusher;
use Pusher\PusherException;

/**
 * Class ServeRounds
 *
 * @package Adrenth\CoffeeManager\Console
 */
class ServeRounds extends Command
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->signature = 'adrenth:coffeemanager:serve-rounds';
        $this->description = 'Serves Coffee Rounds';

        parent::__construct();
    }

    /**
     * @param Pusher $pusher
     * @throws PusherException
     * @throws InvalidArgumentException
     */
    public function handle(Pusher $pusher): void
    {
        $rounds = Models\Round::query()
            ->where('expires_at', '<=', Carbon::now())
            ->whereNull('designated_participant_id')
            ->where('is_finished', '=', false)
            ->get();

        /** @var Models\Round $round */
        foreach ($rounds as $round) {
            /*
             * Round has no participants. Finish it and continue to next round.
             */
            if ($round->participants->count() < 2) {
                $round->update([
                    'is_finished' => true,
                ]);

                $pusher->trigger(
                    'group-' . $round->group->getKey(),
                    'round-expired',
                    []
                );

                continue;
            }

            /*
             * Choose a designated participant
             */
            /** @var Models\Participant $participant */
            $participant = $round->participants->random(1)->first();

            $round->update([
                'designated_participant_id' => $participant->getKey(),
            ]);

            $pusher->trigger(
                'group-' . $round->group->getKey(),
                'participant-chosen',
                [
                    'participant' => $participant->getAttribute('name'),
                    'participant_id' => $participant->getKey(),
                    'participants' => $round->participants->pluck('id'),
                    'round_id' => $round->getKey(),
                ]
            );
        }
    }
}
