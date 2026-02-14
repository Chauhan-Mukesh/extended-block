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
 * @extends pimcore.object.classes.data.data
 */
pimcore.object.classes.data.extendedBlock = Class.create(pimcore.object.classes.data.data, {

    /**
     * Data type identifier
     * @type {string}
     */
    type: 'extendedBlock',

    /**
     * Whether this type can be used in various contexts
     * @type {Object}
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true,
        classificationstore: false,
        block: false  // ExtendedBlock cannot be nested in Block
    },

    /**
     * Disallowed field types inside ExtendedBlock
     * @type {Array}
     */
    disallowedDataTypes: [
        'localizedfields',  // Localized fields not supported
        'block',            // No nested blocks
        'fieldcollections', // No field collections
        'objectbricks',     // No object bricks
        'extendedBlock'     // No nested extended blocks
    ],

    /**
     * Initializes the data type definition.
     *
     * @param {Object} treeNode - The tree node in class editor
     * @param {Object} initData - Initial configuration data
     */
    initialize: function(treeNode, initData) {
        this.type = 'extendedBlock';
        this.initData(initData);
        this.treeNode = treeNode;
    },

    /**
     * Returns the type name for display.
     *
     * @returns {string} The type name
     */
    getTypeName: function() {
        return t('extended_block') || 'Extended Block';
    },

    /**
     * Returns the icon class for this data type.
     *
     * @returns {string} The icon class
     */
    getIconClass: function() {
        return 'pimcore_icon_block';
    },

    /**
     * Returns the configuration panel layout.
     *
     * Creates the settings interface including:
     * - General settings (title, name)
     * - Item limits (min/max)
     * - Display settings
     *
     * @returns {Ext.form.Panel} The settings panel
     */
    getLayout: function() {
        var _this = this;

        // Settings panel
        this.settingsPanel = new Ext.form.Panel({
            layout: 'form',
            bodyStyle: 'padding: 10px;',
            autoScroll: true,
            defaults: {
                labelWidth: 180
            },
            items: [
                // Name field
                {
                    xtype: 'textfield',
                    fieldLabel: t('name'),
                    name: 'name',
                    itemId: 'name',
                    value: this.datax.name,
                    width: 540,
                    maxLength: 70,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function(field) {
                            // Sanitize name
                            field.setValue(field.getValue().replace(/[^a-zA-Z0-9_]/g, ''));

                            // Auto-fill title field if untouched
                            var title = field.ownerCt.getComponent('title');
                            if (title && title._autooverwrite === true) {
                                var nameValue = field.getValue();
                                var fixedTitle = '';
                                for (var i = 0; i < nameValue.length; i++) {
                                    var currentChar = nameValue[i];
                                    var isDigit = /[0-9]/.test(currentChar);
                                    fixedTitle += i === 0
                                        ? currentChar.toUpperCase()
                                        : (currentChar === currentChar.toUpperCase() && !isDigit)
                                            ? ' ' + currentChar
                                            : currentChar;
                                }
                                title.setValue(fixedTitle);
                            }

                            // Update tree node text in real-time
                            _this.updateTreeNodeName(field.getValue());
                        }
                    }
                },
                // Title field
                {
                    xtype: 'textfield',
                    fieldLabel: t('title') + ' (' + t('label') + ')',
                    name: 'title',
                    itemId: 'title',
                    value: this.datax.title,
                    width: 540,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function(field) {
                            field._autooverwrite = false;
                        },
                        afterrender: function(field) {
                            if (!field.getValue() || field.getValue().length < 1) {
                                field._autooverwrite = true;
                            }
                        }
                    }
                },
                // Tooltip field
                {
                    xtype: 'textarea',
                    fieldLabel: t('tooltip'),
                    name: 'tooltip',
                    value: this.datax.tooltip || '',
                    width: 540,
                    height: 80
                },
                // Min items
                {
                    xtype: 'numberfield',
                    fieldLabel: t('minimum_items') || 'Minimum Items',
                    name: 'minItems',
                    value: this.datax.minItems || 0,
                    minValue: 0,
                    width: 300
                },
                // Max items
                {
                    xtype: 'numberfield',
                    fieldLabel: t('maximum_items') || 'Maximum Items',
                    name: 'maxItems',
                    value: this.datax.maxItems,
                    minValue: 0,
                    width: 300
                },
                // Collapsible
                {
                    xtype: 'checkbox',
                    fieldLabel: t('collapsible'),
                    name: 'collapsible',
                    checked: this.datax.collapsible !== false
                },
                // Collapsed by default
                {
                    xtype: 'checkbox',
                    fieldLabel: t('collapsed') || 'Collapsed by Default',
                    name: 'collapsed',
                    checked: this.datax.collapsed === true
                },
                // Lazy loading
                {
                    xtype: 'checkbox',
                    fieldLabel: t('lazy_loading') || 'Lazy Loading',
                    name: 'lazyLoading',
                    checked: this.datax.lazyLoading !== false
                },
                // Info panel about adding fields
                {
                    xtype: 'panel',
                    style: 'margin-top: 20px; padding: 10px; background-color: #f5f5f5; border: 1px solid #ddd; border-radius: 4px;',
                    html: '<div style="color: #555; font-size: 12px;">' +
                        '<strong>' + (t('add_fields') || 'Add Fields') + ':</strong><br>' +
                        (t('extended_block_fields_help') || 'Right-click on this field in the tree to add sub-fields. ' +
                        'Supported field types: Input, Textarea, WYSIWYG, Numeric, Checkbox, Date, Select, Multiselect, Link, Image. ' +
                        'Note: LocalizedFields, Block, FieldCollections, ObjectBricks, and ExtendedBlock are not allowed as sub-fields.') +
                        '</div>'
                }
            ]
        });

        return this.settingsPanel;
    },

    /**
     * Updates the tree node text when the name field changes.
     *
     * @param {string} name - The new field name
     */
    updateTreeNodeName: function(name) {
        if (this.treeNode && name && this.isValidName(name)) {
            this.treeNode.set('text', name);
        }
    },

    /**
     * Validates field name format.
     *
     * @param {string} name - The name to validate
     * @returns {boolean} True if valid
     */
    isValidName: function(name) {
        var validNamePattern = /^[a-zA-Z][a-zA-Z0-9_]*$/;
        return validNamePattern.test(name);
    },

    /**
     * Gets the data for saving.
     *
     * @returns {Object} The configuration data
     */
    getData: function() {
        var values = this.settingsPanel.getForm().getFieldValues();

        return {
            name: values.name,
            title: values.title,
            tooltip: values.tooltip,
            minItems: values.minItems,
            maxItems: values.maxItems,
            collapsible: values.collapsible,
            collapsed: values.collapsed,
            lazyLoading: values.lazyLoading
        };
    },

    /**
     * Validates the configuration.
     *
     * @returns {boolean} True if valid
     */
    isValid: function() {
        var data = this.getData();

        if (!data.name || data.name.length === 0) {
            return false;
        }

        return true;
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
     */
    onNodeAppend: function(child) {
        if (!this.isAllowedDataType(child.type)) {
            Ext.MessageBox.alert(
                t('error'),
                t('type_not_allowed_in_extended_block') || 
                'This field type is not allowed inside Extended Block: ' + child.type
            );
            return false;
        }
        return true;
    }
});
