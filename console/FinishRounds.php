<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\Console;

use Adrenth\CoffeeManager\Models;
use Carbon\Carbon;
use Illuminate\Console\Command;
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
     * @param Pusher $pusher
     * @throws PusherException
     */
    public function handle(Pusher $pusher): void
    {
        $rounds = Models\Round::query()
            ->where('is_finished', '=', false)
            ->get();

        /** @var Models\Round $round */
        foreach ($rounds as $round) {
            if ($round->expires_at->addMinutes(5) > Carbon::now()) {
                continue;
            }

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
