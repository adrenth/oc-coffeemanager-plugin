<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Behaviors\RelationController;
use Backend\Classes\NavigationManager;
use Backend\Classes\Controller;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * Class Beverages
 *
 * Beverages Back-end Controller.
 *
 * @package Adrenth\CoffeeManager\Controllers
 * @mixin FormController
 * @mixin ListController
 * @mixin RelationController
 */
class Beverages extends Controller
{
    /** {@inheritdoc} */
    public $implement = [
        FormController::class,
        ListController::class,
        RelationController::class,
    ];

    /** @var string */
    public $formConfig = 'config_form.yaml';

    /** @var string */
    public $listConfig = 'config_list.yaml';

    /** @var string */
    public $relationConfig = 'config_relation.yaml';

    /** {@inheritdoc} */
     public $requiredPermissions = ['adrenth.coffeemanager.access_beverages'];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        NavigationManager::instance()->setContext('Adrenth.CoffeeManager', 'coffeemanager', 'beverages');
    }
}
