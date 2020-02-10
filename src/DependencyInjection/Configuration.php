<?php

namespace Flagbit\Bundle\ProductClonerBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see
 * {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $root = $treeBuilder->root('flagbit_product_cloner');

        $root->children()
            ->arrayNode('attribute_blacklist')
                ->info('A list of attribute codes that need to be left out for the clone')
                ->defaultValue([])
                ->scalarPrototype()
                    ->info('Valid attribute codes')
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
