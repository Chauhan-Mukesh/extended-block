<?php

declare(strict_types=1);

/**
 * Extended Block Bundle for Pimcore.
 *
 * This bundle provides an enhanced block data type that stores data in separate
 * database tables instead of serialized data in a single column.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle;

use ExtendedBlockBundle\Installer\ExtendedBlockInstaller;
use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Installer\InstallerInterface;
use Pimcore\Extension\Bundle\PimcoreBundleAdminClassicInterface;
use Pimcore\Extension\Bundle\Traits\BundleAdminClassicTrait;

/**
 * Main bundle class for Extended Block Bundle.
 *
 * This bundle extends Pimcore's block data type by storing block items in
 * separate database tables (similar to field collections) while maintaining
 * full compatibility with Pimcore's data model architecture.
 *
 * Key features:
 * - Separate table storage for each block type definition
 * - Full support for localized fields within block items
 * - Prevention of recursive ExtendedBlock in LocalizedFields
 * - Performance optimized database queries
 * - Complete admin UI integration
 *
 * @see Model\DataObject\ClassDefinition\Data\ExtendedBlock
 */
class ExtendedBlockBundle extends AbstractPimcoreBundle implements PimcoreBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;

    /**
     * Returns the installer instance for this bundle.
     *
     * The installer handles database table creation and migration
     * when the bundle is installed or updated.
     *
     * @return InstallerInterface The installer instance
     */
    public function getInstaller(): InstallerInterface
    {
        return $this->container->get(ExtendedBlockInstaller::class);
    }

    /**
     * Returns the human-readable description of this bundle.
     *
     * @return string The bundle description
     */
    public function getDescription(): string
    {
        return 'Extended Block data type with separate table storage for Pimcore. '
            .'Provides better performance and queryability compared to standard block type.';
    }

    /**
     * Returns the bundle version.
     *
     * @return string The version string in semver format
     */
    public function getVersion(): string
    {
        return '1.0.0';
    }

    /**
     * Returns the paths to JavaScript files for the admin interface.
     *
     * These scripts provide the admin UI for configuring and editing
     * extended block fields in the Pimcore backend.
     *
     * @return array<string> Array of JavaScript file paths
     */
    public function getJsPaths(): array
    {
        return [
            '/bundles/extendedblock/js/pimcore/startup.js',
            '/bundles/extendedblock/js/pimcore/object/tags/extendedBlock.js',
            '/bundles/extendedblock/js/pimcore/object/classes/data/extendedBlock.js',
        ];
    }

    /**
     * Returns the paths to CSS files for the admin interface.
     *
     * @return array<string> Array of CSS file paths
     */
    public function getCssPaths(): array
    {
        return [
            '/bundles/extendedblock/css/admin.css',
        ];
    }

    /**
     * Returns the path to the bundle's routes configuration.
     *
     * @return string The routes file path
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
