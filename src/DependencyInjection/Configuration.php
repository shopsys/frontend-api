<?php

declare(strict_types=1);

namespace Shopsys\FrontendApiBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder();

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('shopsys_frontend_api');

        $rootNode
            ->children()
                ->append($this->createEnabledDomainsNode())
            ->end();

        return $treeBuilder;
    }

    /**
     * @return \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    public function createEnabledDomainsNode(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder();

        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->root('enabled_domains');

        $rootNode
            ->treatNullLike([])
            ->prototype('scalar')
            ->end();

        return $rootNode;
    }
}
