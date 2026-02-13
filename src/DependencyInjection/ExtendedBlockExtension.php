<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Dependency Injection Extension
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * DI Extension for Extended Block Bundle.
 *
 * This class handles the loading of service configurations and
 * bundle parameters into the Symfony dependency injection container.
 *
 * @see https://symfony.com/doc/current/bundles/extension.html
 */
class ExtendedBlockExtension extends Extension
{
    /**
     * Loads the bundle configuration into the container.
     *
     * This method is called when the container is being built and
     * loads all service definitions from the config directory.
     *
     * @param array<string, mixed> $configs   The bundle configuration
     * @param ContainerBuilder     $container The container builder
     *
     * @return void
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        // Process and validate bundle configuration
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        // Set bundle configuration parameters
        $container->setParameter('extended_block.table_prefix', $config['table_prefix']);
        $container->setParameter('extended_block.enable_localized_fields', $config['enable_localized_fields']);
        $container->setParameter('extended_block.strict_mode', $config['strict_mode']);

        // Load service definitions from YAML configuration
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
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
}
