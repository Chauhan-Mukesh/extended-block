/**
 * Extended Block Bundle - Startup Script
 *
 * Initializes the Extended Block data type in Pimcore admin.
 * Registers translations and adds the data type to the class definition editor.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

/**
 * Register namespace for the plugin.
 */
pimcore.registerNS('pimcore.plugin.extendedBlock');

/**
 * Extended Block Bundle plugin class.
 */
pimcore.plugin.extendedBlock = Class.create({
    /**
     * Initializes the plugin.
     * Called when Pimcore admin is ready.
     */
    initialize: function() {
        // Add default English translations for ExtendedBlock
        this.registerTranslations();
        
        // Log initialization in dev mode
        if (pimcore.settings && pimcore.settings.devmode) {
            console.log('Extended Block Bundle initialized');
        }
    },

    /**
     * Registers default English translations for the bundle.
     */
    registerTranslations: function() {
        // Check if translation system is available
        if (typeof pimcore.system_i18n_en === 'undefined') {
            pimcore.system_i18n_en = {};
        }
        
        // Register translations
        var translations = {
            'extended_block': 'Extended Block',
            'extended_block_fields_help': 'Right-click on this field in the tree to add sub-fields. Supported types: Input, Textarea, WYSIWYG, Numeric, Checkbox, Date, Select, Multiselect, Link, Image. LocalizedFields, Block, FieldCollections, ObjectBricks, and ExtendedBlock are not allowed.',
            'add_fields': 'Add Fields',
            'type_not_allowed_in_extended_block': 'This field type is not allowed inside Extended Block',
            'minimum_items': 'Minimum Items',
            'maximum_items': 'Maximum Items',
            'collapsed_by_default': 'Collapsed by Default',
            'lazy_loading': 'Lazy Loading',
            'limit_reached': 'Maximum number of items reached'
        };
        
        // Add translations to the i18n object
        for (var key in translations) {
            if (translations.hasOwnProperty(key)) {
                if (!pimcore.system_i18n_en[key]) {
                    pimcore.system_i18n_en[key] = translations[key];
                }
            }
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

/**
 * Initialize the plugin when document is ready.
 */
document.addEventListener('DOMContentLoaded', function() {
    // Create plugin instance
    var extendedBlockPlugin = new pimcore.plugin.extendedBlock();
});
