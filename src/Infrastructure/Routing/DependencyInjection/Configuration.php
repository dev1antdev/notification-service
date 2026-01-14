<?php

declare(strict_types=1);

namespace App\Infrastructure\Routing\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('delivery_routing');

        $root = $treeBuilder->getRootNode();
        $root
            ->children()
                ->arrayNode('channels')
                    ->isRequired()
                    ->useAttributeAsKey('name')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('primary')->isRequired()->cannotBeEmpty()->end()
                            ->arrayNode('fallbacks')->scalarPrototype()->end()->defaultValue([])->end()
                            ->arrayNode('switch_on')->scalarPrototype()->end()->defaultValue([])->end()
                            ->arrayNode('options')->variablePrototype()->end()->defaultValue([])->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
