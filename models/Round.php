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
 * @property Participant initiatingParticipant
 * @property Participant designatedParticipant
 * @method BelongsToMany participants()
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
            'key' => 'initiating_participant_id',
        ],
        'designatedParticipant' => [
            Participant::class,
            'key' => 'designated_participant_id',
        ],
        'group' => [
            Group::class,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public $belongsToMany = [
        'participants' => [
            Participant::class,
            'pivot' => [
                'beverage_id',
                'updated_at',
                'created_at',
            ],
            'table' => 'adrenth_coffeemanager_round_participant',
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected $dates = [
        'expires_at'
    ];
}
