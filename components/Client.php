<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Models\Beverage;
use Adrenth\CoffeeManager\Models\BeverageGroup;
use Adrenth\CoffeeManager\Models\Participant;
use Adrenth\CoffeeManager\Models\Round;
use Cms\Classes\CodeBase;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use October\Rain\Database\Collection;
use October\Rain\Flash\FlashBag;
use Pusher\Pusher;

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
     * {@inheritdoc}
     */
    public function __construct(
        CodeBase $cmsObject = null,
        array $properties = []
    ) {
        parent::__construct($cmsObject, $properties);

        $this->request = resolve(Request::class);
        $this->session = resolve(Store::class);
        $this->config = config('coffeemanager');
        $this->flashBag = resolve(FlashBag::class);
        $this->pusher = resolve(Pusher::class);
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

    /** @noinspection PhpMissingParentCallCommonInspection */
    /**
     * This method is used the first time the component is rendered into the
     * page.
     *
     * @return mixed
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function onRun()
    {
        if (!$this->session->has('coffeemanager.participantId')) {
            return redirect()->to(Page::url($this->property('joinPage')));
        }

        $this->controller->addJs('https://js.pusher.com/4.3/pusher.min.js');
        $this->controller->addJs('/plugins/adrenth/coffeemanager/assets/js/client.js');

        $this->prepareVars();
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    protected function prepareVars()
    {
        $this->participant = Participant::findOrFail(
            $this->session->get('coffeemanager.participantId')
        );

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
     * @throws \Pusher\PusherException
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
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
            'current_round_id' => $round->getKey()
        ]);

        $this->pusher->trigger(
            'group-' . $this->participant->group->getKey(),
            'participant-initiates-new-round',
            [
                'participant' => $this->participant->getAttribute('name'),
                'participant_id' => $this->participant->getKey(),
                'round_id' => $round->getKey()
            ]
        );

        $this->prepareVars();

        return [
            '#details' => $this->renderPartial($this->alias . '::_details')
        ];
    }

    /**
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Pusher\PusherException
     */
    public function onJoinRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::findOrFail(
            $this->session->get('coffeemanager.participantId')
        );

        $round->participants()->add(
            $participant,
            null,
            [
                'beverage_id' => $this->request->get('beverage_id')
            ]
        );

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'participant-joined-round',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey()
            ]
        );

        $this->prepareVars();

        return [
            '#details' => $this->renderPartial($this->alias . '::_details')
        ];
    }

    /**
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Pusher\PusherException
     */
    public function onLeaveRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::findOrFail(
            $this->session->get('coffeemanager.participantId')
        );

        $round->participants()->remove($participant);

        $this->pusher->trigger(
            'group-' . $round->group->getKey(),
            'participant-left-round',
            [
                'participant' => $participant->getAttribute('name'),
                'participant_id' => $participant->getKey(),
                'round_id' => $round->getKey()
            ]
        );

        $this->prepareVars();

        return [
            '#details' => $this->renderPartial($this->alias . '::_details')
        ];
    }

    /**
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     * @throws \Pusher\PusherException
     * @throws \Exception
     */
    public function onCancelRound(): array
    {
        /** @var Round $round */
        $round = Round::query()->findOrFail($this->request->get('round_id'));

        /** @var Participant $participant */
        $participant = Participant::findOrFail(
            $this->session->get('coffeemanager.participantId')
        );

        if ($round->initiatingParticipant->getKey() !== $participant->getKey()) {
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
                'round_id' => $round->getKey()
            ]
        );

        $this->prepareVars();

        return [
            '#details' => $this->renderPartial($this->alias . '::_details')
        ];
    }

    /**
     * @return array
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function onRefresh(): array
    {
        $this->prepareVars();

        return [
            '#details' => $this->renderPartial($this->alias . '::_details')
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
            ]
        ];
    }
}
