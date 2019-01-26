<?php

declare(strict_types=1);

namespace Adrenth\CoffeeManager\ServiceProviders;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use October\Rain\Support\ServiceProvider;
use Pusher\Pusher;

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

    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(Pusher::class, function (Container $container): Pusher {
            /** @var Repository $config */
            $config = $container->make(Repository::class);

            return new Pusher(
                $config->get('coffeemanager.pusher.auth_key'),
                $config->get('coffeemanager.pusher.secret'),
                $config->get('coffeemanager.pusher.app_id'),
                [
                    'cluster' => $config->get('coffeemanager.pusher.options.cluster'),
                    'useTLS' => true,
                ]
            );
        });
    }
}
