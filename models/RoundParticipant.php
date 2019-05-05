<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Models;

use October\Rain\Database\Model;

/**
 * Class RoundParticipant
 *
 * @package Adrenth\CoffeeManager\Models
 */
class RoundParticipant extends Model
{
    /**
     * {@inheritdoc}
     */
    protected $table = 'adrenth_coffeemanager_round_participant';

    /**
     * {@inheritdoc}
     */
    protected $primaryKey = ['round_id', 'participant_id', 'beverage_id'];

    /**
     * {@inheritdoc}
     */
    public $incrementing = false;

    /**
     * {@inheritdoc}
     */
    public $belongsTo = [
        'round' => [
            Round::class
        ],
        'participant' => [
            Participant::class
        ],
        'beverage' => [
            Beverage::class
        ]
    ];
}
