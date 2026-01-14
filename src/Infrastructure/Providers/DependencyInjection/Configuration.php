<?php

declare(strict_types=1);

namespace App\Infrastructure\Providers\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('providers');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('sms')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->variablePrototype()->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('email')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->variablePrototype()->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('push')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->variablePrototype()->end()
                    ->end()
                    ->defaultValue([])
                ->end()
                ->arrayNode('webhook')
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->variablePrototype()->end()
                    ->end()
                    ->defaultValue([])
            ->end()
        ;

        return $treeBuilder;
    }
}
