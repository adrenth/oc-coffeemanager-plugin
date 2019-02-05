<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Components;

use Adrenth\CoffeeManager\Models\Group;
use Adrenth\CoffeeManager\Models\Participant;
use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use October\Rain\Database\Collection;

/**
 * Class Join
 *
 * @package Adrenth\CoffeeManager\Console
 */
class Join extends ComponentBase
{
    /**
     * @var Collection
     */
    public $groups;

    /**
     * @var Collection
     */
    public $participants;

    /**
     * {@inheritdoc}
     */
    public function componentDetails(): array
    {
        return [
            'name' => 'Join Component',
            'description' => 'This programmer was too lazy to put a description here...',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function defineProperties(): array
    {
        return [
            'clientPage' => [
                'label' => 'Coffee Manager Client Page',
            ],
        ];
    }

    /** @noinspection PhpMissingParentCallCommonInspection */

    /**
     * This method is used the first time the component is rendered into the page.
     *
     * {@inheritdoc}
     */
    public function onRun(): void
    {
        $this->prepareVars();
    }

    /**
     * @return RedirectResponse
     * @throws ModelNotFoundException
     */
    public function onJoin(): RedirectResponse
    {
        /** @var Request $request */
        $request = resolve(Request::class);

        /** @var Store $session */
        $session = resolve(Store::class);

        $groupId = (int) $request->get('participantGroupId');

        /** @var Participant $participant */
        $participant = Participant::query()
            ->findOrFail((int) $request->get('participantId'));

        if ($participant->group->getKey() !== $groupId) {
            $participant->update([
                'group_id' => $groupId
            ]);
        }

        $session->put('coffeemanager.participantId', $participant->getKey());

        return redirect()->to(Page::url($this->property('clientPage')));
    }

    /**
     * @return array
     * @throws ModelNotFoundException
     */
    public function onChangeParticipant(): array
    {
        /** @var Request $request */
        $request = resolve(Request::class);

        /** @var Participant $participant */
        $participant = Participant::query()->findOrFail(
            (int) $request->get('participantId')
        );

        return [
            '#participantGroupWrapper' => $this->renderPartial($this->alias . '::_group', [
                'selectedGroupId' => $participant ? $participant->group->getKey() : null,
                'groups' => $this->getGroups(),
            ])
        ];
    }

    /**
     * Prepare variables for use in AJAX handlers.
     *
     * @return void
     */
    protected function prepareVars(): void
    {
        $this->groups = $this->getGroups();
        $this->participants = $this->getParticipants();
    }

    /**
     * @return Collection
     */
    private function getGroups(): Collection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Group::query()
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection
     */
    private function getParticipants(): Collection
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Participant::query()
            ->orderBy('name')
            ->get();
    }
}
