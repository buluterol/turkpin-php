<?php

declare(strict_types=1);

namespace Turkpin\Symfony;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Turkpin\Client;

/**
 * Registers Turkpin\Client as a service. Configuration (config/packages/turkpin.yaml):
 *
 *     turkpin:
 *         username: '%env(TURKPIN_USERNAME)%'
 *         password: '%env(TURKPIN_PASSWORD)%'
 *         test: false
 *         timeout: 30
 */
final class TurkpinBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->scalarNode('username')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('password')->isRequired()->cannotBeEmpty()->end()
                ->booleanNode('test')->defaultFalse()->end()
                ->integerNode('timeout')->defaultValue(30)->min(1)->end()
            ->end();
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->services()
            ->set(Client::class)
                ->args([$config['username'], $config['password'], $config['test'], $config['timeout']])
                ->public()
            ->alias('turkpin', Client::class)
                ->public();
    }
}
