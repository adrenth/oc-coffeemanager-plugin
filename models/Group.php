<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Model;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
/** @noinspection LongInheritanceChainInspection */

/**
 * Class Group
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 * @property Round|null round
 */
class Group extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_groups';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'round' => [
            Round::class,
            'key' => 'current_round_id',
        ]
    ];
}
