<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Controllers;

use Backend\Behaviors\FormController;
use Backend\Behaviors\ListController;
use Backend\Classes\NavigationManager;
use Backend\Classes\Controller;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */

/**
 * Class Participants
 *
 * Participants Back-end Controller.
 *
 * @package Adrenth\CoffeeManager\Controllers
 * @mixin ListController
 * @mixin FormController
 */
class Participants extends Controller
{
    /** {@inheritdoc} */
    public $implement = [
        FormController::class,
        ListController::class,
    ];

    /** @var string */
    public $formConfig = 'config_form.yaml';

    /** @var string */
    public $listConfig = 'config_list.yaml';

     /** {@inheritdoc} */
     public $requiredPermissions = ['adrenth.coffeemanager.access_participants'];

    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();

        NavigationManager::instance()->setContext('Adrenth.CoffeeManager', 'coffeemanager', 'participants');
    }
}
