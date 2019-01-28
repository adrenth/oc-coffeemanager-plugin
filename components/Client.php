<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Models\Beverage;
use Adrenth\CoffeeManager\Models\BeverageGroup;
use Adrenth\CoffeeManager\Models\Participant;
use Adrenth\CoffeeManager\Models\Round;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store;
use October\Rain\Database\Collection;
use October\Rain\Flash\FlashBag;
use Pusher\Pusher;
use Pusher\PusherException;

/**
 * Class Client
 *
 * @package Adrenth\CoffeeManager\Console
 */
class Client extends ComponentBase
{
    /**
     * @var array
     */
    private static $allowedPartials = [
        '_participant-details',
        '_round-details',
        '_round-join',
        '_session-actions',
    ];

    /**
     * @var array
     */
    public $config;

    /**
     * @var Participant
     */
    public $participant;

    /**
     * @var Collection
     */
    public $participants;

    /**
     * @var Round
     */
    public $round;

    /**
     * @var array
     */
    public $beverages;

    /**
     * @var Collection
     */
    public $beverageGroups;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Store
     */
    private $session;

    /**
     * @var FlashBag
     */
    private $flashBag;

    /**
     * @var Pusher
     */
    private $pusher;

    /**
     * @var Redirector
     */
    private $redirector;

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        $this->request = resolve(Request::class);
        $this->session = resolve(Store::class);
        $this->config = config('coffeemanager');
        $this->flashBag = resolve(FlashBag::class);
        $this->pusher = resolve(Pusher::class);
        $this->redirector = resolve(Redirector::class);
    }

    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Client Component',
            'description' => 'This programmer was too lazy to put a description here...',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineProperties(): array
    {
        return [
            'joinPage' => [
                'label' => 'Coffee Manager Join Page',
            ],
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * This method is used the first time the component is rendered into the
     * page.
     *
     * @return mixed
     * @throws ModelNotFoundException
     */
    public function onRun()
    {
        if (!$this->session->has('coffeemanager.participantId')) {
            return $this->redirector->to(Page::url($this->property('joinPage')));
        }

        $this->controller->addJs('https://js.pusher.com/4.3/pusher.min.js');
        $this->controller->addJs('/plugins/adrenth/coffeemanager/assets/js/client.js');

        $this->prepareVars();
    }

    /**
     * @throws ModelNotFoundException
     */
    protected function prepareVars(): void
    {
        $this->participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $this->round = $this->participant->group->round;

        $this->beverageGroups = BeverageGroup::query()
            ->orderBy('name')
            ->get();

        $this->beverages = Beverage::query()
            ->orderBy('name')
            ->get(['name', 'id'])
            ->pluck('name', 'id')
            ->toArray();

        $this->participants = $this->round ? $this->round->participants : new Collection();
    }

    /**
     * @throws PusherException
     * @throws ModelNotFoundException
     */
    public function onInitiateRound(): array
    {
        $this->prepareVars();

        $this->participant->group->reload();

        if ($this->participant->group->getAttribute('current_round_id') !== null) {
            $this->flashBag->error('There\'s currently a round ongoing!');
            return [];
        }

        $round = Round::create([
            'group_id' => $this->participant->group->getKey(),
            'initiating_participant_id' => $this->participant->getKey(),
            'expires_at' => now()->addMinutes(3)->toDateTimeString(),
        ]);

        $this->participant->group->update([
            'current_round_id' => $round->getKey(),
        ]);

        $this->pusher->trigger(
            'group-' . $this->participant->group->getKey(),
            'participant-initiates-new-round',
            [
                'participant' => $this->participant->getAttribute('name'),
                'participant_id' => $this->participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#session-actions' => $this->renderPartial($this->alias . '::_session-actions'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @throws ModelNotFoundException
     * @throws PusherException
     */
    public function onJoinRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $round->participants()->add(
            $participant,
            null,
            [
                'beverage_id' => $this->request->get('beverage_id'),
            ]
        );

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'participant-joined-round',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#session-actions' => $this->renderPartial($this->alias . '::_session-actions'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     * @throws PusherException
     */
    public function onServeRound(): array
    {
        /** @var Round $round */
        $round = Round::query()
            ->where('id', $this->request->get('round_id'))
            ->whereNull('designated_participant_id')
            ->firstOrFail();

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $round->update([
            'designated_participant_id' => $participant->getKey(),
        ]);

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'participant-chosen',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#session-actions' => $this->renderPartial($this->alias . '::_session-actions'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     * @throws PusherException
     */
    public function onLeaveRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $round->participants()->remove($participant);

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'participant-left-round',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @return RedirectResponse
     */
    public function onLeave(): RedirectResponse
    {
        $this->session->forget('coffeemanager.participantId');

        return $this->redirector->to(Page::url($this->property('joinPage')));
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     * @throws PusherException
     * @throws Exception
     */
    public function onCancelRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        if ($round->initiatingParticipant->getKey() !== $participant->getKey()) {
            $this->flashBag->error('You are not allowed to do that!');
            return [];
        }

        $round->delete();

        $participant->group->update(['current_round_id' => null]);

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'round-cancelled',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#session-actions' => $this->renderPartial($this->alias . '::_session-actions'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     * @throws PusherException
     */
    public function onFinishRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        if ($round->designatedParticipant->getKey() !== $participant->getKey()) {
            dd(1);
            $this->flashBag->error('You are not allowed to do that!');
            return [];
        }

        $round->update([
            'is_finished' => true,
        ]);

        $participant->group->update(['current_round_id' => null]);

        $participant->update([
            'score' => $participant->getAttribute('score') + 1,
        ]);

        /** @var Participant $roundParticipant */
        foreach ($round->participants as $roundParticipant) {
            $roundParticipant->update([
                'last_beverage_id' => $roundParticipant->pivot->beverage_id
            ]);
        }

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'round-finished',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey(),
            ]
        );

        $this->prepareVars();

        return [
            '#participant-details' => $this->renderPartial($this->alias . '::_participant-details'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     */
    public function onRefresh(): array
    {
        $this->prepareVars();

        $partials = [];
        $partialIds = explode(',', $this->request->get('partialIds', ''));

        foreach ($partialIds as $partialId) {
            if (!in_array($partialId, self::$allowedPartials, true)) {
                continue;
            }

            $partials['#' . ltrim($partialId, '_')] = $this->renderPartial(
                $this->alias . '::' . $partialId
            );
        }

        return $partials;
    }
}
