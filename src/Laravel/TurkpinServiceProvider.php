<?php

declare(strict_types=1);

namespace Turkpin\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Turkpin\Client;

/** Registers Turkpin\Client as a singleton built from config/turkpin.php. */
final class TurkpinServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/turkpin.php', 'turkpin');

        $this->app->singleton(Client::class, static function (Application $app): Client {
            $config = $app['config']['turkpin'];

            return new Client(
                (string) $config['username'],
                (string) $config['password'],
                (bool) $config['test'],
                (int) $config['timeout'],
            );
        });
        $this->app->alias(Client::class, 'turkpin');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../../config/turkpin.php' => config_path('turkpin.php')], 'turkpin-config');
        }
    }
}
