/**
 * Extended Block Bundle - Data Tag Definition
 *
 * Defines the Extended Block tag for object editing in Pimcore admin.
 * This handles the user interface for managing extended block items.
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
 * This class provides the admin interface for:
 * - Adding new block items
 * - Editing existing block items
 * - Reordering items via drag and drop
 * - Removing items
 * - Managing localized content within blocks
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
     * Whether this field supports dirty detection
     * @type {boolean}
     */
    allowBatchAppend: true,

    /**
     * Container for block items
     * @type {Array}
     */
    items: [],

    /**
     * Initializes the extended block tag.
     *
     * @param {Object} data - The block data
     * @param {Object} fieldConfig - The field configuration
     */
    initialize: function(data, fieldConfig) {
        this.data = data || [];
        this.fieldConfig = fieldConfig;
        this.items = [];
    },

    /**
     * Returns the layout component for the Pimcore admin.
     *
     * Creates the main panel containing:
     * - Toolbar with add/collapse buttons
     * - Container for block items
     *
     * @returns {Ext.Panel} The layout component
     */
    getLayoutEdit: function() {
        // Create toolbar
        var toolbar = this.createToolbar();

        // Create items container
        this.itemsContainer = new Ext.Panel({
            layout: 'anchor',
            border: false,
            defaults: {
                anchor: '100%'
            },
            items: []
        });

        // Create main component
        this.component = new Ext.Panel({
            xtype: 'panel',
            title: this.fieldConfig.title,
            layout: 'anchor',
            border: true,
            style: 'margin-bottom: 10px;',
            bodyStyle: 'padding: 10px;',
            collapsible: this.fieldConfig.collapsible,
            collapsed: this.fieldConfig.collapsed,
            autoHeight: true,
            cls: 'extended-block-container',
            tbar: toolbar,
            items: [this.itemsContainer]
        });

        // Load initial data
        this.loadData();

        return this.component;
    },

    /**
     * Creates the toolbar for the extended block panel.
     *
     * @returns {Ext.Toolbar} The toolbar
     */
    createToolbar: function() {
        var _this = this;
        var menuItems = [];

        // Create menu items for each block type
        var blockDefinitions = this.fieldConfig.blockDefinitions || {};
        for (var typeName in blockDefinitions) {
            if (blockDefinitions.hasOwnProperty(typeName)) {
                var blockDef = blockDefinitions[typeName];
                menuItems.push({
                    text: blockDef.name || typeName,
                    iconCls: blockDef.icon || 'pimcore_icon_add',
                    handler: function(type) {
                        return function() {
                            _this.addItem(type);
                        };
                    }(typeName)
                });
            }
        }

        // If no block types defined, add default
        if (menuItems.length === 0) {
            menuItems.push({
                text: t('default'),
                iconCls: 'pimcore_icon_add',
                handler: function() {
                    _this.addItem('default');
                }
            });
        }

        return new Ext.Toolbar({
            items: [
                {
                    xtype: 'button',
                    text: t('add'),
                    iconCls: 'pimcore_icon_add',
                    menu: menuItems
                },
                '-',
                {
                    xtype: 'button',
                    text: t('collapse_all'),
                    iconCls: 'pimcore_icon_collapse',
                    handler: function() {
                        _this.collapseAll();
                    }
                },
                {
                    xtype: 'button',
                    text: t('expand_all'),
                    iconCls: 'pimcore_icon_expand',
                    handler: function() {
                        _this.expandAll();
                    }
                }
            ]
        });
    },

    /**
     * Loads data from the server into the UI.
     */
    loadData: function() {
        if (!this.data || !Array.isArray(this.data)) {
            return;
        }

        for (var i = 0; i < this.data.length; i++) {
            this.createItemPanel(this.data[i], i);
        }
    },

    /**
     * Adds a new block item.
     *
     * @param {string} type - The block type
     */
    addItem: function(type) {
        // Check max items limit
        if (this.fieldConfig.maxItems && this.items.length >= this.fieldConfig.maxItems) {
            Ext.MessageBox.alert(t('error'), t('maximum_items_reached'));
            return;
        }

        var itemData = {
            type: type,
            index: this.items.length,
            data: {},
            localizedData: {}
        };

        this.createItemPanel(itemData, this.items.length);
        this.markDirty();
    },

    /**
     * Creates a panel for a single block item.
     *
     * @param {Object} itemData - The item data
     * @param {number} index - The item index
     */
    createItemPanel: function(itemData, index) {
        var _this = this;
        var type = itemData.type || 'default';
        var blockDef = this.fieldConfig.blockDefinitions[type] || {};

        // Build field components
        var fieldComponents = this.buildFieldComponents(itemData, blockDef);

        // Create item panel
        var itemPanel = new Ext.Panel({
            title: (blockDef.name || type) + ' #' + (index + 1),
            layout: 'anchor',
            border: true,
            collapsible: true,
            collapsed: false,
            style: 'margin-bottom: 10px;',
            bodyStyle: 'padding: 10px;',
            cls: 'extended-block-item',
            tools: [
                {
                    type: 'up',
                    handler: function(event, toolEl, panel) {
                        _this.moveItem(panel, -1);
                    }
                },
                {
                    type: 'down',
                    handler: function(event, toolEl, panel) {
                        _this.moveItem(panel, 1);
                    }
                },
                {
                    type: 'close',
                    handler: function(event, toolEl, panel) {
                        _this.removeItem(panel);
                    }
                }
            ],
            items: fieldComponents,
            itemData: itemData
        });

        // Store reference
        this.items.push(itemPanel);

        // Add to container
        this.itemsContainer.add(itemPanel);
        this.itemsContainer.updateLayout();
    },

    /**
     * Builds field components for a block item.
     *
     * @param {Object} itemData - The item data
     * @param {Object} blockDef - The block definition
     * @returns {Array} Array of field components
     */
    buildFieldComponents: function(itemData, blockDef) {
        var components = [];
        var fields = blockDef.fields || [];

        for (var i = 0; i < fields.length; i++) {
            var fieldDef = fields[i];

            if (fieldDef.fieldtype === 'localizedfields') {
                // Handle localized fields
                components.push(this.buildLocalizedFieldsComponent(itemData, fieldDef));
            } else {
                // Build standard field
                var component = this.buildFieldComponent(itemData, fieldDef);
                if (component) {
                    components.push(component);
                }
            }
        }

        return components;
    },

    /**
     * Builds a single field component.
     *
     * @param {Object} itemData - The item data
     * @param {Object} fieldDef - The field definition
     * @returns {Ext.Component} The field component
     */
    buildFieldComponent: function(itemData, fieldDef) {
        var value = (itemData.data && itemData.data[fieldDef.name]) || null;

        // Create appropriate field type
        switch (fieldDef.fieldtype) {
            case 'input':
                return new Ext.form.TextField({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    value: value,
                    anchor: '100%'
                });

            case 'textarea':
                return new Ext.form.TextArea({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    value: value,
                    anchor: '100%',
                    height: 100
                });

            case 'wysiwyg':
                return new Ext.form.HtmlEditor({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    value: value,
                    anchor: '100%',
                    height: 200
                });

            case 'checkbox':
                return new Ext.form.Checkbox({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    checked: value === true
                });

            case 'numeric':
                return new Ext.form.NumberField({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    value: value,
                    anchor: '100%'
                });

            default:
                return new Ext.form.TextField({
                    fieldLabel: fieldDef.title || fieldDef.name,
                    name: fieldDef.name,
                    value: value,
                    anchor: '100%'
                });
        }
    },

    /**
     * Builds a localized fields component.
     *
     * @param {Object} itemData - The item data
     * @param {Object} fieldDef - The localized fields definition
     * @returns {Ext.TabPanel} The localized fields tab panel
     */
    buildLocalizedFieldsComponent: function(itemData, fieldDef) {
        var languages = pimcore.settings.websiteLanguages || ['en'];
        var tabs = [];

        for (var i = 0; i < languages.length; i++) {
            var lang = languages[i];
            var langData = (itemData.localizedData && itemData.localizedData[lang]) || {};
            var langFields = [];

            // Build fields for this language
            var localizedFieldDefs = fieldDef.fieldDefinitions || [];
            for (var j = 0; j < localizedFieldDefs.length; j++) {
                var localizedFieldDef = localizedFieldDefs[j];
                var localizedValue = langData[localizedFieldDef.name] || null;

                var component = this.buildFieldComponent(
                    { data: langData },
                    localizedFieldDef
                );
                if (component) {
                    langFields.push(component);
                }
            }

            tabs.push({
                title: pimcore.available_languages[lang] || lang,
                iconCls: 'pimcore_icon_language_' + lang.toLowerCase(),
                layout: 'anchor',
                bodyStyle: 'padding: 10px;',
                items: langFields,
                language: lang
            });
        }

        return new Ext.TabPanel({
            activeTab: 0,
            deferredRender: false,
            border: true,
            style: 'margin-top: 10px;',
            items: tabs
        });
    },

    /**
     * Moves a block item up or down.
     *
     * @param {Ext.Panel} panel - The item panel to move
     * @param {number} direction - Direction (-1 for up, 1 for down)
     */
    moveItem: function(panel, direction) {
        var index = this.items.indexOf(panel);
        var newIndex = index + direction;

        if (newIndex < 0 || newIndex >= this.items.length) {
            return;
        }

        // Swap items in array
        var temp = this.items[index];
        this.items[index] = this.items[newIndex];
        this.items[newIndex] = temp;

        // Update UI
        this.itemsContainer.removeAll(false);
        for (var i = 0; i < this.items.length; i++) {
            this.itemsContainer.add(this.items[i]);
        }
        this.itemsContainer.updateLayout();

        this.markDirty();
    },

    /**
     * Removes a block item.
     *
     * @param {Ext.Panel} panel - The item panel to remove
     */
    removeItem: function(panel) {
        var _this = this;

        Ext.MessageBox.confirm(t('delete'), t('are_you_sure'), function(btn) {
            if (btn === 'yes') {
                var index = _this.items.indexOf(panel);
                if (index > -1) {
                    _this.items.splice(index, 1);
                    _this.itemsContainer.remove(panel);
                    _this.markDirty();
                }
            }
        });
    },

    /**
     * Collapses all item panels.
     */
    collapseAll: function() {
        for (var i = 0; i < this.items.length; i++) {
            this.items[i].collapse();
        }
    },

    /**
     * Expands all item panels.
     */
    expandAll: function() {
        for (var i = 0; i < this.items.length; i++) {
            this.items[i].expand();
        }
    },

    /**
     * Gets the current value for saving.
     *
     * @returns {Array} The block data
     */
    getValue: function() {
        var result = [];

        for (var i = 0; i < this.items.length; i++) {
            var panel = this.items[i];
            var itemData = panel.itemData || {};
            var data = {};
            var localizedData = {};

            // Collect field values
            var fields = panel.query('field');
            for (var j = 0; j < fields.length; j++) {
                var field = fields[j];
                if (field.name) {
                    data[field.name] = field.getValue();
                }
            }

            // Collect localized values
            var tabPanels = panel.query('tabpanel');
            for (var k = 0; k < tabPanels.length; k++) {
                var tabPanel = tabPanels[k];
                var tabs = tabPanel.items.items;

                for (var l = 0; l < tabs.length; l++) {
                    var tab = tabs[l];
                    var lang = tab.language;
                    if (lang) {
                        localizedData[lang] = {};
                        var langFields = tab.query('field');
                        for (var m = 0; m < langFields.length; m++) {
                            var langField = langFields[m];
                            if (langField.name) {
                                localizedData[lang][langField.name] = langField.getValue();
                            }
                        }
                    }
                }
            }

            result.push({
                id: itemData.id || null,
                type: itemData.type || 'default',
                index: i,
                data: data,
                localizedData: localizedData
            });
        }

        return result;
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
     * Marks this field as dirty (modified).
     */
    markDirty: function() {
        this.dirty = true;
    },

    /**
     * Checks if this field is dirty (modified).
     *
     * @returns {boolean} True if modified
     */
    isDirty: function() {
        if (this.dirty) {
            return true;
        }

        // Check if any field values have changed
        var currentValue = this.getValue();
        return JSON.stringify(currentValue) !== JSON.stringify(this.data);
    }
});
