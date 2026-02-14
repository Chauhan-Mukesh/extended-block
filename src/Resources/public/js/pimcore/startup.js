/**
 * Extended Block Bundle - Startup Script
 *
 * Initializes the Extended Block data type in Pimcore admin.
 * Registers translations and hooks into the class definition editor
 * to enable adding sub-fields to ExtendedBlock via context menu.
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
     * Initializes the plugin.
     * Called when Pimcore admin is ready.
     */
    initialize: function() {
        // Add default English translations for ExtendedBlock
        this.registerTranslations();
        
        // Register ExtendedBlock as a container type in class editor context menu
        this.registerContextMenuTypes();
        
        // Log initialization in dev mode
        if (pimcore.settings && pimcore.settings.devmode) {
            console.log('Extended Block Bundle initialized');
        }
    },

    /**
     * Registers ExtendedBlock as a container type in class editor.
     * Uses the prepareClassLayoutContextMenu event to add extendedBlock
     * to the allowed types, enabling sub-fields to be added via context menu.
     */
    registerContextMenuTypes: function() {
        var _this = this;
        
        // Listen for the prepareClassLayoutContextMenu event
        document.addEventListener(pimcore.events.prepareClassLayoutContextMenu, function(e) {
            var allowedTypes = e.detail.allowedTypes;
            
            // Add extendedBlock as a container type (like block).
            // This list follows the same pattern as Pimcore's 'block' type definition.
            // 'data' allows any data component, layout types allow organizing UI elements.
            // Complex nested types (localizedfields, block, fieldcollections, objectbricks,
            // extendedBlock) are excluded via the disallowedDataTypes array and allowIn checks.
            allowedTypes.extendedBlock = ['data', 'panel', 'tabpanel', 'accordion', 
                'fieldset', 'fieldcontainer', 'text', 'region', 'button', 'iframe'];
        });

        // Also hook into data type registration to set allowIn.extendedBlock
        // This happens when each data type is being checked for allowed context
        this.patchDataTypeAllowIn();
    },

    /**
     * Patches existing data types to set allowIn.extendedBlock property.
     * This allows the class editor to know which data types can be
     * added inside ExtendedBlock.
     */
    patchDataTypeAllowIn: function() {
        var _this = this;
        
        // Wait for Pimcore to be fully loaded
        var checkAndPatch = function() {
            if (typeof pimcore !== 'undefined' && 
                typeof pimcore.object !== 'undefined' && 
                typeof pimcore.object.classes !== 'undefined' &&
                typeof pimcore.object.classes.data !== 'undefined') {
                
                var dataTypes = Object.keys(pimcore.object.classes.data);
                
                for (var i = 0; i < dataTypes.length; i++) {
                    var typeName = dataTypes[i];
                    var dataClass = pimcore.object.classes.data[typeName];
                    
                    // Skip if it's not a constructor function
                    if (typeof dataClass !== 'function' || !dataClass.prototype) {
                        continue;
                    }
                    
                    // Set allowIn.extendedBlock based on disallowed types
                    // All types are allowed except the disallowed ones
                    if (typeof dataClass.prototype.allowIn !== 'undefined') {
                        if (_this.disallowedDataTypes.indexOf(typeName) === -1) {
                            // Allow this type inside extendedBlock
                            dataClass.prototype.allowIn.extendedBlock = true;
                        } else {
                            // Disallow complex types inside extendedBlock
                            dataClass.prototype.allowIn.extendedBlock = false;
                        }
                    }
                }
                
                // Also patch the class editor to recognize extendedBlock as a container type
                _this.patchClassEditorAddDataChild();
                
                if (pimcore.settings && pimcore.settings.devmode) {
                    console.log('Extended Block: Patched data type allowIn properties');
                }
            } else {
                // Pimcore not ready yet, retry
                setTimeout(checkAndPatch, 100);
            }
        };
        
        // Start checking
        setTimeout(checkAndPatch, 100);
    },

    /**
     * Patches the class editor's addDataChild function to recognize
     * extendedBlock as a container type (like block and localizedfields).
     * This ensures the tree node is configured with leaf: false so children can be added.
     */
    patchClassEditorAddDataChild: function() {
        var _this = this;
        
        // Check if klass class exists
        if (typeof pimcore.object.classes.klass === 'undefined') {
            // Not loaded yet, try again later
            setTimeout(function() {
                _this.patchClassEditorAddDataChild();
            }, 200);
            return;
        }
        
        // Store original addDataChild function
        var originalAddDataChild = pimcore.object.classes.klass.prototype.addDataChild;
        
        if (!originalAddDataChild) {
            if (pimcore.settings && pimcore.settings.devmode) {
                console.warn('Extended Block: Could not find addDataChild to patch');
            }
            return;
        }
        
        // Patch addDataChild to include extendedBlock as a container type.
        // NOTE: We replace the entire function rather than extending it because:
        // 1. The original function is simple and stable (only handles tree node creation)
        // 2. We need to modify the internal logic (the if condition for container types)
        // 3. Calling the original would create the node twice, breaking the tree
        // This implementation mirrors Pimcore's original addDataChild logic exactly,
        // with the addition of 'extendedBlock' to the container type check.
        pimcore.object.classes.klass.prototype.addDataChild = function(type, initData, context) {
            var nodeLabel = '';
            
            if (initData) {
                if (initData.name) {
                    nodeLabel = initData.name;
                }
            }
            
            var newNode = {
                text: htmlspecialchars(nodeLabel),
                type: "data",
                leaf: true,
                iconCls: pimcore.object.classes.data[type].prototype.getIconClass()
            };
            
            // Check if type is a container type (including extendedBlock)
            if (type === "localizedfields" || type === "block" || type === "extendedBlock") {
                newNode.leaf = false;
                newNode.expanded = true;
                newNode.expandable = false;
            }
            
            newNode = this.appendChild(newNode);
            
            // Add event listeners for expand/collapse icon management
            newNode.addListener('remove', function(node, removedNode, isMove) {
                if(!node.hasChildNodes()) {
                    node.set('expandable', false);
                }
            });
            newNode.addListener('append', function(node) {
                node.set('expandable', true);
            });
            
            var editor = new pimcore.object.classes.data[type](newNode, initData);
            editor.setContext(context);
            newNode.set("editor", editor);
            
            this.expand();
            
            return newNode;
        };
        
        if (pimcore.settings && pimcore.settings.devmode) {
            console.log('Extended Block: Patched addDataChild for container support');
        }
        
        // Also patch getRestrictionsFromParent to recognize extendedBlock
        this.patchGetRestrictionsFromParent();
    },

    /**
     * Patches the class editor's getRestrictionsFromParent function to recognize
     * extendedBlock as a container type that restricts child types.
     */
    patchGetRestrictionsFromParent: function() {
        // Store original function
        var originalGetRestrictionsFromParent = pimcore.object.classes.klass.prototype.getRestrictionsFromParent;
        
        if (!originalGetRestrictionsFromParent) {
            if (pimcore.settings && pimcore.settings.devmode) {
                console.warn('Extended Block: Could not find getRestrictionsFromParent to patch');
            }
            return;
        }
        
        // Patch to include extendedBlock
        pimcore.object.classes.klass.prototype.getRestrictionsFromParent = function(node) {
            // Check if current node is a container type (including extendedBlock)
            if (node.data.editor && 
                (node.data.editor.type === 'localizedfields' || 
                 node.data.editor.type === 'block' || 
                 node.data.editor.type === 'extendedBlock')) {
                return node.data.editor.type;
            } else {
                if (node.parentNode && node.parentNode.getDepth() > 0) {
                    var parentType = this.getRestrictionsFromParent(node.parentNode);
                    if (parentType !== null) {
                        return parentType;
                    }
                }
            }
            
            return null;
        };
        
        if (pimcore.settings && pimcore.settings.devmode) {
            console.log('Extended Block: Patched getRestrictionsFromParent for restriction support');
        }
        
        // Patch getAllowedTypes to dynamically add extendedBlock allowed types
        this.patchGetAllowedTypes();
    },

    /**
     * Patches the layout helper's getAllowedTypes function to dynamically
     * populate the extendedBlock allowed types based on allowIn.extendedBlock.
     * This mirrors how Pimcore handles localizedfields and block in onTreeNodeContextmenu.
     */
    patchGetAllowedTypes: function() {
        var _this = this;
        
        // Store original function
        var originalGetAllowedTypes = pimcore.object.helpers.layout.getAllowedTypes;
        
        if (!originalGetAllowedTypes) {
            if (pimcore.settings && pimcore.settings.devmode) {
                console.warn('Extended Block: Could not find getAllowedTypes to patch');
            }
            return;
        }
        
        // Wrap getAllowedTypes to add extendedBlock allowed types after the event
        pimcore.object.helpers.layout.getAllowedTypes = function(source) {
            // Call the original function first
            var allowedTypes = originalGetAllowedTypes.call(this, source);
            
            // Ensure extendedBlock has an array (it should from our event listener)
            if (!allowedTypes.extendedBlock) {
                allowedTypes.extendedBlock = ['data', 'panel', 'tabpanel', 'accordion', 
                    'fieldset', 'fieldcontainer', 'text', 'region', 'button', 'iframe'];
            }
            
            // Now add data types based on allowIn.extendedBlock (mirroring onTreeNodeContextmenu)
            // Note: onTreeNodeContextmenu does this for localizedfields and block
            var dataComps = Object.keys(pimcore.object.classes.data);
            
            for (var i = 0; i < dataComps.length; i++) {
                var dataCompName = dataComps[i];
                if ('object' === typeof pimcore.object.classes.data[dataCompName]) {
                    continue;
                }
                var component = pimcore.object.classes.data[dataCompName];
                if (component.prototype.allowIn && component.prototype.allowIn['extendedBlock']) {
                    if (allowedTypes.extendedBlock.indexOf(dataCompName) === -1) {
                        allowedTypes.extendedBlock.push(dataCompName);
                    }
                }
            }
            
            return allowedTypes;
        };
        
        if (pimcore.settings && pimcore.settings.devmode) {
            console.log('Extended Block: Patched getAllowedTypes for extendedBlock data type support');
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
