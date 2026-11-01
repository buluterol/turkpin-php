<?php

declare(strict_types=1);

namespace Turkpin\Tests\Integration;

use Orchestra\Testbench\TestCase;
use Turkpin\Client;
use Turkpin\Laravel\Facades\Turkpin;
use Turkpin\Laravel\TurkpinServiceProvider;

// orchestra/testbench is not installed on PHP 8.1 in CI (Laravel 10 is blocked by security advisories)
if (!class_exists(TestCase::class)) {
    return;
}

final class LaravelTest extends TestCase
{
    protected function getPackageProviders($app): array
    {
        return [TurkpinServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('turkpin.username', 'dealer@example.com');
        $app['config']->set('turkpin.password', 'secret');
        $app['config']->set('turkpin.test', true);
    }

    public function testClientIsBoundAsSingleton(): void
    {
        $client = $this->app->make(Client::class);

        self::assertSame($client, $this->app->make('turkpin'));
        self::assertSame($client, Turkpin::getFacadeRoot());
        self::assertSame(Client::TEST_URL, $client->getUrl());
    }

    public function testDefaultsToLive(): void
    {
        $this->app['config']->set('turkpin.test', false);
        $this->app->forgetInstance(Client::class);

        self::assertSame(Client::LIVE_URL, $this->app->make(Client::class)->getUrl());
    }
}
