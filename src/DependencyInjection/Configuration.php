<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Configuration Definition.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Configuration class for Extended Block Bundle.
 *
 * Defines the structure and validation rules for the bundle configuration.
 * Configuration can be set in config/packages/extended_block.yaml:
 *
 * ```yaml
 * extended_block:
 *     table_prefix: 'eb_'
 *     strict_mode: true
 * ```
 *
 * @see https://symfony.com/doc/current/bundles/configuration.html
 */
class Configuration implements ConfigurationInterface
{
    /**
     * The default table prefix for extended block tables.
     */
    public const DEFAULT_TABLE_PREFIX = 'object_eb_';

    /**
     * Builds and returns the configuration tree.
     *
     * Defines all available configuration options with their types,
     * default values, and validation rules.
     *
     * @return TreeBuilder The configuration tree builder
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('extended_block');

        // @phpstan-ignore-next-line - getRootNode returns ArrayNodeDefinition for named TreeBuilder
        $treeBuilder->getRootNode()
            ->children()
                // Table prefix for all extended block tables
                ->scalarNode('table_prefix')
                    ->defaultValue(self::DEFAULT_TABLE_PREFIX)
                    ->info('Prefix for database tables created by extended blocks')
                    ->example('object_eb_')
                    ->validate()
                        ->ifTrue(static function ($value) {
                            return !preg_match('/^[a-z_][a-z0-9_]*$/i', $value);
                        })
                        ->thenInvalid('Table prefix must be a valid SQL identifier (letters, numbers, underscores)')
                    ->end()
                ->end()

                // Strict mode for validation
                ->booleanNode('strict_mode')
                    ->defaultTrue()
                    ->info('Enable strict validation to prevent ExtendedBlock nesting in LocalizedFields')
                ->end()

                // Maximum items allowed in a single extended block
                ->integerNode('max_items')
                    ->defaultNull()
                    ->min(1)
                    ->info('Maximum number of items allowed in a single extended block (null for unlimited)')
                ->end()

                // Enable query logging for debugging
                ->booleanNode('debug_queries')
                    ->defaultFalse()
                    ->info('Enable query logging for debugging purposes')
                ->end()
            ->end();

        return $treeBuilder;
    }
}
