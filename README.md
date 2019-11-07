# Kits

## 1. Log

> Log content to *.log with path `storage/logs/`

- Need `monolog/monolog`
- Attention function `storage_path()`

## 2. Tree

> Build tree for array and getTree

## 3. Excel

> Excel export or import

- Need `"maatwebsite/excel": ">=3.0"`

## 4. Database Design Manual

> Manual for database design and usage

## 5. Phpunit

> Listener and Handler for phpunit sql analyze

- Binding listener

in Laravel 5.1 in `app\Providers\EventServiceProvider.php` => `boot()`

```php
if (env('APP_ENV') == 'testing') {
    app()->singleton('Tests\Query\Listener', function ($app) {
        return new Listener();
    });
    $events->listen('illuminate.query', function ($query, $bindings, $time) {
        app()->make('Tests\Query\Listener')->analyzeSQL($query, $bindings, $time);
    });
}
```

in Laravel 5.5 in `app\Providers\AppServiceProvider.php` => `boot()`

```php
if (config('app.env') == 'testing') {
    app()->singleton('Tests\Query\Listener', function ($app) {
        return new Listener();
    });
    \DB::listen(function ($query) {
        app()->make('Tests\Query\Listener')->analyzeSQL($query->sql, $query->bindings, $query->time);
    });
}
```

- Usage `queryCorrect()` and `showQueries()`
