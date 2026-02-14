/**
 * Extended Block Bundle - Data Tag Definition
 *
 * Defines the Extended Block tag for object editing in Pimcore admin.
 * This handles the user interface for managing extended block items.
 * 
 * Unlike the standard Block which stores data as serialized JSON,
 * ExtendedBlock stores data in separate database tables for SQL queryability.
 *
 * @package    ExtendedBlockBundle
 * @author     Chauhan Mukesh
 * @copyright  Copyright (c) 2026 Chauhan Mukesh
 * @license    MIT License
 */

pimcore.registerNS('pimcore.object.tags.extendedBlock');

/**
 * Extended Block tag for Pimcore object editor.
 *
 * This class provides the admin interface following Pimcore's block pattern:
 * - Inline controls for add before/after, delete, move up/down
 * - Dynamic field rendering based on block definitions
 * - Full responsive design with auto-adjusting height/width
 *
 * Field restrictions: LocalizedFields, Block, ObjectBricks, FieldCollections, 
 * and ExtendedBlock cannot be used within ExtendedBlock items.
 *
 * @extends pimcore.object.tags.abstract
 */
pimcore.object.tags.extendedBlock = Class.create(pimcore.object.tags.abstract, {

    /**
     * Data type identifier
     * @type {string}
     */
    type: 'extendedBlock',

    /**
     * Track dirty state
     * @type {boolean}
     */
    dirty: false,

    /**
     * Current block elements
     * @type {Array}
     */
    currentElements: [],

    /**
     * Data fields reference
     * @type {Object}
     */
    dataFields: {},

    /**
     * Initializes the extended block tag.
     *
     * @param {Object} data - The block data
     * @param {Object} fieldConfig - The field configuration
     */
    initialize: function(data, fieldConfig) {
        this.dirty = false;
        this.data = [];
        this.currentElements = [];
        this.dataFields = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = fieldConfig || {};
    },

    /**
     * Returns the layout component for grid column.
     *
     * @param {Object} field - The field configuration
     * @returns {Object} Column configuration
     */
    getGridColumnConfig: function(field) {
        return {
            text: t(field.label),
            width: 150,
            sortable: false,
            dataIndex: field.key,
            renderer: function(key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);
                return t('not_supported');
            }.bind(this, field.key)
        };
    },

    /**
     * Returns the layout component for the Pimcore admin.
     *
     * Creates a panel container following Pimcore's block pattern:
     * - Inline toolbar controls per item
     * - Auto-height management
     * - Collapsible support
     *
     * @returns {Ext.Panel} The layout component
     */
    getLayoutEdit: function() {
        this.fieldConfig.datatype = 'layout';
        this.fieldConfig.fieldtype = 'panel';

        var panelConf = {
            autoHeight: true,
            border: true,
            style: 'margin-bottom: 10px',
            componentCls: this.getWrapperClassNames(),
            collapsible: this.fieldConfig.collapsible,
            collapsed: this.fieldConfig.collapsed,
            cls: 'extended-block-container'
        };

        if (this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }

        this.component = new Ext.Panel(panelConf);

        this.component.addListener('render', function() {
            if (this.object && this.object.data && this.object.data.metaData && 
                this.object.data.metaData[this.getName()] && 
                this.object.data.metaData[this.getName()].hasParentValue) {
                this.addInheritanceSourceButton(this.object.data.metaData[this.getName()]);
            }
        }.bind(this));

        this.initData();

        return this.component;
    },

    /**
     * Initializes block data and renders items.
     */
    initData: function() {
        if (this.data.length < 1) {
            this.component.add(this.getControls());
        } else {
            Ext.suspendLayouts();
            for (var i = 0; i < this.data.length; i++) {
                this.addBlockElement(
                    i,
                    { oIndex: this.data[i].oIndex },
                    this.data[i].data,
                    this.data[i].type,
                    true
                );
            }
            Ext.resumeLayouts();
        }

        this.component.updateLayout();
    },

    /**
     * Creates inline toolbar controls for block elements.
     *
     * @param {Ext.Panel} blockElement - The block element panel (null for initial add button)
     * @returns {Ext.Toolbar} The toolbar with controls
     */
    getControls: function(blockElement) {
        var _this = this;
        var items = [];

        // Get block type menu items
        var blockTypeMenuItems = this.getBlockTypeMenuItems();

        if (blockElement) {
            // Add before
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_plus',
                iconCls: 'pimcore_icon_plus_up',
                menu: this.createBlockTypeMenu('before', blockElement)
            });

            // Add after
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_plus',
                iconCls: 'pimcore_icon_plus_down',
                menu: this.createBlockTypeMenu('after', blockElement)
            });

            // Delete
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_minus',
                iconCls: 'pimcore_icon_minus',
                listeners: {
                    click: this.removeBlock.bind(this, blockElement)
                }
            });

            // Move up
            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: 'pimcore_block_button_up',
                iconCls: 'pimcore_icon_up',
                listeners: {
                    click: this.moveBlockUp.bind(this, blockElement)
                }
            });

            // Move down
            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: 'pimcore_block_button_down',
                iconCls: 'pimcore_icon_down',
                listeners: {
                    click: this.moveBlockDown.bind(this, blockElement)
                }
            });
        } else {
            // Initial add button (when no items exist)
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_plus',
                iconCls: 'pimcore_icon_plus',
                menu: this.createBlockTypeMenu('after', null)
            });
        }

        var toolbar = new Ext.Toolbar({
            cls: 'extended-block-toolbar',
            items: items
        });

        return toolbar;
    },

    /**
     * Creates a menu for selecting block type when adding.
     *
     * @param {string} position - 'before' or 'after'
     * @param {Ext.Panel} blockElement - The reference block element
     * @returns {Ext.menu.Menu} The block type menu
     */
    createBlockTypeMenu: function(position, blockElement) {
        var _this = this;
        var blockDefinitions = this.fieldConfig.blockDefinitions || {};
        var menuItems = [];

        for (var typeName in blockDefinitions) {
            if (blockDefinitions.hasOwnProperty(typeName)) {
                var blockDef = blockDefinitions[typeName];
                menuItems.push({
                    text: blockDef.name || typeName,
                    iconCls: 'pimcore_icon_add',
                    handler: function(type, pos, element) {
                        return function() {
                            _this.addBlock(element, pos, type);
                        };
                    }(typeName, position, blockElement)
                });
            }
        }

        // Default type if no definitions
        if (menuItems.length === 0) {
            menuItems.push({
                text: t('default') || 'Default',
                iconCls: 'pimcore_icon_add',
                handler: function() {
                    _this.addBlock(blockElement, position, 'default');
                }
            });
        }

        return new Ext.menu.Menu({ items: menuItems });
    },

    /**
     * Gets menu items for block types.
     *
     * @returns {Array} Menu item configurations
     */
    getBlockTypeMenuItems: function() {
        var _this = this;
        var blockDefinitions = this.fieldConfig.blockDefinitions || {};
        var menuItems = [];

        for (var typeName in blockDefinitions) {
            if (blockDefinitions.hasOwnProperty(typeName)) {
                var blockDef = blockDefinitions[typeName];
                menuItems.push({
                    text: blockDef.name || typeName,
                    value: typeName
                });
            }
        }

        if (menuItems.length === 0) {
            menuItems.push({
                text: t('default') || 'Default',
                value: 'default'
            });
        }

        return menuItems;
    },

    /**
     * Detects the index of a block element.
     *
     * @param {Ext.Panel} blockElement - The block element
     * @returns {number} The index
     */
    detectBlockIndex: function(blockElement) {
        var index;
        for (var s = 0; s < this.component.items.items.length; s++) {
            if (this.component.items.items[s].key === blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    },

    /**
     * Adds a new block element.
     *
     * @param {Ext.Panel} blockElement - Reference block element
     * @param {string} position - 'before' or 'after'
     * @param {string} type - Block type name
     */
    addBlock: function(blockElement, position, type) {
        // Check max items limit
        if (this.fieldConfig.maxItems) {
            var itemAmount = 0;
            for (var s = 0; s < this.component.items.items.length; s++) {
                if (typeof this.component.items.items[s].key !== 'undefined') {
                    itemAmount++;
                }
            }

            if (itemAmount >= this.fieldConfig.maxItems) {
                Ext.MessageBox.alert(t('error'), t('limit_reached') || 'Maximum number of items reached');
                return;
            }
        }

        var index = 0;
        if (blockElement) {
            index = this.detectBlockIndex(blockElement);
        }

        if (position !== 'before') {
            index++;
        }

        this.addBlockElement(index, {}, null, type || 'default');
    },

    /**
     * Removes a block element.
     *
     * @param {Ext.Panel} blockElement - The block element to remove
     */
    removeBlock: function(blockElement) {
        var key = blockElement.key;
        this.currentElements[key] = 'deleted';

        this.component.remove(blockElement);
        this.dirty = true;

        // Check for remaining elements
        if (this.component.items.items.length < 1) {
            this.component.removeAll();
            this.component.add(this.getControls());
            this.component.updateLayout();
            this.currentElements = [];
        }
    },

    /**
     * Moves a block element up.
     *
     * @param {Ext.Panel} blockElement - The block element to move
     */
    moveBlockUp: function(blockElement) {
        this.component.moveBefore(blockElement, blockElement.previousSibling());
        this.dirty = true;
    },

    /**
     * Moves a block element down.
     *
     * @param {Ext.Panel} blockElement - The block element to move
     */
    moveBlockDown: function(blockElement) {
        this.component.moveAfter(blockElement, blockElement.nextSibling());
        this.dirty = true;
    },

    /**
     * Adds a block element to the container.
     *
     * @param {number} index - Position index
     * @param {Object} config - Configuration object with oIndex
     * @param {Object} blockData - Field data for the block
     * @param {string} type - Block type name
     * @param {boolean} ignoreChange - Whether to ignore dirty state change
     */
    addBlockElement: function(index, config, blockData, type, ignoreChange) {
        var _this = this;
        var oIndex = config.oIndex;

        // Remove the initial toolbar if there are no elements
        if (this.currentElements.length < 1) {
            this.component.removeAll();
        }

        this.dataFields = {};
        this.currentData = {};
        this.currentType = type || 'default';

        if (blockData) {
            this.currentData = blockData;
        }

        // Get block definition
        var blockDef = this.fieldConfig.blockDefinitions 
            ? this.fieldConfig.blockDefinitions[this.currentType] 
            : null;
        var blockTitle = blockDef ? (blockDef.name || this.currentType) : this.currentType;

        // Build field items
        var fieldItems = this.buildFieldItems(blockDef);

        var blockElement = new Ext.Panel({
            pimcore_oIndex: oIndex,
            pimcore_type: this.currentType,
            bodyStyle: 'padding: 10px;',
            style: 'margin: 0 0 10px 0;',
            manageHeight: false,
            border: true,
            title: blockTitle,
            cls: 'extended-block-item',
            collapsible: true,
            collapsed: false,
            animCollapse: false,
            items: fieldItems,
            disabled: this.fieldConfig.noteditable
        });

        blockElement.insert(0, this.getControls(blockElement));

        blockElement.key = this.currentElements.length;
        this.component.insert(index, blockElement);
        this.component.updateLayout();

        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields,
            type: this.currentType
        });

        if (!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = {};
        this.currentData = {};
    },

    /**
     * Builds field items based on block definition.
     *
     * @param {Object} blockDef - The block definition
     * @returns {Array} Array of field components
     */
    buildFieldItems: function(blockDef) {
        var items = [];
        var fields = blockDef ? (blockDef.fields || []) : [];

        for (var i = 0; i < fields.length; i++) {
            var fieldDef = fields[i];
            var fieldComponent = this.createFieldComponent(fieldDef);
            if (fieldComponent) {
                items.push(fieldComponent);
                this.dataFields[fieldDef.name] = fieldComponent;
            }
        }

        return items;
    },

    /**
     * Creates a field component based on field definition.
     *
     * @param {Object} fieldDef - The field definition
     * @returns {Ext.Component} The field component
     */
    createFieldComponent: function(fieldDef) {
        var value = this.currentData[fieldDef.name] || null;
        var fieldLabel = fieldDef.title || fieldDef.name;
        var tooltip = fieldDef.tooltip || '';

        var baseConfig = {
            fieldLabel: fieldLabel,
            name: fieldDef.name,
            anchor: '100%',
            labelWidth: 150
        };

        if (tooltip) {
            baseConfig.labelAttrTpl = 'data-qtip="' + Ext.util.Format.htmlEncode(tooltip) + '"';
        }

        switch (fieldDef.fieldtype) {
            case 'input':
                return new Ext.form.TextField(Ext.apply(baseConfig, {
                    value: value
                }));

            case 'textarea':
                return new Ext.form.TextArea(Ext.apply(baseConfig, {
                    value: value,
                    height: 100
                }));

            case 'wysiwyg':
                return new Ext.form.HtmlEditor(Ext.apply(baseConfig, {
                    value: value,
                    height: 200
                }));

            case 'checkbox':
                return new Ext.form.Checkbox(Ext.apply(baseConfig, {
                    checked: value === true
                }));

            case 'numeric':
                return new Ext.form.NumberField(Ext.apply(baseConfig, {
                    value: value
                }));

            case 'date':
                return new Ext.form.DateField(Ext.apply(baseConfig, {
                    value: value ? new Date(value) : null,
                    format: 'Y-m-d'
                }));

            case 'select':
                return new Ext.form.ComboBox(Ext.apply(baseConfig, {
                    value: value,
                    store: fieldDef.options || [],
                    editable: false,
                    forceSelection: true
                }));

            case 'multiselect':
                return new Ext.form.field.Tag(Ext.apply(baseConfig, {
                    value: value,
                    store: fieldDef.options || [],
                    multiSelect: true
                }));

            case 'link':
                return new Ext.form.TextField(Ext.apply(baseConfig, {
                    value: value,
                    vtype: 'url'
                }));

            case 'image':
                return this.createImageField(fieldDef, value);

            default:
                return new Ext.form.TextField(Ext.apply(baseConfig, {
                    value: value
                }));
        }
    },

    /**
     * Creates an image field component.
     *
     * @param {Object} fieldDef - The field definition
     * @param {*} value - The current value
     * @returns {Ext.Panel} The image field panel
     */
    createImageField: function(fieldDef, value) {
        var _this = this;
        var fieldName = fieldDef.name;
        var fieldLabel = fieldDef.title || fieldDef.name;

        var imagePanel = new Ext.Panel({
            layout: 'hbox',
            border: false,
            margin: '0 0 10px 0',
            items: [
                {
                    xtype: 'label',
                    text: fieldLabel + ':',
                    width: 150
                },
                {
                    xtype: 'textfield',
                    name: fieldName,
                    value: value,
                    flex: 1
                },
                {
                    xtype: 'button',
                    text: t('select') || 'Select',
                    iconCls: 'pimcore_icon_search',
                    handler: function() {
                        // Image selection would use Pimcore's asset selector
                        pimcore.helpers.itemselector(
                            false,
                            function(items) {
                                if (items.length > 0) {
                                    var textField = imagePanel.down('textfield');
                                    if (textField) {
                                        textField.setValue(items[0].fullpath);
                                    }
                                }
                            },
                            { type: ['asset'], subtype: { asset: ['image'] } }
                        );
                    }
                }
            ]
        });

        return imagePanel;
    },

    /**
     * Returns the layout for view mode (disabled).
     *
     * @returns {Ext.Panel} The layout component
     */
    getLayoutShow: function() {
        this.component = this.getLayoutEdit();
        this.component.disable();
        return this.component;
    },

    /**
     * Gets the current value for saving.
     *
     * @returns {Array} The block data array
     */
    getValue: function() {
        var data = [];
        var element;
        var elementData = {};

        for (var s = 0; s < this.component.items.items.length; s++) {
            elementData = {};
            if (this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u = 0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    try {
                        var field = element.fields[elementFieldName];
                        if (field && typeof field.getValue === 'function') {
                            elementData[elementFieldName] = field.getValue();
                        } else if (field && field.down && field.down('textfield')) {
                            // Handle composite fields like image
                            elementData[elementFieldName] = field.down('textfield').getValue();
                        }
                    } catch (e) {
                        console.log(e);
                        elementData[elementFieldName] = '';
                    }
                }

                data.push({
                    data: elementData,
                    oIndex: element.container.pimcore_oIndex,
                    type: element.type || 'default'
                });
            }
        }

        return data;
    },

    /**
     * Gets the name of this field.
     *
     * @returns {string} The field name
     */
    getName: function() {
        return this.fieldConfig.name;
    },

    /**
     * Checks if this field is dirty (modified).
     *
     * @returns {boolean} True if modified
     */
    isDirty: function() {
        // Check elements
        var element;

        if (!this.isRendered()) {
            return false;
        }

        if (typeof this.component.items === 'undefined') {
            return false;
        }

        for (var s = 0; s < this.component.items.items.length; s++) {
            if (this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u = 0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    var field = element.fields[elementFieldName];
                    if (field && typeof field.isDirty === 'function' && field.isDirty()) {
                        return true;
                    }
                }
            }
        }

        return this.dirty;
    },

    /**
     * Checks if any field in this block is mandatory.
     *
     * @returns {boolean} True if mandatory
     */
    isMandatory: function() {
        var element;

        for (var s = 0; s < this.component.items.items.length; s++) {
            if (this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u = 0; u < elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    var field = element.fields[elementFieldName];
                    if (field && typeof field.isMandatory === 'function' && field.isMandatory()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
});
