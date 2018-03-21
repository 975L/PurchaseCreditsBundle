<?php
/*
 * (c) 2018: 975l <contact@975l.com>
 * (c) 2018: Laurent Marquet <laurent.marquet@laposte.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace c975L\PurchaseCreditsBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('c975_l_purchase_credits');

        $rootNode
            ->children()
                ->arrayNode('creditsNumber')
                    ->prototype('scalar')->end()
                ->end()
                ->arrayNode('creditsPrice')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('currency')
                    ->defaultValue('EUR')
                ->end()
                ->scalarNode('live')
                    ->defaultFalse()
                ->end()
                ->scalarNode('userEntity')
                ->end()
                ->scalarNode('roleNeeded')
                    ->defaultValue('ROLE_ADMIN')
                ->end()
                ->scalarNode('tosUrl')
                ->end()
                ->scalarNode('tosPdf')
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
