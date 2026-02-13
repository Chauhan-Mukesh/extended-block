<?php

declare(strict_types=1);

/**
 * Extended Block Bundle - Installer.
 *
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

namespace ExtendedBlockBundle\Installer;

use Doctrine\DBAL\Connection;
use Pimcore\Extension\Bundle\Installer\AbstractInstaller;

/**
 * Installer for Extended Block Bundle.
 *
 * Handles the installation, update, and uninstallation of the bundle:
 * - Creates necessary database tables
 * - Registers the extended block data type
 * - Handles migrations when updating
 * - Cleans up resources when uninstalling
 *
 * @see https://pimcore.com/docs/platform/Bundles/Bundle_Installation.html
 */
class ExtendedBlockInstaller extends AbstractInstaller
{
    /**
     * Database connection.
     */
    protected Connection $db;

    /**
     * Path to the bundle.
     */
    protected string $bundlePath;

    /**
     * Table name for storing bundle metadata.
     */
    private const METADATA_TABLE = 'extended_block_metadata';

    /**
     * Current bundle version.
     */
    private const VERSION = '1.0.0';

    /**
     * Creates a new installer instance.
     *
     * @param Connection $db The database connection
     */
    public function __construct(Connection $db)
    {
        parent::__construct();
        $this->db = $db;
        $this->bundlePath = dirname(__DIR__, 2);
    }

    /**
     * Installs the bundle.
     *
     * This method is called when the bundle is first installed:
     * - Creates the metadata table to track installation status
     * - Publishes bundle assets
     * - Performs any initial setup tasks
     */
    public function install(): void
    {
        $this->output->writeln('Installing Extended Block Bundle...');

        try {
            // Create metadata table
            $this->createMetadataTable();

            // Use quoteIdentifier for consistent security practice
            $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

            // Record installation
            $this->db->executeStatement(
                "INSERT INTO {$quotedTable} (key_name, value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE value = VALUES(value)",
                ['version', self::VERSION]
            );

            $this->db->executeStatement(
                "INSERT INTO {$quotedTable} (key_name, value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE value = VALUES(value)",
                ['installed_at', date('Y-m-d H:i:s')]
            );

            $this->output->writeln('Extended Block Bundle installed successfully!');
        } catch (\Exception $e) {
            $this->output->writeln('Error installing bundle: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Updates the bundle.
     *
     * Called when updating to a new version:
     * - Runs any necessary migrations
     * - Updates bundle metadata
     */
    public function update(): void
    {
        $this->output->writeln('Updating Extended Block Bundle...');

        try {
            // Get current installed version
            $currentVersion = $this->getInstalledVersion();

            // Run migrations based on version
            $this->runMigrations($currentVersion, self::VERSION);

            // Use quoteIdentifier for consistent security practice
            $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

            // Update version in metadata
            $this->db->executeStatement(
                "UPDATE {$quotedTable} SET value = ? WHERE key_name = ?",
                [self::VERSION, 'version']
            );

            $this->db->executeStatement(
                "INSERT INTO {$quotedTable} (key_name, value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE value = VALUES(value)",
                ['updated_at', date('Y-m-d H:i:s')]
            );

            $this->output->writeln('Extended Block Bundle updated successfully!');
        } catch (\Exception $e) {
            $this->output->writeln('Error updating bundle: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Uninstalls the bundle.
     *
     * Called when the bundle is removed:
     * - WARNING: Does NOT delete extended block data tables by default
     * - Removes metadata table
     * - Cleans up bundle assets
     *
     * Data tables are preserved to prevent accidental data loss.
     * Use the console command to manually remove data tables if needed.
     */
    public function uninstall(): void
    {
        $this->output->writeln('Uninstalling Extended Block Bundle...');
        $this->output->writeln('Note: Extended block data tables are preserved. Use console command to remove them manually.');

        try {
            // Use quoteIdentifier for consistent security practice
            $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

            // Remove metadata table
            $this->db->executeStatement("DROP TABLE IF EXISTS {$quotedTable}");

            $this->output->writeln('Extended Block Bundle uninstalled successfully!');
        } catch (\Exception $e) {
            $this->output->writeln('Error uninstalling bundle: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Checks if the bundle is installed.
     *
     * @return bool True if installed
     */
    public function isInstalled(): bool
    {
        try {
            // Check if metadata table exists (parameterized query for table name)
            $tableExists = $this->db->fetchOne(
                'SELECT COUNT(*) FROM information_schema.tables 
                 WHERE table_schema = DATABASE() AND table_name = ?',
                [self::METADATA_TABLE]
            );

            // Explicitly check for table existence - fetchOne returns string '0' or '1' for COUNT
            // or false on failure. Cast to int for reliable comparison.
            if (0 === (int) $tableExists) {
                return false;
            }

            // Use quoteIdentifier for consistent security practice
            $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

            // Check for version entry
            $version = $this->db->fetchOne(
                "SELECT value FROM {$quotedTable} WHERE key_name = ?",
                ['version']
            );

            // Check if version exists and is not empty
            // fetchOne returns false when no row found, or the value when found
            return !\in_array($version, [false, null, ''], true);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Checks if updates are available.
     *
     * @return bool True if updates are needed
     */
    public function needsUpdate(): bool
    {
        if (!$this->isInstalled()) {
            return false;
        }

        $installedVersion = $this->getInstalledVersion();

        return version_compare($installedVersion, self::VERSION, '<');
    }

    /**
     * Checks if the bundle can be installed.
     *
     * @return bool True if installation is possible
     */
    public function canBeInstalled(): bool
    {
        return !$this->isInstalled();
    }

    /**
     * Checks if the bundle can be uninstalled.
     *
     * @return bool True if uninstallation is possible
     */
    public function canBeUninstalled(): bool
    {
        return $this->isInstalled();
    }

    /**
     * Checks if the bundle can be updated.
     *
     * @return bool True if update is possible
     */
    public function canBeUpdated(): bool
    {
        return $this->needsUpdate();
    }

    /**
     * Gets the currently installed version.
     *
     * @return string The installed version
     */
    protected function getInstalledVersion(): string
    {
        try {
            // Use quoteIdentifier for consistent security practice
            $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

            $version = $this->db->fetchOne(
                "SELECT value FROM {$quotedTable} WHERE key_name = ?",
                ['version']
            );

            return $version ?: '0.0.0';
        } catch (\Exception $e) {
            return '0.0.0';
        }
    }

    /**
     * Creates the metadata table for tracking installation status.
     */
    protected function createMetadataTable(): void
    {
        // Use quoteIdentifier for consistent security practice
        $quotedTable = $this->db->quoteIdentifier(self::METADATA_TABLE);

        $sql = "CREATE TABLE IF NOT EXISTS {$quotedTable} (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `key_name` VARCHAR(100) NOT NULL,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `key_name` (`key_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        $this->db->executeStatement($sql);
    }

    /**
     * Runs migrations between two versions.
     *
     * @param string $fromVersion The current version
     * @param string $toVersion   The target version
     */
    protected function runMigrations(string $fromVersion, string $toVersion): void
    {
        $migrations = $this->getMigrations();

        foreach ($migrations as $migrationVersion => $migration) {
            if (version_compare($migrationVersion, $fromVersion, '>')
                && version_compare($migrationVersion, $toVersion, '<=')) {
                $this->output->writeln("Running migration for version {$migrationVersion}...");
                $migration();
            }
        }
    }

    /**
     * Returns available migrations.
     *
     * Each migration is a callable that performs the upgrade.
     *
     * @return array<string, callable> Array of migrations keyed by version
     */
    protected function getMigrations(): array
    {
        return [
            // Example migration for version 1.1.0
            // '1.1.0' => function() {
            //     $this->db->executeStatement("ALTER TABLE ...");
            // },
        ];
    }

    /**
     * Determines if admin interface should be reloaded after installation/uninstallation.
     *
     * @return bool True if admin should reload
     */
    public function needsReloadAfterInstall(): bool
    {
        return true;
    }
}
