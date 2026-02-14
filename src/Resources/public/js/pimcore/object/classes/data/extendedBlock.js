/**
 * Extended Block Bundle - Class Definition Data Type
 *
 * Defines the Extended Block data type configuration in Pimcore class editor.
 * This handles the field configuration interface when defining object classes.
 * 
 * Following Pimcore Block pattern: sub-fields are added via tree view context menu.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

pimcore.registerNS('pimcore.object.classes.data.extendedBlock');

/**
 * Extended Block data type for class definition editor.
 *
 * This class provides the configuration interface for:
 * - Setting min/max item limits
 * - Setting display options (collapsible, lazy loading)
 * - Adding sub-fields via tree view (like core Block)
 *
 * Follows Pimcore's data.js pattern: uses $super() to call parent's getLayout(),
 * adds specific settings to this.specificPanel, and relies on parent's getData()
 * which uses applyData() to sync form values.
 *
 * @extends pimcore.object.classes.data.data
 */
pimcore.object.classes.data.extendedBlock = Class.create(pimcore.object.classes.data.data, {

    /**
     * Data type identifier
     * @type {string}
     */
    type: 'extendedBlock',

    /**
     * Disable index for this type
     * @type {boolean}
     */
    allowIndex: false,

    /**
     * Whether this type can be used in various contexts.
     * ExtendedBlock stores data in separate tables (not serialized JSON),
     * so it can only be used at the root level of class definitions.
     * @type {Object}
     */
    allowIn: {
        object: true,
        objectbrick: false,      // Not allowed - ExtendedBlock can only be at root level
        fieldcollection: false,  // Not allowed - ExtendedBlock can only be at root level
        localizedfield: false,   // Not allowed - ExtendedBlock can only be at root level
        classificationstore: false,
        block: false,            // Not allowed - ExtendedBlock can only be at root level
        extendedBlock: false     // Prevent nesting ExtendedBlock inside itself
    },

    /**
     * Disallowed field types inside ExtendedBlock.
     * These types cannot be added as children because they either:
     * - Store data in complex structures incompatible with separate table storage
     * - Would create circular dependencies
     * @type {Array}
     */
    disallowedDataTypes: [
        'localizedfields',
        'block',
        'fieldcollections',
        'objectbricks',
        'extendedBlock',
        'classificationstore'
    ],

    /**
     * Initializes the data type definition.
     * Follows Pimcore pattern by calling initData and setting availableSettingsFields.
     *
     * @param {Object} treeNode - The tree node in class editor
     * @param {Object} initData - Initial configuration data
     */
    initialize: function(treeNode, initData) {
        this.type = 'extendedBlock';
        this.initData(initData);
        this.treeNode = treeNode;

        // Define which standard settings fields are available (following Pimcore block.js pattern)
        this.availableSettingsFields = ['name', 'title', 'noteditable', 'invisible', 'style'];
    },

    /**
     * Returns the type name for display.
     *
     * @returns {string} The type name
     */
    getTypeName: function() {
        return t('extended_block');
    },

    /**
     * Returns the icon class for this data type.
     * Uses a distinct icon different from the standard Block.
     *
     * @returns {string} The icon class
     */
    getIconClass: function() {
        return 'pimcore_icon_objectbricks';
    },

    /**
     * Returns the group this data type belongs to.
     * ExtendedBlock is in the "structured" group like Block, FieldCollections, etc.
     *
     * @returns {string} The group name
     */
    getGroup: function() {
        return 'structured';
    },

    /**
     * Returns the configuration panel layout.
     * Follows Pimcore pattern: calls $super() to build standard layout,
     * then adds specific settings to this.specificPanel.
     *
     * @param {Function} $super - Parent class method
     * @returns {Ext.Panel} The layout panel
     */
    getLayout: function($super) {
        // Call parent to create standard layout with standardSettingsForm, layoutSettingsForm, specificPanel
        $super();

        // Clear specific panel and add ExtendedBlock-specific settings
        this.specificPanel.removeAll();

        if (!this.isInCustomLayoutEditor()) {
            this.specificPanel.add([
                {
                    xtype: 'numberfield',
                    fieldLabel: t('minimum_items'),
                    name: 'minItems',
                    value: this.datax.minItems,
                    minValue: 0
                },
                {
                    xtype: 'numberfield',
                    fieldLabel: t('maximum_items'),
                    name: 'maxItems',
                    value: this.datax.maxItems,
                    minValue: 0
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: t('lazy_loading'),
                    name: 'lazyLoading',
                    disabled: this.isInCustomLayoutEditor(),
                    checked: this.datax.lazyLoading
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: t('disallow_addremove'),
                    name: 'disallowAddRemove',
                    checked: this.datax.disallowAddRemove
                },
                {
                    xtype: 'checkbox',
                    fieldLabel: t('disallow_reorder'),
                    name: 'disallowReorder',
                    checked: this.datax.disallowReorder
                }
            ]);
        }

        // Add CSS style field (following Pimcore block.js pattern)
        this.specificPanel.add({
            xtype: 'textfield',
            fieldLabel: t('css_style') + ' (float: left; margin:10px; ...)',
            name: 'styleElement',
            itemId: 'styleElement',
            value: this.datax.styleElement,
            width: 740
        });

        this.specificPanel.updateLayout();

        // Add collapsible settings to standard settings form (following Pimcore block.js pattern)
        this.standardSettingsForm.add([
            {
                xtype: 'checkbox',
                fieldLabel: t('collapsible'),
                name: 'collapsible',
                checked: this.datax.collapsible
            },
            {
                xtype: 'checkbox',
                fieldLabel: t('collapsed'),
                name: 'collapsed',
                checked: this.datax.collapsed
            }
        ]);

        this.standardSettingsForm.updateLayout();

        return this.layout;
    },

    /**
     * Copies specific data from source (used for copy/paste functionality).
     * Follows Pimcore block.js pattern.
     *
     * @param {Object} source - Source data object
     */
    applySpecialData: function(source) {
        if (source.datax) {
            if (!this.datax) {
                this.datax = {};
            }
            Ext.apply(this.datax, {
                minItems: source.datax.minItems,
                maxItems: source.datax.maxItems,
                disallowAddRemove: source.datax.disallowAddRemove,
                disallowReorder: source.datax.disallowReorder,
                collapsible: source.datax.collapsible,
                collapsed: source.datax.collapsed,
                lazyLoading: source.datax.lazyLoading,
                styleElement: source.datax.styleElement
            });
        }
    },

    /**
     * Checks if a data type is allowed inside ExtendedBlock.
     *
     * @param {string} type - The data type to check
     * @returns {boolean} True if allowed
     */
    isAllowedDataType: function(type) {
        return this.disallowedDataTypes.indexOf(type) === -1;
    },

    /**
     * Called when a node is appended as child.
     * Validates if the data type is allowed.
     *
     * @param {Object} child - The child node being appended
     * @returns {boolean} True if allowed
     */
    onNodeAppend: function(child) {
        if (!this.isAllowedDataType(child.type)) {
            Ext.MessageBox.alert(
                t('error'),
                t('type_not_allowed_in_extended_block') + ': ' + child.type
            );
            return false;
        }
        return true;
    }
});
