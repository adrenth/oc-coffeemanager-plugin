<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use BackendMenu;
use Backend\Classes\Controller;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * Class BeverageGroups
 *
 * Beverage Groups Back-end Controller.
 *
 * @package Adrenth\CoffeeManager\Controllers
 * @mixin ListController
 * @mixin FormController
 */
class BeverageGroups extends Controller
{
    /** {@inheritdoc} */
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController'
    ];

    /** @var string */
    public $formConfig = 'config_form.yaml';

    /** @var string */
    public $listConfig = 'config_list.yaml';

    // /** {@inheritdoc} */
    // public $requiredPermissions = ['adrenth.coffeemanager.'];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Adrenth.CoffeeManager', 'coffeemanager', 'beveragegroups');
    }
}
