<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager;

use Adrenth\CoffeeManager\ServiceProviders\CoffeeManager;
use Backend\Helpers\Backend;
use System\Classes\PluginBase;
use Adrenth\CoffeeManager\Components;

/**
 * Class Plugin
 *
 * @package Adrenth\CoffeeManager
 */
class Plugin extends PluginBase
{
    /**
     * {@inheritdoc}
     */
    public function pluginDetails(): array
    {
        return [
            'name' => 'Adrenth.CoffeeManager',
            'author' => 'A. Drenth <adrenth@gmail.com>',
            'icon' => 'icon-coffee',
            'homepage' => 'http://github.com/adrenth',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->register(CoffeeManager::class);
    }

    /**
     * {@inheritdoc}
     */
    public function registerComponents(): array
    {
        return [
            Components\Join::class => 'coffeeManagerJoin',
            Components\Client::class => 'coffeeManagerClient',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerPermissions(): array
    {
        return [
            'adrenth.coffeemanager.access_groups' => [
                'label' => 'Manage Groups',
                'tab' => 'Coffee Manager',
                'roles' => ['developer'],
            ],
            'adrenth.coffeemanager.access_beverage_groups' => [
                'label' => 'Manage Beverage Groups',
                'tab' => 'Coffee Manager',
                'roles' => ['developer'],
            ],
            'adrenth.coffeemanager.access_beverages' => [
                'label' => 'Manage Beverages',
                'tab' => 'Coffee Manager',
                'roles' => ['developer'],
            ],
            'adrenth.coffeemanager.access_participants' => [
                'label' => 'Manage Participants',
                'tab' => 'Coffee Manager',
                'roles' => ['developer'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function registerNavigation(): array
    {
        /** @var Backend $backendHelper */
        $backendHelper = resolve(Backend::class);

        return [
            'coffeemanager' => [
                'label' => 'Coffee Manager',
                'url' => $backendHelper->url('adrenth/coffeemanager/groups'),
                'icon' => 'icon-coffee',
                'permissions' => ['adrenth.coffeemanager.*'],
                'order' => 500,
                'sideMenu' => [
                    'groups' => [
                        'label' => 'Groups',
                        'icon' => 'icon-sitemap',
                        'url' => $backendHelper->url('adrenth/coffeemanager/groups'),
                        'permissions' => ['adrenth.coffeemanager.access_groups'],
                    ],
                    'beverage-groups' => [
                        'label' => 'Beverage Groups',
                        'icon' => 'icon-sitemap',
                        'url' => $backendHelper->url('adrenth/coffeemanager/beveragegroups'),
                        'permissions' => ['adrenth.coffeemanager.access_beverage_groups'],
                    ],
                    'beverages' => [
                        'label' => 'Beverages',
                        'icon' => 'icon-coffee',
                        'url' => $backendHelper->url('adrenth/coffeemanager/beverages'),
                        'permissions' => ['adrenth.coffeemanager.access_beverages'],
                    ],
                    'participants' => [
                        'label' => 'Participants',
                        'icon' => 'icon-users',
                        'url' => $backendHelper->url('adrenth/coffeemanager/participants'),
                        'permissions' => ['adrenth.coffeemanager.access_participants'],
                    ],
                ],
            ],
        ];
    }
}
