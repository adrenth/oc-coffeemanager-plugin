<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Classes\BeveragePredictor\BeveragePredictor;
use Adrenth\CoffeeManager\Classes\Exceptions\OngoingRound;
use Adrenth\CoffeeManager\Classes\RoundHelper;
use Adrenth\CoffeeManager\Models;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store;
use October\Rain\Database\Builder;
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
    public $participantBeverages;

    /**
     * @var Collection
     */
    public $participants;

    /**
     * @var Models\Beverage|null
     */
    public $predictedBeverage;

    /**
     * @var Models\Round
     */
    public $round;

    /**
     * @var Collection
     */
    public $previousRounds;

    /**
     * @var array
     */
    public $beverages;

    /**
     * @var Collection
     */
    public $beverageGroups;

    /**
     * @var BeveragePredictor
     */
    private $beveragePredictor;

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
        $this->beveragePredictor = resolve(BeveragePredictor::class);
        $this->request = resolve(Request::class);
        $this->session = resolve(Store::class);
        $this->config = config('coffeemanager');
        $this->flashBag = resolve('flash');
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

        $this->controller->addJs('https://js.pusher.com/4.4/pusher.min.js');
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


        // TODO: Limit participant rounds / collect data in some "DataCollector" object which outputs value objects

        $data = [];

        /** @var Models\RoundParticipant $participantRound */
        foreach ($this->participant->participantRounds as $participantRound) {
            $data[] = [
                'sample' => [
                    $participantRound->getAttribute('created_at')->format('Hi'),
                    $participantRound->getAttribute('created_at')->format('Hi')
                ],
                'label' => $participantRound->getAttribute('beverage_id'),
            ];
        }

        $predictedBeverageId = $this->beveragePredictor->predict($data);
        if ($predictedBeverageId) {
            $this->predictedBeverage = Models\Beverage::query()->findOrFail($predictedBeverageId);
        }

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

        $this->page->title = $this->participant->group->getAttribute('name');
        $this->page->subTitle = $this->participant->getAttribute('name');
    }

    /**
     * @throws ModelNotFoundException
     */
    public function onInitiateRound(): array
    {
        $this->prepareVars();

        try {
            (new RoundHelper())->initiate(
                (int) $this->session->get('coffeemanager.participantId'),
                3,
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
