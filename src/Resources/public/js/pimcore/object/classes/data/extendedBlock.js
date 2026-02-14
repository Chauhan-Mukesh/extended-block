/**
 * Extended Block Bundle - Class Definition Data Type
 *
 * Defines the Extended Block data type configuration in Pimcore class editor.
 * This handles the field configuration interface when defining object classes.
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
 * - Defining block types with their fields
 * - Setting min/max item limits
 * - Configuring localized field support
 * - Setting display options (collapsible, lazy loading)
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
     * Whether this type can be used in localized fields
     * @type {boolean}
     */
    allowIn: {
        object: true,
        objectbrick: true,
        fieldcollection: true,
        localizedfield: true, // Conditionally allowed (validated at save time)
        classificationstore: false,
        block: false
    },

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
        return t('extended_block');
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
     * - General settings (title, name, mandatory)
     * - Block type definitions
     * - Item limits (min/max)
     * - Display settings
     *
     * @returns {Ext.form.Panel} The settings panel
     */
    getLayout: function() {
        var _this = this;

        // Block definitions grid
        var blockDefinitionsGrid = this.createBlockDefinitionsGrid();

        // Settings panel
        this.settingsPanel = new Ext.form.Panel({
            layout: 'form',
            bodyStyle: 'padding: 10px;',
            autoScroll: true,
            defaults: {
                labelWidth: 180
            },
            items: [
                // Name field (Issue #1 & #2: Auto-fill title and real-time tree update)
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

                            // Auto-fill title field if untouched (like Pimcore default)
                            var title = field.ownerCt.getComponent('title');
                            if (title && title._autooverwrite === true) {
                                var nameValue = field.getValue();
                                var fixedTitle = '';
                                for (var i = 0; i < nameValue.length; i++) {
                                    var currentChar = nameValue[i];
                                    // Capitalize first letter, add space before uppercase letters (excluding digits)
                                    var isDigit = /[0-9]/.test(currentChar);
                                    fixedTitle += i === 0
                                        ? currentChar.toUpperCase()
                                        : (currentChar === currentChar.toUpperCase() && !isDigit)
                                            ? ' ' + currentChar
                                            : currentChar;
                                }
                                title.setValue(fixedTitle);
                            }

                            // Update tree node text in real-time (Issue #2)
                            _this.updateTreeNodeName(field.getValue());
                        }
                    }
                },
                // Title field (Issue #1: Auto-fill support)
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
                            // Mark as manually edited
                            field._autooverwrite = false;
                        },
                        afterrender: function(field) {
                            // Enable auto-overwrite if title is empty
                            if (!field.getValue() || field.getValue().length < 1) {
                                field._autooverwrite = true;
                            }
                        }
                    }
                },
                // Tooltip field (Issue #3: Add tooltip option)
                {
                    xtype: 'textarea',
                    fieldLabel: t('tooltip'),
                    name: 'tooltip',
                    value: this.datax.tooltip || '',
                    width: 540,
                    height: 80
                },
                // Min items (Issue #4: Nice label name)
                {
                    xtype: 'numberfield',
                    fieldLabel: t('minimum_items') || 'Minimum Items',
                    name: 'minItems',
                    value: this.datax.minItems || 0,
                    minValue: 0,
                    width: 300
                },
                // Max items (Issue #4: Nice label name)
                {
                    xtype: 'numberfield',
                    fieldLabel: t('maximum_items') || 'Maximum Items',
                    name: 'maxItems',
                    value: this.datax.maxItems,
                    minValue: 0,
                    width: 300
                },
                // Allow localized fields (Issue #4: Nice label name)
                {
                    xtype: 'checkbox',
                    fieldLabel: t('allow_localized_fields') || 'Allow Localized Fields',
                    name: 'allowLocalizedFields',
                    checked: this.datax.allowLocalizedFields !== false
                },
                // Collapsible (Issue #4: Nice label name)
                {
                    xtype: 'checkbox',
                    fieldLabel: t('collapsible'),
                    name: 'collapsible',
                    checked: this.datax.collapsible !== false
                },
                // Collapsed by default (Issue #4: Nice label name)
                {
                    xtype: 'checkbox',
                    fieldLabel: t('collapsed_by_default') || 'Collapsed by Default',
                    name: 'collapsed',
                    checked: this.datax.collapsed === true
                },
                // Lazy loading (Issue #4: Nice label name)
                {
                    xtype: 'checkbox',
                    fieldLabel: t('lazy_loading') || 'Lazy Loading',
                    name: 'lazyLoading',
                    checked: this.datax.lazyLoading !== false
                },
                // Block definitions (Issue #5: Improved layout)
                {
                    xtype: 'fieldset',
                    title: t('block_definitions') || 'Block Definitions',
                    collapsible: true,
                    collapsed: false,
                    layout: 'fit',
                    style: 'margin-top: 20px;',
                    items: [blockDefinitionsGrid]
                }
            ]
        });

        return this.settingsPanel;
    },

    /**
     * Updates the tree node text when the name field changes (Issue #2).
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
     * Creates the block definitions grid.
     *
     * This grid allows users to define multiple block types,
     * each with its own set of fields.
     * (Issue #5: Improved responsive layout)
     *
     * @returns {Ext.grid.Panel} The block definitions grid
     */
    createBlockDefinitionsGrid: function() {
        var _this = this;

        // Create store for block definitions
        var blockDefinitions = this.datax.blockDefinitions || {};
        var storeData = [];

        for (var typeName in blockDefinitions) {
            if (blockDefinitions.hasOwnProperty(typeName)) {
                var def = blockDefinitions[typeName];
                storeData.push({
                    typeName: typeName,
                    displayName: def.name || typeName,
                    icon: def.icon || '',
                    fields: def.fields || []
                });
            }
        }

        // If no definitions, add a default one
        if (storeData.length === 0) {
            storeData.push({
                typeName: 'default',
                displayName: 'Default',
                icon: '',
                fields: []
            });
        }

        this.blockDefinitionsStore = new Ext.data.JsonStore({
            fields: ['typeName', 'displayName', 'icon', 'fields'],
            data: storeData
        });

        // Create grid with improved responsive layout
        this.blockDefinitionsGrid = new Ext.grid.Panel({
            store: this.blockDefinitionsStore,
            cls: 'extended-block-definitions-grid',
            columns: [
                {
                    text: t('type_name') || 'Type Name',
                    dataIndex: 'typeName',
                    flex: 1,
                    minWidth: 100,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    text: t('display_name') || 'Display Name',
                    dataIndex: 'displayName',
                    flex: 1,
                    minWidth: 100,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    text: t('icon'),
                    dataIndex: 'icon',
                    flex: 0.5,
                    minWidth: 80,
                    editor: {
                        xtype: 'textfield'
                    }
                },
                {
                    text: t('fields'),
                    dataIndex: 'fields',
                    width: 60,
                    align: 'center',
                    renderer: function(value) {
                        return '<span class="extended-block-field-count">' + ((value && value.length) || 0) + '</span>';
                    }
                },
                {
                    xtype: 'actioncolumn',
                    text: t('actions') || 'Actions',
                    width: 80,
                    items: [
                        {
                            iconCls: 'pimcore_icon_edit',
                            tooltip: t('edit_fields') || 'Edit Fields',
                            handler: function(grid, rowIndex) {
                                _this.editBlockType(rowIndex);
                            }
                        },
                        {
                            iconCls: 'pimcore_icon_delete',
                            tooltip: t('delete'),
                            handler: function(grid, rowIndex) {
                                _this.blockDefinitionsStore.removeAt(rowIndex);
                            }
                        }
                    ]
                }
            ],
            selModel: 'cellmodel',
            plugins: {
                ptype: 'cellediting',
                clicksToEdit: 2
            },
            tbar: [
                {
                    text: t('add_block_type') || 'Add Block Type',
                    iconCls: 'pimcore_icon_add',
                    handler: function() {
                        _this.addBlockType();
                    }
                }
            ],
            height: 250,
            minHeight: 150,
            width: '100%'
        });

        return this.blockDefinitionsGrid;
    },

    /**
     * Adds a new block type.
     */
    addBlockType: function() {
        var count = this.blockDefinitionsStore.getCount() + 1;
        this.blockDefinitionsStore.add({
            typeName: 'type_' + count,
            displayName: 'Type ' + count,
            icon: '',
            fields: []
        });
    },

    /**
     * Opens the field editor for a block type.
     *
     * @param {number} rowIndex - The row index in the grid
     */
    editBlockType: function(rowIndex) {
        var record = this.blockDefinitionsStore.getAt(rowIndex);
        var _this = this;

        // Create fields editor window
        var fieldsEditor = new pimcore.object.classes.data.extendedBlock.fieldsEditor(
            record.get('typeName'),
            record.get('fields') || [],
            function(updatedFields) {
                record.set('fields', updatedFields);
            }
        );
    },

    /**
     * Gets the data for saving.
     *
     * @returns {Object} The configuration data
     */
    getData: function() {
        var values = this.settingsPanel.getForm().getFieldValues();

        // Build block definitions from store
        var blockDefinitions = {};
        this.blockDefinitionsStore.each(function(record) {
            blockDefinitions[record.get('typeName')] = {
                name: record.get('displayName'),
                icon: record.get('icon'),
                fields: record.get('fields') || []
            };
        });

        return {
            name: values.name,
            title: values.title,
            tooltip: values.tooltip,  // Issue #3: Include tooltip in saved data
            minItems: values.minItems,
            maxItems: values.maxItems,
            allowLocalizedFields: values.allowLocalizedFields,
            collapsible: values.collapsible,
            collapsed: values.collapsed,
            lazyLoading: values.lazyLoading,
            blockDefinitions: blockDefinitions
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
    }
});

/**
 * Fields editor window for block type configuration.
 * (Issue #6: Complete rewrite for proper functionality)
 *
 * Provides a grid-based interface for defining fields within a block type.
 */
pimcore.object.classes.data.extendedBlock.fieldsEditor = Class.create({

    /**
     * Initializes the fields editor.
     *
     * @param {string} typeName - The block type name
     * @param {Array} fields - Existing field definitions
     * @param {Function} callback - Callback when fields are saved
     */
    initialize: function(typeName, fields, callback) {
        this.typeName = typeName;
        this.fields = Ext.clone(fields || []);
        this.callback = callback;

        this.showWindow();
    },

    /**
     * Shows the fields editor window.
     */
    showWindow: function() {
        var _this = this;

        // Create available field types combo data
        this.fieldTypes = [
            { value: 'input', text: t('input') || 'Input' },
            { value: 'textarea', text: t('textarea') || 'Textarea' },
            { value: 'wysiwyg', text: t('wysiwyg') || 'WYSIWYG' },
            { value: 'numeric', text: t('numeric') || 'Numeric' },
            { value: 'checkbox', text: t('checkbox') || 'Checkbox' },
            { value: 'date', text: t('date') || 'Date' },
            { value: 'select', text: t('select') || 'Select' },
            { value: 'multiselect', text: t('multiselect') || 'Multiselect' },
            { value: 'link', text: t('link') || 'Link' },
            { value: 'image', text: t('image') || 'Image' },
            { value: 'localizedfields', text: t('localized_fields') || 'Localized Fields' }
        ];

        // Create store for selected fields
        this.fieldsStore = new Ext.data.JsonStore({
            fields: ['fieldtype', 'name', 'title', 'tooltip'],
            data: this.fields
        });

        // Create fields grid
        this.fieldsGrid = new Ext.grid.Panel({
            store: this.fieldsStore,
            region: 'center',
            cls: 'extended-block-fields-grid',
            columns: [
                {
                    text: t('type') || 'Type',
                    dataIndex: 'fieldtype',
                    width: 140,
                    renderer: function(value) {
                        var fieldType = _this.fieldTypes.find(function(ft) {
                            return ft.value === value;
                        });
                        return fieldType ? fieldType.text : value;
                    },
                    editor: {
                        xtype: 'combobox',
                        store: new Ext.data.Store({
                            fields: ['value', 'text'],
                            data: this.fieldTypes
                        }),
                        displayField: 'text',
                        valueField: 'value',
                        editable: false,
                        forceSelection: true
                    }
                },
                {
                    text: t('name'),
                    dataIndex: 'name',
                    flex: 1,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    text: (t('title') || 'Title') + ' (' + (t('label') || 'Label') + ')',
                    dataIndex: 'title',
                    flex: 1,
                    editor: {
                        xtype: 'textfield'
                    }
                },
                {
                    text: t('tooltip') || 'Tooltip',
                    dataIndex: 'tooltip',
                    flex: 1,
                    editor: {
                        xtype: 'textfield'
                    }
                },
                {
                    xtype: 'actioncolumn',
                    width: 80,
                    items: [
                        {
                            iconCls: 'pimcore_icon_up',
                            tooltip: t('move_up') || 'Move Up',
                            handler: function(grid, rowIndex) {
                                _this.moveField(rowIndex, -1);
                            }
                        },
                        {
                            iconCls: 'pimcore_icon_down',
                            tooltip: t('move_down') || 'Move Down',
                            handler: function(grid, rowIndex) {
                                _this.moveField(rowIndex, 1);
                            }
                        },
                        {
                            iconCls: 'pimcore_icon_delete',
                            tooltip: t('delete'),
                            handler: function(grid, rowIndex) {
                                _this.fieldsStore.removeAt(rowIndex);
                            }
                        }
                    ]
                }
            ],
            selModel: 'cellmodel',
            plugins: {
                ptype: 'cellediting',
                clicksToEdit: 1
            },
            tbar: [
                {
                    text: t('add_field') || 'Add Field',
                    iconCls: 'pimcore_icon_add',
                    menu: {
                        items: this.fieldTypes.map(function(fieldType) {
                            return {
                                text: fieldType.text,
                                iconCls: 'pimcore_icon_' + fieldType.value,
                                handler: function() {
                                    _this.addField(fieldType.value);
                                }
                            };
                        })
                    }
                }
            ]
        });

        // Create window
        this.window = new Ext.Window({
            title: (t('edit_fields') || 'Edit Fields') + ': ' + this.typeName,
            width: 900,
            height: 500,
            modal: true,
            layout: 'border',
            items: [
                {
                    region: 'north',
                    xtype: 'panel',
                    height: 40,
                    bodyStyle: 'padding: 10px;',
                    html: '<div class="extended-block-info">' +
                        (t('fields_editor_help') || 'Click cells to edit. Use the + menu to add fields. Use arrow buttons to reorder.') +
                        '</div>'
                },
                this.fieldsGrid
            ],
            buttons: [
                {
                    text: t('save'),
                    iconCls: 'pimcore_icon_save',
                    handler: function() {
                        _this.save();
                    }
                },
                {
                    text: t('cancel'),
                    iconCls: 'pimcore_icon_cancel',
                    handler: function() {
                        _this.window.close();
                    }
                }
            ]
        });

        this.window.show();
    },

    /**
     * Adds a new field to the grid.
     *
     * @param {string} fieldtype - The field type
     */
    addField: function(fieldtype) {
        var count = this.fieldsStore.getCount() + 1;
        var fieldName = fieldtype + '_' + count;

        // Generate a nice title from the name
        var fieldTitle = fieldtype.charAt(0).toUpperCase() + fieldtype.slice(1) + ' ' + count;

        this.fieldsStore.add({
            fieldtype: fieldtype,
            name: fieldName,
            title: fieldTitle,
            tooltip: ''
        });
    },

    /**
     * Moves a field up or down in the list.
     *
     * @param {number} rowIndex - The row index
     * @param {number} direction - Direction (-1 for up, 1 for down)
     */
    moveField: function(rowIndex, direction) {
        var newIndex = rowIndex + direction;
        var store = this.fieldsStore;

        if (newIndex < 0 || newIndex >= store.getCount()) {
            return;
        }

        var record = store.getAt(rowIndex);
        store.removeAt(rowIndex);
        store.insert(newIndex, record);
    },

    /**
     * Saves the field configuration.
     */
    save: function() {
        var fields = [];

        this.fieldsStore.each(function(record) {
            var data = record.getData();
            // Only include fields with valid names
            if (data.name && data.name.length > 0) {
                fields.push({
                    fieldtype: data.fieldtype,
                    name: data.name,
                    title: data.title || '',
                    tooltip: data.tooltip || ''
                });
            }
        });

        this.callback(fields);
        this.window.close();
    }
});
