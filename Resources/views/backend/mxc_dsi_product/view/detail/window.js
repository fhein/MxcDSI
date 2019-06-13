//

Ext.define('Shopware.apps.MxcDsiProduct.view.detail.Window', {
    extend: 'Enlight.app.Window',
    //extend: 'Shopware.window.Detail',
    alias: 'widget.mxc-dsi-product-detail-window',
    title : 'InnoCigs Product',
    height: 520,
    width: 650,

    mixins: {
        helper: 'Shopware.model.Helper'
    },


    initComponent: function() {
        var me = this;



        me.topForm = me.getFormTopPart();

        me.supplierInfoForm = me.getInfoForm();
        me.supplierInfoForm.getForm().loadRecord(this.record);

        me.associationComponents = [];

        me.items = [me.supplierInfoForm];

        //me.items.push(me.columns);

        debugger;
        //me.variantList =me.createVariants();
        //me.items.push(me.variantList);

        me.items.push( me.createFormPanel() );

        me.callParent(arguments);


    },

    createVariants: function(){

        var me = this;

        me.variantList = Ext.create('Shopware.apps.MxcDsiProduct.view.detail.list', {
            //article: me.article,
            flex: 1,
            autoScroll:true,
            margin: 10
        });

        listContainer = Ext.create('Ext.container.Container', {
            region: 'center',
            bodyPadding: 10,
            name: 'category-tab',
            plain: true,
            autoScroll:true,
            layout: {
                align: 'stretch',
                type: 'vbox'
            },
            items: [me.variantList]
        });

        return [listContainer];
    },

    getColumns: function() {
        var me = this;

        var columns = [
            {
                header: 'Name', //me.snippets.columnName,
                dataIndex: 'name',
                renderer: 'htmlEncode',
                flex: 2,
                editor: {
                    allowBlank: false
                }
            }  /*,
            {
                xtype: 'actioncolumn',
                width: 24,
                hideable: false,
                items: [
                    {
                        iconCls: 'sprite-minus-circle-frame',
                        action: 'delete',
                        cls: 'delete',
                        tooltip: me.snippets.tooltipDeleteAssignment,
                        handler: function (grid, rowIndex) {
                            var record = grid.getStore().getAt(rowIndex);

                            me.fireEvent('deleteAssignment', record, grid);
                        }
                    }
                ]
            }*/
        ];

        return columns;
    },

    createColumnPanel: function () {
        var me = this, items;

        me.viewConfig = {
            plugins: {
                ptype: 'gridviewdragdrop',
                ddGroup: 'variant-grid-dd',
                dragText: 'DRAGTEXT'//me.snippets.dragText
            }
        };

        debugger;
        me.store = me.record.getVariantsStore;

        items = me.getColumns();

        //check if more than one tab was created
        if (items.length > 1) {
            //in this case, we have to display a tab panel.
            me.tabPanel = Ext.create('Ext.tab.Panel', {
                flex: 1,
                items: items,
                listeners: {
                    tabchange: function (tabPanel, newCard, oldCard, eOpts) {
                        me.onTabChange(tabPanel, newCard, oldCard, eOpts);
                    }
                }
            });
            //otherwise, the created item would be displayed directly in the form panel.
            items = [ me.tabPanel ];
        }
        /*
                var plugins = [];
                if (me.getConfig('translationKey')) {
                    plugins.push({
                        pluginId: 'translation',
                        ptype: 'translation',
                        translationType: me.getConfig('translationKey')
                    });
                }
        */
        me.formPanel = Ext.create('Ext.form.Panel', {
            items: items,
            flex: 1,
            plugins: [{
                ptype: 'translation',
                pluginId: 'translation',
                translationType: 'product',
                translationMerge: false,
                translationKey: null
            }],
            defaults: {
                cls: 'shopware-form'
            },
            layout: {
                type: 'hbox',
                align: 'stretch'
            }
        });

        return me.formPanel;
    },

    createFormPanel: function () {
        var me = this, items;

        items = me.createTabItems();

        //check if more than one tab was created
        if (items.length > 1) {
            //in this case, we have to display a tab panel.
            me.tabPanel = Ext.create('Ext.tab.Panel', {
                flex: 1,
                items: items,
                listeners: {
                    tabchange: function (tabPanel, newCard, oldCard, eOpts) {
                        me.onTabChange(tabPanel, newCard, oldCard, eOpts);
                    }
                }
            });
            //otherwise, the created item would be displayed directly in the form panel.
            items = [ me.tabPanel ];
        }
/*
        var plugins = [];
        if (me.getConfig('translationKey')) {
            plugins.push({
                pluginId: 'translation',
                ptype: 'translation',
                translationType: me.getConfig('translationKey')
            });
        }
*/
        me.formPanel = Ext.create('Ext.form.Panel', {
            items: items,
            flex: 1,
            plugins: [{
                ptype: 'translation',
                pluginId: 'translation',
                translationType: 'product',
                translationMerge: false,
                translationKey: null
            }],
            defaults: {
                cls: 'shopware-form'
            },
            layout: {
                type: 'hbox',
                align: 'stretch'
            }
        });

        return me.formPanel;
    },

    createTabItems: function () {
        var me = this, item, items = [];

     /*   if (!me.fireEvent(me.getEventName('before-create-tab-items'), me, items)) {
            return [];
        }*/
     debugger;

        Ext.each(me.getTabItemsAssociations(), function (association) {
            item = me.createTabItem(association);
            if (item) items.push(item);
        });

     //   me.fireEvent(me.getEventName('after-create-tab-items'), me, items);

        return items;
    },
    createTabItem: function (association) {
        var me = this, item;

        /*if (!me.fireEvent(me.getEventName('before-create-tab-item'), me, association)) {
            return false;
        }*/

        if (association.isBaseRecord) {
            item = me.createAssociationComponent('detail', me.record, null, null, me.record);
        } else {
            item = me.createAssociationComponent(
                me.getComponentTypeOfAssociation(association),
                Ext.create(association.associatedName),
                me.getAssociationStore(me.record, association),
                association,
                me.record
            );
        }
        me.associationComponents[association.associationKey] = item;

        //me.fireEvent(me.getEventName('after-create-tab-item'), me, association, item);

        if (item.title === undefined) {
            item.title = me.getModelName(association.associatedName);
        }

        return item;
    },

    getConfig: function (prop) {
        var me = this;
        return me._opts[prop];
    },

    getTabItemsAssociations: function () {
        var me = this, associations, config = [];

        associations = me.getAssociations(me.record.$className, [
            { associationKey: config }
        ]);

        associations = Ext.Array.insert(associations, 0, [
            {  isBaseRecord: true, associationKey: 'baseRecord' }
        ]);

        return associations;
    },


    getInfoForm : function()
    {
        var me = this;

        me.formPanel = Ext.create('Ext.form.Panel', {
            collapsible : false,
            split       : false,
            region      : 'center',
            width       : '100%',
            id          : 'productFormPanel',
            defaults : {
                anchor      : '100%'
            },
            bodyPadding : 10,
            border : 0,
            autoScroll: true,
            plugins: [{
                ptype: 'translation',
                pluginId: 'translation',
                translationType: 'product',
                translationMerge: false,
                translationKey: null
            }],
            items : [
                Ext.create('Ext.form.FieldSet', {
                    alias:'widget.product-base-field-set',
                    //cls: Ext.baseCSSPrefix + 'supplier-base-field-set',
                    title : '{s name=panel_base}Product information{/s}',
                    layout: 'form',
                    items : [
                        {
                            xtype : 'container',
                            layout : 'column',
                            border : 1,
                            items : [
                                {
                                    xtype       : 'container',
                                    layout      : 'anchor',
                                    columnWidth : 0.8,
                                    defaults : {
                                        labelWidth  : 155
                                    },
                                    items : [me.topForm]
                                }
                            ]
                        }
                    ]
                })
            ]
        });

        /*me.attributeForm = Ext.create('Shopware.attribute.Form', {
            table: 's_articles_supplier_attributes',
            allowTranslation: false,
            translationForm: me.formPanel
        });*/

        //me.formPanel.add(me.attributeForm);
        return me.formPanel;
    },
    createAssociationComponent: function(type, model, store, association, baseRecord) {
        var me = this, component = { };

        if (!(model instanceof Shopware.data.Model)) {
            me.throwException(model.$className + ' has to be an instance of Shopware.data.Model');
        }
        if (baseRecord && !(baseRecord instanceof Shopware.data.Model)) {
            me.throwException(baseRecord.$className + ' has to be an instance of Shopware.data.Model');
        }

        var componentType = model.getConfig(type);

        /*if (!me.fireEvent(me.getEventName('before-association-component-created'), me, component, type, model, store)) {
            return component;
        }*/

        component = Ext.create(componentType, {
            record: model,
            store: store,
            flex: 1,
            subApp: this.subApp,
            association: association,
            configure: function() {
                debugger;
                var config = { };

                if (association) {
                    config.associationKey = association.associationKey;
                }

                if (baseRecord && baseRecord.getConfig('controller')) {
                    config.controller = baseRecord.getConfig('controller');
                }

                return config;
            }
        });

        //add lazy loading event listener.
        component.on('viewready', function() {
            if (me.isLazyLoadingComponent(component)) {
                if (!(me.fireEvent(me.getEventName('before-load-lazy-loading-component'), me, component))) {
                    return true;
                }

                component.getStore().load({
                    callback: function(records, operation) {
                        me.fireEvent(me.getEventName('after-load-lazy-loading-component'), me, component, records, operation);
                    }
                });
            }
        });

        //me.fireEvent(me.getEventName('after-association-component-created'), me, component, type, model, store);


        return component;
    },

    getFormTopPart : function() {

        return [
            {
                xtype : 'textfield',
                name : 'number',
                allowBlank  : false,
                anchor : '95%',
                fieldLabel  : '{s name=number}Number{/s}',
                supportText : '{s name=number_support}Number of the product{/s}'
            },{
                xtype : 'textfield',
                name : 'name',
                allowBlank  : false,
                anchor : '95%',
                fieldLabel  : '{s name=name}Name{/s}',
                supportText : '{s name=name_support}Name of the product e.g. Innocigs Liquid{/s}'
            },{
                xtype: 'tinymce',
                name: 'description',
                margin: '0 0 15',
                height: 100
            }
        ];
    }
});
