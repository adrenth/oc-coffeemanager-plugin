<?php

declare(strict_types=1);

use Adrenth\CoffeeManager\Models\Participant;
use Illuminate\Routing\Router;
use Illuminate\Session\Store;
use Laravel\Socialite\Contracts\Factory;

$router = resolve(Router::class);

$router->group(['middleware' => ['web']], static function () use ($router) {
    // TODO: Move to controller
    $router->get('/login/google', static function (Factory $socialiteFactory) {
        return $socialiteFactory->driver('google')->redirect();
    });

    // TODO: Move to controller
    $router->get('/google/callback', static function (Factory $socialiteFactory, Store $store) {
        try {
            $user = $socialiteFactory->driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->to('/');
        }

        // TODO: Add email to participant table (migration)
        $existingUser = Participant::query()->where('email', $user->getEmail())->firstOrFail();

        $store->put('coffeemanager.participantId', $existingUser->getKey());

        return redirect()->to('/');
    });
});
