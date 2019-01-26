<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use Eloquent;
use October\Rain\Database\Collection;
use October\Rain\Database\Model;
use October\Rain\Database\Relations\BelongsToMany;

/** @noinspection ClassOverridesFieldOfSuperClassInspection */
/** @noinspection LongInheritanceChainInspection */

/**
 * Class Round
 *
 * @package Adrenth\CoffeeManager\Models
 * @mixin Eloquent
 * @property Group group
 * @property Collection participants
 * @method BelongsToMany participants()
 * @property Participant initiatingParticipant
 */
class Round extends Model
{
    /**
     * {@inheritdoc}
     */
    public $table = 'adrenth_coffeemanager_rounds';

    /**
     * {@inheritdoc}
     */
    protected $guarded = [];

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'initiatingParticipant' => [
            Participant::class,
            'key' => 'initiating_participant_id'
        ],
        'group' => [
            Group::class
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public $belongsToMany = [
        'participants' => [
            Participant::class,
            'pivot' => ['beverage_id'],
            'table' => 'adrenth_coffeemanager_round_participant'
        ]
    ];
}
