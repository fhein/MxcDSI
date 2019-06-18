//

Ext.define('Shopware.apps.MxcDsiProduct.view.list.Product', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-product-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiProduct.view.detail.Window',
            columns: {
                new:                        { header: 'new', width: 40, flex: 0 },
                linked:                     { header: 'linked', width: 40, flex: 0 },
                active:                     { header: 'active', width: 40, flex: 0 },
                number:                     { header: 'Number'},
                manufacturer:               { header: 'Manufacturer' },
                supplier:                   { header: 'Supplier'},
                brand:                      { header: 'Brand'},
                type:                       { header: 'Type'},
                commonName:                 { header: 'Common' },
                category:                   { header: 'Category'},
                name:                       { header: 'Name', flex: 3 },
                flavor:                     { header: 'Flavor', flex: 3 },
                accepted:                   { header: 'accept', width:45, flex: 0}
            },
            addButton: false,
            deleteButton: false,
            deleteColumn: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            'mxcBuildCategoryTree',
            'mxcSaveProduct',
            'mxcRemapProperties',
            'mxcPullShopwareDescriptions',
            'mxcPullShopwareDescriptionsSelected',
            'mxcRemapPropertiesSelected',
            'mxcUpdateImages',
            'mxcUpdateImagesSelected',
            'mxcUpdateCategories',
            'mxcUpdateCategoriesSelected',
            'mxcRemoveEmptyCategories',
            'mxcDeleteAll',
            'mxcCreateAll',
            'mxcRefreshAssociated',
            'mxcSetActiveSelected',
            'mxcSetLinkedSelected',
            'mxcSetAcceptedSelected',
            'mxcCreateRelatedSelected',
            'mxcCreateSimilarSelected',
            'mxcImportItems',
            'mxcRefreshItems',
            'mxcCheckRegularExpressions',
            'mxcCheckNameMappingConsistency',
            'mxcCheckVariantMappingConsistency',
            'mxcExportConfig',
            'mxcExcelExport',
            'mxcExcelImport',
            'mxcExcelImportFlavors',
            'mxcExcelImportDosages',
            'mxcExcelImportMappings',
            'mxcExcelImportPrices',
            'mxcExcelImportDescriptions',

            'mxcTestImport1',
            'mxcTestImport2',
            'mxcTestImport3',
            'mxcTestImport4',

            'mxcDev1',
            'mxcDev2',
            'mxcDev3',
            'mxcDev4',
            'mxcDev5',
            'mxcDev6',
            'mxcDev7',
            'mxcDev8',
        );
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            me.createActionsButton(),
            me.createExcelButton(),
            me.createSelectionButton(),
            me.createAllButton(),
            me.createToolsButton(),
            me.createTestButton(),
            me.createDevButton()
        ]);
        return items;
    },

    handleLinkedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            // deselect records which already have the target states
            // set the target state otherwise
            if (record.get('linked') === changeTo || record.get('accepted') === false) {
                selModel.deselect(record);
            } else {
                record.set('linked', changeTo);
            }
        });
        if (selModel.getCount() > 0) {
            me.fireEvent('mxcSetLinkedSelected', me);
        }
    },

    handleActiveState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            // deselect records which already have the target states
            // set the target state otherwise
            if (record.get('active') === changeTo || record.get('accepted') === false) {
                selModel.deselect(record);
            } else {
                record.set('active', changeTo);
            }
        });
        if (selModel.getCount() > 0) {
            me.fireEvent('mxcSetActiveSelected', me);
        }
    },

    handleAcceptedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            // deselect records which already have the target states
            // set the target state otherwise
            if (record.get('accepted') === changeTo) {
                selModel.deselect(record);
            } else {
                record.set('accepted', changeTo)
            }
        });
        if (selModel.getCount() > 0) {
            me.fireEvent('mxcSetAcceptedSelected', me);
        }
    },

    createSelectionButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'MxcDsiProductSelectionMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text : 'Create Shopware articles',
                    iconCls: 'sprite-plus-circle',
                    handler: function() {
                        me.handleLinkedState(true);
                    }
                },
                {
                    text : 'Delete Shopware articles',
                    iconCls: 'sprite-minus-circle',
                    handler: function() {
                        me.handleLinkedState(false);
                    }
                },
                '-',
                {
                    text : 'Activate selected',
                    iconCls: 'sprite-tick',
                    handler: function() {
                        me.handleActiveState(true);
                    }
                },
                {
                    text : 'Deactivate selected',
                    iconCls: 'sprite-cross',
                    handler: function() {
                        me.handleActiveState(false);
                    }
                },
                '-',
                {
                    text: 'Accept selected',
                    iconCls: 'sprite-tick-circle',
                    handler: function() {
                        me.handleAcceptedState(true);
                    }
                },
                {
                    text: 'Ignore selected',
                    iconCls: 'sprite-cross-circle',
                    handler: function() {
                        me.handleAcceptedState(false);
                    }
                },
                '-',
                {
                    text: 'Create related articles',
                    handler: function() {
                        me.fireEvent('mxcCreateRelatedSelected', me);
                    }
                },
                {
                    text: 'Create similar articles',
                    handler: function() {
                        me.fireEvent('mxcCreateSimilarSelected', me);
                    }
                },
                '-',
                {
                    text: 'Remap properties',
                    iconCls: 'sprite-maps',
                    handler: function() {
                        me.fireEvent('mxcRemapPropertiesSelected', me);
                    }
                },
                {
                    text : 'Pull Shopware descriptions',
                    iconCls: 'sprite-blue-document-horizontal-text',
                    handler: function() {
                        me.fireEvent('mxcPullShopwareDescriptionsSelected', me);
                    }

                },

                '-',
                {
                    text: 'Update images',
                    iconCls: 'sprite-images-stack',
                    handler: function() {
                        me.fireEvent('mxcUpdateImagesSelected', me);
                    }
                },
                {
                    text: 'Update categories',
                    iconCls: 'sprite-category',
                    handler: function() {
                        me.fireEvent('mxcUpdateCategoriesSelected', me);
                    }
                },
            ]
        });
        me.selectionButton = Ext.create('Ext.button.Button', {
            text: 'Selected products',
            disabled: true,
            iconCls: 'sprite-ui-check-box',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
        return me.selectionButton;
    },

    createAllButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'MxcDsiProductAllMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text : 'Create Shopware articles',
                    iconCls: 'sprite-plus-circle',
                    handler: function() {
                        me.fireEvent('mxcCreateAll', me);
                    }
                },
                {
                    text : 'Delete Shopware articles',
                    iconCls: 'sprite-minus-circle',
                    handler: function() {
                        me.fireEvent('mxcDeleteAll', me);
                    }
                },
                '-',
                {
                    text: 'Refresh link state',
                    iconCls: 'sprite-arrow-circle',
                    handler: function() {
                        me.fireEvent('mxcRefreshItems', me);
                    }
                },
                '-',
                {
                    text: 'Remap properties',
                    iconCls: 'sprite-maps',
                    handler: function() {
                        me.fireEvent('mxcRemapProperties', me);
                    }
                },
                {
                    text : 'Pull Shopware descriptions',
                    iconCls: 'sprite-blue-document-horizontal-text',
                    handler: function() {
                        me.fireEvent('mxcPullShopwareDescriptions', me);
                    }

                },
                '-',
                {
                    text: 'Refresh associated products',
                    iconCls: 'sprite-tables-relation',
                    handler: function() {
                        me.fireEvent('mxcRefreshAssociated', me);
                    }
                },
                '-',
                {
                    text: 'Update images',
                    iconCls: 'sprite-images-stack',
                    handler: function() {
                        me.fireEvent('mxcUpdateImages', me);
                    }
                },
                {
                    text: 'Update categories',
                    iconCls: 'sprite-category',
                    handler: function() {
                        me.fireEvent('mxcUpdateCategories', me);
                    }
                },
            ]
        });
        me.allButton = Ext.create('Ext.button.Button', {
            text: 'All products',
            iconCls: 'sprite-duplicate-article',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
        return me.allButton;
    },

    createToolsButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
          id: 'mxcDsiToolsMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text : 'Check regular expressions',
                    handler: function() {
                        me.fireEvent('mxcCheckRegularExpressions', me);
                    }
                },
                '-',
                {
                    text : 'Check name mapping consistency',
                    handler: function() {
                        me.fireEvent('mxcCheckNameMappingConsistency', me);
                    }
                },
                '-',
                {
                    text : 'Check variant mapping consistency',
                    handler: function() {
                        me.fireEvent('mxcCheckVariantMappingConsistency', me);
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Checks',
            iconCls: 'sprite-wrench-screwdriver',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
    },

    createExcelButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiExcelMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text: 'Excel Export',
                    iconCls: 'sprite-table-import',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/excelExport');
                    }
                },
                {
                    text: 'Excel Import',
                    listeners: {
                        click: function (event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                }
                ,
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId: 'mxcDsiExcelImportField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function (fileSelection) {
                            me.fireEvent('mxcExcelImport', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender: function (cmp) {
                            cmp.fileInputEl.set({
                                accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
                '-',
                {
                    text: 'Import prices only',
                    listeners: {
                        click: function(event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportPricesField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                },
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId:'mxcDsiExcelImportPricesField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function(fileSelection) {
                            me.fireEvent('mxcExcelImportPrices', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender:function(cmp){
                            cmp.fileInputEl.set({
                                accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
                {
                    text: 'Import descriptions only',
                    listeners: {
                        click: function(event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportDescriptionsField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                }
                ,
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId:'mxcDsiExcelImportDescriptionsField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function(fileSelection) {
                            me.fireEvent('mxcExcelImportDescriptions', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender:function(cmp){
                            cmp.fileInputEl.set({
                                accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
                {
                    text: 'Import flavors only',
                    listeners: {
                        click: function(event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportFlavorsField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                }
                ,
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId:'mxcDsiExcelImportFlavorsField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function(fileSelection) {
                            me.fireEvent('mxcExcelImportFlavors', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender:function(cmp){
                            cmp.fileInputEl.set({
                                accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
                {
                    text: 'Import dosages only',
                    listeners: {
                        click: function(event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportDosagesField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                }
                ,
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId:'mxcDsiExcelImportDosagesField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function(fileSelection) {
                            me.fireEvent('mxcExcelImportDosages', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender:function(cmp){
                            cmp.fileInputEl.set({
                                accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
                {
                    text: 'Import mappings only',
                    listeners: {
                        click: function(event) {
                            var filefield = Ext.ComponentQuery.query('#mxcDsiExcelImportMappingsField');
                            var button = filefield[0].el.query('input[type=file]');
                            button[0].click();
                        }

                    }
                }
                ,
                {
                    xtype: 'filefield',
                    name: 'excelFile',
                    itemId:'mxcDsiExcelImportMappingsField',
                    accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    hidden: true,

                    listeners: {
                        scope: me,
                        change: function(fileSelection) {
                            me.fireEvent('mxcExcelImportMappings', me, fileSelection.fileInputEl.dom.files[0]);
                        },
                        afterrender:function(cmp){
                            cmp.fileInputEl.set({
                                accept:'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                            });
                        }
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Excel',
            iconCls: 'sprite-table-excel',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });

    },

    createActionsButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiActionsMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text: 'Import/Update',
                    iconCls: 'sprite-download-cloud',
                    handler: function() {
                        me.fireEvent('mxcImportItems', me);
                    }
                },
                '-',
                {
                    text : 'Export product configuration',
                    iconCls: 'sprite-document-export',
                    handler: function() {
                        me.fireEvent('mxcExportConfig', me);
                    }
                },
                '-',
                {
                    text: 'Rebuild category positions',
                    iconCls: 'sprite-folder-tree',
                    handler: function() {
                        me.fireEvent('mxcBuildCategoryTree', me);
                    }
                },
                {
                    text: 'Remove empty categories',
                    iconCls: 'sprite-bin-metal-full',
                    handler: function() {
                        me.fireEvent('mxcRemoveEmptyCategories', me);
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Actions',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
    },

    createTestButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiTestMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text: 'Erstimport',
                    handler: function() {
                        me.fireEvent('mxcTestImport1', me);
                    }
                },
                '-',
                {
                    text: 'Update Feldwerte',
                    handler: function() {
                        me.fireEvent('mxcTestImport2', me);
                    }
                },
                {
                    text: 'Update Varianten',
                    handler: function() {
                        me.fireEvent('mxcTestImport3', me);
                    }
                },
                {
                    text: 'Empty product list',
                    handler: function() {
                        me.fireEvent('mxcTestImport4', me);
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Tests',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
    },

    createDevButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiDevelopmentMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text: 'Dev #1',
                    handler: function() {
                        me.fireEvent('mxcDev1', me);
                    }
                },
                {
                    text: 'Dev #2',
                    handler: function() {
                        me.fireEvent('mxcDev2', me);
                    }
                },
                {
                    text: 'Dev #3',
                    handler: function() {
                        me.fireEvent('mxcDev3', me);
                    }
                },
                {
                    text: 'Dev #4',
                    handler: function() {
                        me.fireEvent('mxcDev4', me);
                    }
                },
                '-',
                {
                    text: 'Dev #5 on selected',
                    handler: function() {
                        me.fireEvent('mxcDev5', me);
                    }
                },
                {
                    text: 'Dev #6 on selected',
                    handler: function() {
                        me.fireEvent('mxcDev6', me);
                    }
                },
                {
                    text: 'Dev #7 on selected',
                    handler: function() {
                        me.fireEvent('mxcDev7', me);
                    }
                },
                {
                    text: 'Dev #8 on selected',
                    handler: function() {
                        me.fireEvent('mxcDev8', me);
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Development',
            menu: menu,
            listeners: {
                'mouseover': function() {
                    this.showMenu();
                }
            }
        });
    },

    createPlugins: function () {
        let me = this;
        let items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, e) {
                    let header = e.column.text;
                    if (header === 'active' || header === 'linked') {
                        return e.record.get('accepted') === true;
                    }
                    return (
                        header === 'Brand'
                        || header === 'Supplier'
                        || header === 'Flavor'
                        || header === 'new'
                        || header === 'related'
                        || header === 'similar'
                        || header === 'accept'
                    );
                },
                edit: function(editor, e) {
                    // the 'edit' event gets fired even if the new value equals the old value
                    if (e.originalValue === e.value) {
                        return;
                    }
                    me.fireEvent('mxcSaveProduct', e.record);
                }
            }
        });
        items.push(me.cellEditor);

        return items;
    },

    onSelectionChange: function(selModel, selection) {
        let me = this;
        me.selectionButton.setDisabled(selection.length === 0);
    },

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }

});
