<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Webpack Entry Point Provider
 *
 * This class provides Pimcore Studio UI with the location of the
 * bundled JavaScript assets for the ExtendedBlock plugin.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Webpack;

use Pimcore\Bundle\StudioUiBundle\Webpack\WebpackEntryPointProviderInterface;

/**
 * Provides webpack entry points for Pimcore Studio UI.
 *
 * This service is tagged with 'pimcore_studio_ui.webpack_entry_point_provider'
 * to register the ExtendedBlock plugin assets with Pimcore Studio UI.
 *
 * @internal
 */
final class WebpackEntryPointProvider implements WebpackEntryPointProviderInterface
{
    /**
     * Returns the paths to entrypoints.json files.
     *
     * The build process creates a unique build ID directory
     * to enable cache busting.
     *
     * @return array<string>
     */
    public function getEntryPointsJsonLocations(): array
    {
        return glob(__DIR__ . '/../../public/studio-ui-build/*/entrypoints.json') ?: [];
    }

    /**
     * Returns the entry points that should be loaded.
     *
     * @return array<string>
     */
    public function getEntryPoints(): array
    {
        return ['exposeRemote'];
    }

    /**
     * Returns optional entry points (loaded only if available).
     *
     * @return array<string>
     */
    public function getOptionalEntryPoints(): array
    {
        return [];
    }
}
