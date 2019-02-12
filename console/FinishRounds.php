<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Console;

use Adrenth\CoffeeManager\Models;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Pusher\Pusher;
use Pusher\PusherException;

/**
 * Class FinishRounds
 *
 * @package Adrenth\CoffeeManager\Console
 */
class FinishRounds extends Command
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        $this->signature = 'adrenth:coffeemanager:finish-rounds';
        $this->description = 'Finish Unfinished Coffee Rounds';

        parent::__construct();
    }

    /**
     * @param Connection $connection
     * @param Pusher $pusher
     * @throws PusherException
     */
    public function handle(Connection $connection, Pusher $pusher): void
    {
        $rounds = Models\Round::query()
            ->where($connection->raw('expires_at + INTERVAL 10 MINUTE'), '<', Carbon::now())
            ->where('is_finished', '=', false)
            ->get();

        /** @var Models\Round $round */
        foreach ($rounds as $round) {
            $round->update([
                'is_finished' => true,
            ]);

            $round->group->update([
                'current_round_id' => null,
            ]);

            $pusher->trigger(
                'group-' . $round->group->getKey(),
                'round-finished-automatically',
                []
            );
        }
    }
}
