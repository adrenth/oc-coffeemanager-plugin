<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Models;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Session\Store;
use Illuminate\Validation\Factory;
use InvalidArgumentException;
use October\Rain\Database\Collection;
use October\Rain\Flash\FlashBag;
use Pusher\Pusher;
use ValidationException;

/**
 * Class Profile
 *
 * @package Adrenth\CoffeeManager\Components
 */
class Profile extends ComponentBase
{
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
            'name' => 'Profile Component',
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
            'clientPage' => [
                'label' => 'Coffee Manager Client Page',
            ],
        ];
    }

    /**
     * This method is used the first time the component is rendered into the
     * page.
     *
     * {@inheritdoc}
     * @return mixed
     * @throws ModelNotFoundException
     */
    public function onRun()
    {
        if (!$this->session->has('coffeemanager.participantId')) {
            return $this->redirector->to(Page::url($this->property('joinPage')));
        }

        $this->prepareVars();
    }

    /**
     * @throws ValidationException
     * @throws InvalidArgumentException
     * @throws ModelNotFoundException
     */
    public function onSaveProfile(): array
    {
        $this->prepareVars();

        /** @var Factory $validationFactory */
        $validationFactory = resolve(Factory::class);
        $validator = $validationFactory->make(
            $this->request->all(),
            [
                'name' => 'required|min:1|max:191',
                'defaultBeverageId' => 'required'
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->participant->update([
            'name' => preg_replace('/[^[:alnum:][:space:]]/u', '', $this->request->get('name')),
            'default_beverage_id' => $this->request->get('defaultBeverageId')
        ]);

        $this->flashBag->success('Jouw profiel is bijgewerkt.');

        return [
            '#profileFormElements' => $this->renderPartial($this->alias . '::_form-elements')
        ];
    }

    /**
     * Prepare variables for use in AJAX handlers.
     *
     * @return void
     * @throws ModelNotFoundException
     */
    protected function prepareVars(): void
    {
        $this->participant = Models\Participant::query()
            ->findOrFail($this->session->get('coffeemanager.participantId'));

        $this->beverageGroups = Models\BeverageGroup::query()
            ->orderBy('name')
            ->get();
    }
}
