/**
 * Extended Block Bundle - Startup Script
 *
 * Initializes the Extended Block data type in Pimcore admin.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

/**
 * Register the extended block data type when Pimcore is ready.
 */
pimcore.registerNS('pimcore.plugin.extendedBlock');

pimcore.plugin.extendedBlock = Class.create({
    /**
     * Initializes the plugin.
     * Called when Pimcore admin is ready.
     */
    initialize: function() {
        // Log initialization
        if (pimcore.settings.devmode) {
            console.log('Extended Block Bundle initialized');
        }
    },

    /**
     * Returns the plugin name.
     * @returns {string} Plugin name
     */
    getClassName: function() {
        return 'pimcore.plugin.extendedBlock';
    }
});

// Initialize the plugin when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Create plugin instance
    var extendedBlockPlugin = new pimcore.plugin.extendedBlock();
});
