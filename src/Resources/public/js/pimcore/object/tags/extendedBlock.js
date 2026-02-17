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
 * - Dynamic field rendering based on children definitions (like Pimcore Block)
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
     * Layout definitions cache.
     * Required by pimcore.object.helpers.edit mixin for getRecursiveLayout method.
     * @type {Object}
     */
    layoutDefinitions: {},

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
        this.layoutDefinitions = {};
        this.dataFields = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = {};
        Ext.apply(this.fieldConfig, fieldConfig);
    },

    /**
     * Grid column width for extended block cells.
     * @constant {number}
     */
    GRID_COLUMN_WIDTH: 200,

    /**
     * Returns the layout component for grid column.
     *
     * Implements grid column configuration following the structuredTable pattern.
     * Renders a summary table showing block item count and preview of first items.
     *
     * @param {Object} field - The field configuration
     * @returns {Object} Column configuration
     */
    getGridColumnConfig: function(field) {
        return {
            text: t(field.label),
            width: this.GRID_COLUMN_WIDTH,
            sortable: false,
            dataIndex: field.key,
            renderer: this.createGridRenderer(field)
        };
    },

    /**
     * Creates the grid cell renderer function.
     * Follows structuredTable pattern for proper table UI rendering.
     *
     * @param {Object} field - The field configuration
     * @returns {Function} The renderer function
     */
    createGridRenderer: function(field) {
        return function(key, fieldConfig, value, metaData, record) {
            this.applyPermissionStyle(key, value, metaData, record);
            this.applyInheritanceStyle(key, metaData, record);

            if (!this.isValidGridValue(value)) {
                return this.renderEmptyState();
            }

            var count = value.count || 0;
            var fields = value.fields || [];
            var items = value.items || [];

            if (count === 0 || fields.length === 0) {
                return this.renderEmptyState();
            }

            return this.renderGridTable(count, fields, items);
        }.bind(this, field.key, field);
    },

    /**
     * Applies inheritance styling to the grid cell.
     *
     * @param {string} key - The field key
     * @param {Object} metaData - The cell metadata
     * @param {Object} record - The grid record
     */
    applyInheritanceStyle: function(key, metaData, record) {
        var inheritedFields = record.data.inheritedFields;
        if (inheritedFields && inheritedFields[key] && inheritedFields[key].inherited === true) {
            metaData.tdCls += ' grid_value_inherited';
        }
    },

    /**
     * Checks if the value is valid for grid display.
     *
     * @param {*} value - The value to check
     * @returns {boolean} True if valid
     */
    isValidGridValue: function(value) {
        return value && typeof value === 'object';
    },

    /**
     * Renders the empty state HTML.
     *
     * @returns {string} Empty state HTML
     */
    renderEmptyState: function() {
        return '<span style="color: #999;">0 items</span>';
    },

    /**
     * Renders a proper table with headers and data rows.
     * Follows structuredTable rendering pattern.
     *
     * @param {number} count - Total item count
     * @param {Array} fields - Array of field definitions {key, label}
     * @param {Array} items - Array of row data objects
     * @returns {string} The rendered HTML table
     */
    renderGridTable: function(count, fields, items) {
        var table = '<table cellpadding="2" cellspacing="0" border="1" ' +
                    'style="font-size: 11px; border-collapse: collapse;">';

        // Column headers
        table += '<tr>';
        for (var c = 0; c < fields.length; c++) {
            table += '<td style="background: #f0f0f0; font-weight: bold; padding: 2px 4px;">';
            table += Ext.util.Format.htmlEncode(t(fields[c].label));
            table += '</td>';
        }
        table += '</tr>';

        // Data rows
        for (var i = 0; i < items.length; i++) {
            table += '<tr>';
            for (var f = 0; f < fields.length; f++) {
                var cellValue = items[i][fields[f].key] || '';
                table += '<td style="padding: 2px 4px; max-width: 100px; ' +
                         'overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">';
                table += Ext.util.Format.htmlEncode(cellValue);
                table += '</td>';
            }
            table += '</tr>';
        }

        // Show "more items" indicator if there are more
        if (count > items.length) {
            table += '<tr>';
            table += '<td colspan="' + fields.length + '" ' +
                     'style="color: #999; font-style: italic; padding: 2px 4px;">';
            table += '... and ' + (count - items.length) + ' more';
            table += '</td>';
            table += '</tr>';
        }

        table += '</table>';
        return table;
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
                    {
                        oIndex: this.data[i].oIndex
                    },
                    this.data[i].data,
                    true
                );
            }
            Ext.resumeLayouts();
        }

        this.component.updateLayout();
    },

    /**
     * Creates inline toolbar controls for block elements.
     * Follows Pimcore Block pattern with direct handlers.
     *
     * @param {Ext.Panel} blockElement - The block element panel (null for initial add button)
     * @returns {Ext.Toolbar} The toolbar with controls
     */
    getControls: function(blockElement) {
        var items = [];

        if (blockElement) {
            // Add before
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_plus',
                iconCls: 'pimcore_icon_plus_up',
                handler: this.addBlock.bind(this, blockElement, 'before')
            });

            // Add after
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: 'pimcore_block_button_plus',
                iconCls: 'pimcore_icon_plus_down',
                handler: this.addBlock.bind(this, blockElement, 'after')
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
                handler: this.addBlock.bind(this, blockElement, 'after')
            });
        }

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar;
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
     * Closes any open editors (WYSIWYG, etc.)
     */
    closeOpenEditors: function() {
        for (var i = 0; i < this.currentElements.length; i++) {
            if (typeof this.currentElements[i] === 'object') {
                for (var e = 0; e < this.currentElements[i]['fields'].length; e++) {
                    if (typeof this.currentElements[i]['fields'][e]['close'] === 'function') {
                        this.currentElements[i]['fields'][e].close();
                    }
                }
            }
        }
    },

    /**
     * Adds a new block element.
     * Follows Pimcore Block pattern.
     *
     * @param {Ext.Panel} blockElement - Reference block element
     * @param {string} position - 'before' or 'after'
     */
    addBlock: function(blockElement, position) {
        this.closeOpenEditors();

        // Check max items limit
        if (this.fieldConfig.maxItems) {
            var itemAmount = 0;
            for (var s = 0; s < this.component.items.items.length; s++) {
                if (typeof this.component.items.items[s].key !== 'undefined') {
                    itemAmount++;
                }
            }

            if (itemAmount >= this.fieldConfig.maxItems) {
                Ext.MessageBox.alert(t('error'), t('limit_reached'));
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

        this.addBlockElement(index, {});
    },

    /**
     * Removes a block element.
     *
     * @param {Ext.Panel} blockElement - The block element to remove
     */
    removeBlock: function(blockElement) {
        this.closeOpenEditors();

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
        this.closeOpenEditors();
        this.component.moveBefore(blockElement, blockElement.previousSibling());
        this.dirty = true;
    },

    /**
     * Moves a block element down.
     *
     * @param {Ext.Panel} blockElement - The block element to move
     */
    moveBlockDown: function(blockElement) {
        this.closeOpenEditors();
        this.component.moveAfter(blockElement, blockElement.nextSibling());
        this.dirty = true;
    },

    /**
     * Adds a block element to the container.
     * Follows Pimcore Block pattern using getRecursiveLayout.
     *
     * @param {number} index - Position index
     * @param {Object} config - Configuration object with oIndex
     * @param {Object} blockData - Field data for the block
     * @param {boolean} ignoreChange - Whether to ignore dirty state change
     */
    addBlockElement: function(index, config, blockData, ignoreChange) {
        var oIndex = config.oIndex;
        this.closeOpenEditors();

        // Remove the initial toolbar if there are no elements
        if (this.currentElements.length < 1) {
            this.component.removeAll();
        }

        this.dataFields = {};
        this.currentData = {};

        if (blockData) {
            this.currentData = blockData;
        }

        // Build field items using Pimcore's getRecursiveLayout
        // Parameters: layoutDef, noteditable, context, skipLayoutChildren, onlyLayoutChildren, dataProvider, disableLazyRendering
        var fieldConfig = this.fieldConfig;

        var context = this.getContext();
        context['subContainerType'] = 'extendedBlock';
        context['subContainerKey'] = fieldConfig.name;
        context['applyDefaults'] = true;

        // Call getRecursiveLayout (from pimcore.object.helpers.edit mixin)
        // - fieldConfig: layout definition with children
        // - undefined: noteditable (use default)
        // - context: context object with containerType, objectId, etc.
        // - undefined: skipLayoutChildren (use default)
        // - undefined: onlyLayoutChildren (use default)
        // - undefined: dataProvider (will use 'this' as default)
        // - true: disableLazyRendering (force immediate rendering)
        var items = this.getRecursiveLayout(fieldConfig, undefined, context, undefined, undefined, undefined, true);

        items = items.items;

        var blockElement = new Ext.Panel({
            pimcore_oIndex: oIndex,
            bodyStyle: 'padding: 10px;',
            style: 'margin: 10px 0 10px 0;' + (this.fieldConfig.styleElement || ''),
            manageHeight: false,
            border: false,
            items: [
                {
                    xtype: 'panel',
                    style: 'margin: 10px 0 10px 0;',
                    items: items
                }
            ],
            disabled: this.fieldConfig.noteditable
        });

        blockElement.insert(0, this.getControls(blockElement));

        blockElement.key = this.currentElements.length;
        this.component.insert(index, blockElement);
        this.component.updateLayout();

        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields
        });

        if (!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = {};
        this.currentData = {};

        this.updateBlockIndices();
    },

    /**
     * Updates block indices for all field contexts.
     */
    updateBlockIndices: function() {
        for (var itemIndex = 0; itemIndex < this.component.items.items.length; itemIndex++) {
            var item = this.component.items.items[itemIndex];

            for (var j = 0; j < this.currentElements.length; j++) {
                if (item !== this.currentElements[j].container) {
                    continue;
                }

                var fields = this.currentElements[j].fields;
                for (var fieldName in fields) {
                    if (fields.hasOwnProperty(fieldName) && fields[fieldName].context) {
                        fields[fieldName].context.index = itemIndex;
                    }
                }
            }
        }
    },

    /**
     * Gets data for a field (required by getRecursiveLayout).
     *
     * @param {Object} fieldConfig - Field configuration
     * @returns {*} The field data
     */
    getDataForField: function(fieldConfig) {
        var name = fieldConfig.name;
        return this.currentData[name];
    },

    /**
     * Gets metadata for a field (required by getRecursiveLayout).
     *
     * @param {Object} fieldConfig - Field configuration
     * @returns {null} Always returns null for ExtendedBlock
     */
    getMetaDataForField: function(fieldConfig) {
        return null;
    },

    /**
     * Adds a field to the dataFields collection (required by getRecursiveLayout).
     *
     * @param {Object} field - The field instance
     * @param {string} name - The field name
     */
    addToDataFields: function(field, name) {
        if (this.dataFields[name]) {
            // This is especially for localized fields which get aggregated here into one field definition
            // in the case that there are more than one localized fields in the class definition
            // see also ClassDefinition::extractDataDefinitions();
            if (typeof this.dataFields[name]['addReferencedField'] === 'function') {
                this.dataFields[name].addReferencedField(field);
            }
        } else {
            this.dataFields[name] = field;
        }
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
     * Follows Pimcore Block pattern.
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
                        // no check for dirty, ... always send all field to the server
                        elementData[element.fields[elementFieldName].getName()] = element.fields[elementFieldName].getValue();
                    } catch (e) {
                        console.log(e);
                        elementData[element.fields[elementFieldName].getName()] = '';
                    }
                }

                data.push({
                    data: elementData,
                    oIndex: element.container.pimcore_oIndex
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

// Add helper methods for recursive layout rendering (like Pimcore Block)
pimcore.object.tags.extendedBlock.addMethods(pimcore.object.helpers.edit);
