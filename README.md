# Coffee Manager

## Installation

```
php artisan plugin:install Adrenth.CoffeeManager
```

## Configuration

```
php artisan vendor:publish --provider="Adrenth\CoffeeManager\ServiceProviders\CoffeeManager" --tag="config"
```

Add these environment variables to the `.env` file of your project:

```
COFFEE_MANAGER_PUSHER_AUTH_KEY = ""
COFFEE_MANAGER_PUSHER_SECRET = ""
COFFEE_MANAGER_PUSHER_APP_ID = ""
COFFEE_MANAGER_PUSHER_CLUSTER = "eu"
```

### Socialite Configuration

Add the Google service to `services.php`:

```
'google' => [
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect' => env('GOOGLE_REDIRECT'),
],
```

Add these environment variables to the `.env` file of your project:

```
GOOGLE_CLIENT_ID=xxxxxxx.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT=https://xxxxx.xxx/google/callback
```

TODO: Configuring Google API
