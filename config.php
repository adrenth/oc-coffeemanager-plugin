<?php

declare(strict_types=1);

return [
    'pusher' => [
        'auth_key' => env('COFFEE_MANAGER_PUSHER_AUTH_KEY'),
        'secret' => env('COFFEE_MANAGER_PUSHER_SECRET'),
        'app_id' => env('COFFEE_MANAGER_PUSHER_APP_ID'),
        'options' => [
            'cluster' => env('COFFEE_MANAGER_PUSHER_CLUSTER', 'eu'),
            'useTLS' => true
        ]
    ]
];
