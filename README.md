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

Session time
