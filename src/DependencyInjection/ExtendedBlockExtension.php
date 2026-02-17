<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Dependency Injection Extension.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\DependencyInjection;

use ExtendedBlockBundle\Model\DataObject\ClassDefinition\Data\ExtendedBlock;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * DI Extension for Extended Block Bundle.
 *
 * This class handles the loading of service configurations and
 * bundle parameters into the Symfony dependency injection container.
 *
 * Also registers the ExtendedBlock data type with Pimcore's implementation loader
 * to enable proper class definition import/export from JSON.
 *
 * @see https://symfony.com/doc/current/bundles/extension.html
 */
class ExtendedBlockExtension extends Extension implements PrependExtensionInterface
{
    /**
     * Loads the bundle configuration into the container.
     *
     * This method is called when the container is being built and
     * loads all service definitions from the config directory.
     *
     * @param array<string, mixed> $configs   The bundle configuration
     * @param ContainerBuilder     $container The container builder
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Process and validate bundle configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set bundle configuration parameters
        $container->setParameter('extended_block.table_prefix', $config['table_prefix']);
        $container->setParameter('extended_block.strict_mode', $config['strict_mode']);

        // Load service definitions from YAML configuration
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );

        // Load all service configuration files
        $loader->load('services.yaml');
    }

    /**
     * Returns the alias for this extension.
     *
     * The alias is used as the configuration key in config files:
     * ```yaml
     * extended_block:
     *     table_prefix: 'eb_'
     * ```
     *
     * @return string The extension alias
     */
    public function getAlias(): string
    {
        return 'extended_block';
    }

    /**
     * Prepends configuration to other bundles.
     *
     * Registers the ExtendedBlock data type with Pimcore's implementation loader
     * so that class definitions can be properly imported/exported from JSON.
     * This is essential for the generateLayoutTreeFromArray() method in
     * Pimcore\Model\DataObject\ClassDefinition\Service to work correctly.
     *
     * @param ContainerBuilder $container The container builder
     */
    public function prepend(ContainerBuilder $container): void
    {
        // Register ExtendedBlock data type with Pimcore's implementation loader
        // This allows Pimcore to properly instantiate ExtendedBlock when loading
        // class definitions from JSON (e.g., during import or cache rebuild)
        $container->prependExtensionConfig('pimcore', [
            'objects' => [
                'class_definitions' => [
                    'data' => [
                        'map' => [
                            'extendedBlock' => ExtendedBlock::class,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
