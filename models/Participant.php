<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Model;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
/** @noinspection LongInheritanceChainInspection */

/**
 * Class Participant
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 * @property Group group
 * @property Beverage defaultBeverage
 * @property Beverage lastBeverage
 * @property participantRounds participantRounds
 */
class Participant extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_participants';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'group' => Group::class,
        'defaultBeverage' => Beverage::class,
        'lastBeverage' => Beverage::class,
    ];

    /**
     * {@inheritdoc}
     */
    public $hasMany = [
        'participantRounds' => RoundParticipant::class,
    ];
}
