<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - WebpackEntryPointProvider Unit Test.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Tests\Unit\Webpack;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for WebpackEntryPointProvider class.
 *
 * Tests the Pimcore Studio UI webpack entry point provider functionality.
 * These tests are skipped when Pimcore Studio UI Bundle is not installed.
 *
 * @covers \ExtendedBlockBundle\Webpack\WebpackEntryPointProvider
 */
class WebpackEntryPointProviderTest extends TestCase
{
    /**
     * @var object|null
     */
    private $provider;

    protected function setUp(): void
    {
        // Skip all tests if Studio UI Bundle is not installed
        if (!interface_exists('Pimcore\Bundle\StudioUiBundle\Webpack\WebpackEntryPointProviderInterface')) {
            $this->markTestSkipped('Pimcore Studio UI Bundle is not installed.');
        }

        // Use late static binding to avoid loading the class if interface doesn't exist
        $className = 'ExtendedBlockBundle\Webpack\WebpackEntryPointProvider';
        $this->provider = new $className();
    }

    /**
     * Tests that getEntryPointsJsonLocations returns an array.
     */
    public function testGetEntryPointsJsonLocationsReturnsArray(): void
    {
        $result = $this->provider->getEntryPointsJsonLocations();

        $this->assertIsArray($result);
    }

    /**
     * Tests that getEntryPointsJsonLocations searches for correct pattern.
     */
    public function testGetEntryPointsJsonLocationsSearchesCorrectPath(): void
    {
        // Without actual build files, the result should be an empty array
        // This test verifies the method returns an array without throwing errors
        $result = $this->provider->getEntryPointsJsonLocations();

        $this->assertIsArray($result);

        // If there are results, they should be strings
        foreach ($result as $path) {
            $this->assertIsString($path);
            $this->assertStringEndsWith('entrypoints.json', $path);
        }
    }

    /**
     * Tests that getEntryPoints returns the expected entry points.
     */
    public function testGetEntryPointsReturnsExposeRemote(): void
    {
        $result = $this->provider->getEntryPoints();

        $this->assertIsArray($result);
        $this->assertContains('exposeRemote', $result);
        $this->assertCount(1, $result);
    }

    /**
     * Tests that getOptionalEntryPoints returns an empty array.
     */
    public function testGetOptionalEntryPointsReturnsEmptyArray(): void
    {
        $result = $this->provider->getOptionalEntryPoints();

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Tests that the provider implements the correct interface.
     */
    public function testImplementsWebpackEntryPointProviderInterface(): void
    {
        $this->assertInstanceOf(
            'Pimcore\Bundle\StudioUiBundle\Webpack\WebpackEntryPointProviderInterface',
            $this->provider
        );
    }
}
