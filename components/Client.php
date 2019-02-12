<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Classes\Exceptions\OngoingRound;
use Adrenth\CoffeeManager\Classes\RoundHelper;
use Adrenth\CoffeeManager\Models;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Exception;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
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
     * @var Models\Participant
     */
    public $participant;

    /**
     * @var Collection
     */
    public $participants;

    /**
     * @var Models\Round
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
            'description' => '',
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
            'profilePage' => [
                'label' => 'Coffee Manager Profile Page',
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
        $this->controller->addJs('/plugins/adrenth/coffeemanager/assets/js/jquery.countdown.min.js');

        $this->prepareVars();
    }

    /**
     * @throws ModelNotFoundException
     */
    protected function prepareVars(): void
    {
        $this->participant = Models\Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $this->round = $this->participant->group->round;

        $this->beverageGroups = Models\BeverageGroup::query()
            ->orderBy('name')
            ->get();

        $this->beverages = Models\Beverage::query()
            ->orderBy('name')
            ->get(['name', 'id'])
            ->pluck('name', 'id')
            ->toArray();

        $this->participants = $this->round
            ? $this->round->participants
            : new Collection();
    }

    /**
     * @throws ModelNotFoundException
     */
    public function onInitiateRound(): array
    {
        $this->prepareVars();

        /** @var Factory $validationFactory */
        $validationFactory = resolve(Factory::class);

        $validator = $validationFactory->make(
            $this->request->all(),
            [
                'minutes' => 'in:1,2,3,4,5'
            ]
        );

        if ($validator->fails()) {
            return [];
        }

        try {
            (new RoundHelper())->initiate(
                (int) $this->session->get('coffeemanager.participantId'),
                (int) $this->request->get('minutes', 2),
                (int) $this->request->get('beverageId')
            );
        } catch (OngoingRound $e) {
            $this->flashBag->error($e->getMessage());
        }

        $this->prepareVars();

        return [
            '#session-actions' => $this->renderPartial($this->alias . '::_session-actions'),
            '#round-details' => $this->renderPartial($this->alias . '::_round-details'),
            '#round-join' => $this->renderPartial($this->alias . '::_round-join'),
        ];
    }

    /**
     * @throws ModelNotFoundException
     */
    public function onJoinRound(): array
    {
        (new RoundHelper())->join(
            (int) $this->request->get('roundId'),
            (int) $this->session->get('coffeemanager.participantId'),
            (int) $this->request->get('beverageId')
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
     */
    public function onServeRound(): array
    {
        (new RoundHelper())->serve(
            (int) $this->request->get('roundId'),
            (int) $this->session->get('coffeemanager.participantId')
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
     */
    public function onLeaveRound(): array
    {
        (new RoundHelper())->leave(
            (int) $this->request->get('roundId'),
            (int) $this->session->get('coffeemanager.participantId')
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
     * @throws Exception
     */
    public function onCancelRound(): array
    {
        (new RoundHelper())->cancel(
            (int) $this->request->get('roundId'),
            (int) $this->session->get('coffeemanager.participantId')
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
     */
    public function onFinishRound(): array
    {
        (new RoundHelper())->finish(
            (int) $this->request->get('roundId'),
            (int) $this->session->get('coffeemanager.participantId')
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
