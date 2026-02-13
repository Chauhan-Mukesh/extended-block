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
            items: [
                // Title field
                {
                    xtype: 'textfield',
                    fieldLabel: t('title'),
                    name: 'title',
                    value: this.datax.title,
                    width: 400
                },
                // Name field
                {
                    xtype: 'textfield',
                    fieldLabel: t('name'),
                    name: 'name',
                    value: this.datax.name,
                    width: 400,
                    enableKeyEvents: true,
                    listeners: {
                        keyup: function(field) {
                            field.setValue(field.getValue().replace(/[^a-zA-Z0-9_]/g, ''));
                        }
                    }
                },
                // Min items
                {
                    xtype: 'numberfield',
                    fieldLabel: t('min_items'),
                    name: 'minItems',
                    value: this.datax.minItems || 0,
                    minValue: 0,
                    width: 200
                },
                // Max items
                {
                    xtype: 'numberfield',
                    fieldLabel: t('max_items'),
                    name: 'maxItems',
                    value: this.datax.maxItems,
                    minValue: 0,
                    width: 200
                },
                // Allow localized fields
                {
                    xtype: 'checkbox',
                    fieldLabel: t('allow_localized_fields'),
                    name: 'allowLocalizedFields',
                    checked: this.datax.allowLocalizedFields !== false
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
                    fieldLabel: t('collapsed'),
                    name: 'collapsed',
                    checked: this.datax.collapsed === true
                },
                // Lazy loading
                {
                    xtype: 'checkbox',
                    fieldLabel: t('lazy_loading'),
                    name: 'lazyLoading',
                    checked: this.datax.lazyLoading !== false
                },
                // Block definitions
                {
                    xtype: 'fieldset',
                    title: t('block_definitions'),
                    collapsible: false,
                    style: 'margin-top: 20px;',
                    items: [blockDefinitionsGrid]
                }
            ]
        });

        return this.settingsPanel;
    },

    /**
     * Creates the block definitions grid.
     *
     * This grid allows users to define multiple block types,
     * each with its own set of fields.
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

        // Create grid
        this.blockDefinitionsGrid = new Ext.grid.Panel({
            store: this.blockDefinitionsStore,
            columns: [
                {
                    text: t('type_name'),
                    dataIndex: 'typeName',
                    flex: 1,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    text: t('display_name'),
                    dataIndex: 'displayName',
                    flex: 1,
                    editor: {
                        xtype: 'textfield',
                        allowBlank: false
                    }
                },
                {
                    text: t('icon'),
                    dataIndex: 'icon',
                    width: 150,
                    editor: {
                        xtype: 'textfield'
                    }
                },
                {
                    text: t('fields'),
                    dataIndex: 'fields',
                    width: 80,
                    renderer: function(value) {
                        return (value && value.length) || 0;
                    }
                },
                {
                    xtype: 'actioncolumn',
                    width: 80,
                    items: [
                        {
                            iconCls: 'pimcore_icon_edit',
                            tooltip: t('edit_fields'),
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
                    text: t('add_block_type'),
                    iconCls: 'pimcore_icon_add',
                    handler: function() {
                        _this.addBlockType();
                    }
                }
            ],
            height: 200,
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
 *
 * Provides a drag-and-drop interface for defining fields within a block type.
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
        this.fields = fields || [];
        this.callback = callback;

        this.showWindow();
    },

    /**
     * Shows the fields editor window.
     */
    showWindow: function() {
        var _this = this;

        // Create available fields tree
        var availableFieldsTree = this.createAvailableFieldsTree();

        // Create selected fields tree
        var selectedFieldsTree = this.createSelectedFieldsTree();

        // Create window
        this.window = new Ext.Window({
            title: t('edit_fields') + ': ' + this.typeName,
            width: 800,
            height: 500,
            modal: true,
            layout: 'border',
            items: [
                {
                    region: 'west',
                    title: t('available_fields'),
                    width: 250,
                    split: true,
                    items: [availableFieldsTree]
                },
                {
                    region: 'center',
                    title: t('selected_fields'),
                    layout: 'fit',
                    items: [selectedFieldsTree]
                }
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
     * Creates the available fields tree.
     *
     * @returns {Ext.tree.Panel} The available fields tree
     */
    createAvailableFieldsTree: function() {
        var children = [
            { text: t('input'), leaf: true, iconCls: 'pimcore_icon_input', fieldtype: 'input' },
            { text: t('textarea'), leaf: true, iconCls: 'pimcore_icon_textarea', fieldtype: 'textarea' },
            { text: t('wysiwyg'), leaf: true, iconCls: 'pimcore_icon_wysiwyg', fieldtype: 'wysiwyg' },
            { text: t('numeric'), leaf: true, iconCls: 'pimcore_icon_numeric', fieldtype: 'numeric' },
            { text: t('checkbox'), leaf: true, iconCls: 'pimcore_icon_checkbox', fieldtype: 'checkbox' },
            { text: t('date'), leaf: true, iconCls: 'pimcore_icon_date', fieldtype: 'date' },
            { text: t('select'), leaf: true, iconCls: 'pimcore_icon_select', fieldtype: 'select' },
            { text: t('multiselect'), leaf: true, iconCls: 'pimcore_icon_multiselect', fieldtype: 'multiselect' },
            { text: t('link'), leaf: true, iconCls: 'pimcore_icon_link', fieldtype: 'link' },
            { text: t('image'), leaf: true, iconCls: 'pimcore_icon_image', fieldtype: 'image' },
            { text: t('localized_fields'), leaf: true, iconCls: 'pimcore_icon_localizedfields', fieldtype: 'localizedfields' }
        ];

        return new Ext.tree.Panel({
            store: new Ext.data.TreeStore({
                root: {
                    expanded: true,
                    children: children
                }
            }),
            rootVisible: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    enableDrag: true,
                    enableDrop: false,
                    ddGroup: 'extendedBlockFields'
                }
            }
        });
    },

    /**
     * Creates the selected fields tree.
     *
     * @returns {Ext.tree.Panel} The selected fields tree
     */
    createSelectedFieldsTree: function() {
        var _this = this;

        // Convert fields to tree structure
        var children = [];
        for (var i = 0; i < this.fields.length; i++) {
            var field = this.fields[i];
            children.push({
                text: field.name || field.fieldtype,
                leaf: true,
                iconCls: 'pimcore_icon_' + field.fieldtype,
                fieldData: field
            });
        }

        this.selectedFieldsTree = new Ext.tree.Panel({
            store: new Ext.data.TreeStore({
                root: {
                    expanded: true,
                    children: children
                }
            }),
            rootVisible: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    enableDrag: true,
                    enableDrop: true,
                    ddGroup: 'extendedBlockFields'
                },
                listeners: {
                    drop: function(node, data) {
                        // Handle drop from available fields
                        if (data.records && data.records[0].get('fieldtype')) {
                            var fieldtype = data.records[0].get('fieldtype');
                            _this.showFieldConfigWindow(fieldtype, data.records[0]);
                        }
                    }
                }
            },
            listeners: {
                itemdblclick: function(view, record) {
                    // Edit field on double click
                    if (record.get('fieldData')) {
                        _this.showFieldConfigWindow(
                            record.get('fieldData').fieldtype,
                            record,
                            record.get('fieldData')
                        );
                    }
                }
            }
        });

        return this.selectedFieldsTree;
    },

    /**
     * Shows the field configuration window.
     *
     * @param {string} fieldtype - The field type
     * @param {Object} treeNode - The tree node
     * @param {Object} existingData - Existing field data for editing
     */
    showFieldConfigWindow: function(fieldtype, treeNode, existingData) {
        var _this = this;
        existingData = existingData || {};

        var window = new Ext.Window({
            title: t('configure_field') + ': ' + fieldtype,
            width: 400,
            modal: true,
            layout: 'fit',
            items: [
                {
                    xtype: 'form',
                    bodyStyle: 'padding: 10px;',
                    items: [
                        {
                            xtype: 'textfield',
                            fieldLabel: t('name'),
                            name: 'name',
                            value: existingData.name || '',
                            allowBlank: false,
                            width: '100%'
                        },
                        {
                            xtype: 'textfield',
                            fieldLabel: t('title'),
                            name: 'title',
                            value: existingData.title || '',
                            width: '100%'
                        }
                    ]
                }
            ],
            buttons: [
                {
                    text: t('save'),
                    handler: function() {
                        var form = window.down('form');
                        var values = form.getForm().getFieldValues();

                        if (!values.name) {
                            Ext.Msg.alert(t('error'), t('name_required'));
                            return;
                        }

                        // Update or add field
                        var fieldData = {
                            fieldtype: fieldtype,
                            name: values.name,
                            title: values.title
                        };

                        if (existingData.name) {
                            // Update existing
                            treeNode.set('text', values.name);
                            treeNode.set('fieldData', fieldData);
                        } else {
                            // Add new
                            var rootNode = _this.selectedFieldsTree.getRootNode();
                            rootNode.appendChild({
                                text: values.name,
                                leaf: true,
                                iconCls: 'pimcore_icon_' + fieldtype,
                                fieldData: fieldData
                            });
                        }

                        window.close();
                    }
                },
                {
                    text: t('cancel'),
                    handler: function() {
                        window.close();
                    }
                }
            ]
        });

        window.show();
    },

    /**
     * Saves the field configuration.
     */
    save: function() {
        var fields = [];
        var rootNode = this.selectedFieldsTree.getRootNode();

        rootNode.eachChild(function(node) {
            if (node.get('fieldData')) {
                fields.push(node.get('fieldData'));
            }
        });

        this.callback(fields);
        this.window.close();
    }
});
