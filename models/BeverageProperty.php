<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Model;

/**
 * Class BeverageProperty
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 * @property Beverage beverage
 */
class BeverageProperty extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_beverage_properties';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'beverage' => [
            Beverage::class,
            'key' => 'beverage_id',
        ],
    ];
}
