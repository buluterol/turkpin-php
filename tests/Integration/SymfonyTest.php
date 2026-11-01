<?php

declare(strict_types=1);

namespace Turkpin\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Turkpin\Client;
use Turkpin\Symfony\TurkpinBundle;

final class SymfonyTest extends TestCase
{
    private function container(array $config): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');
        $container->setParameter('kernel.build_dir', sys_get_temp_dir());
        $container->setParameter('kernel.debug', false);
        (new TurkpinBundle())->getContainerExtension()->load([$config], $container);
        $container->compile();

        return $container;
    }

    public function testServiceIsRegistered(): void
    {
        $container = $this->container(['username' => 'dealer@example.com', 'password' => 'secret', 'test' => true]);
        $client = $container->get(Client::class);

        self::assertInstanceOf(Client::class, $client);
        self::assertSame($client, $container->get('turkpin'));
        self::assertSame(Client::TEST_URL, $client->getUrl());
    }

    public function testCredentialsAreRequired(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->container(['test' => true]);
    }
}
