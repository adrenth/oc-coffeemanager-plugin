<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Model;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
/** @noinspection LongInheritanceChainInspection */

/**
 * Class Beverage
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 * @property BeverageProperty[] properties
 */
class Beverage extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_beverages';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $hasMany = [
        'properties' => [
            BeverageProperty::class,
            'key' => 'beverage_id',
            'order' => 'name',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'group' => BeverageGroup::class,
    ];
}
