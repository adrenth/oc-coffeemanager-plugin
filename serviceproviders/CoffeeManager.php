<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\ServiceProviders;

use October\Rain\Support\ServiceProvider;

/**
 * Class CoffeeManager
 *
 * @package Adrenth\CoffeeManager\ServiceProviders
 */
class CoffeeManager extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config.php' => config_path('coffeemanager.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__ . '/../config.php', 'coffeemanager');
    }

    public function register(): void
    {
        $this->app->singleton(\Pusher\Pusher::class, function () {
            return new \Pusher\Pusher(
                config('coffeemanager.pusher.auth_key'),
                config('coffeemanager.pusher.secret'),
                config('coffeemanager.pusher.app_id'),
                [
                    'cluster' => config('coffeemanager.pusher.options.cluster'),
                    'useTLS' => true
                ]
            );
        });
    }
}
