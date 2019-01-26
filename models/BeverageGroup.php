<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Model;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
/** @noinspection LongInheritanceChainInspection */

/**
 * Class BeverageGroup
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 */
class BeverageGroup extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_beverage_groups';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $hasMany = [
        'beverages' => [
            Beverage::class,
            'key' => 'group_id',
        ],
    ];
}
