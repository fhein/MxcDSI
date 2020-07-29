//

Ext.define('Shopware.apps.MxcDsiProduct.view.list.Product', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-product-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiProduct.view.detail.Window',
            columns: {
                // created:                    { header: 'created', width: 80, flex: 0, renderer: this.dateRenderer},
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
                flavor:                     { header: 'Flavor' },
                content:                    { header: 'Content' },
                capacity:                   { header: 'Capacity' },
                dosage:                     { header: 'Dosage' },
                cellCount:                  { header: '# Cells'},
                headChangeable:             { header: 'Head Changeable'},
                cellCapacity:               { header: 'Cell Capacity'},
                power:                      { header: 'Power'},
                releaseDate:                { header: 'Release'},
                accepted:                   { header: 'accept', width:45, flex: 0}
            },
            addButton: false,
            deleteButton: false,
            deleteColumn: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.addEvents(
          'mxcUpdateSchema',

          'mxcUpdateCategorySeo',
          'mxcSaveCategorySeo',
          'mxcRebuildArticleSeo',
          'mxcUpdateArticleSeo',
          'mxcUpdateSupplierSeo',
          'mxcSaveProduct',
          'mxcRelink',
          'mxcSetLastStock',

          'mxcRemapProperties',
          'mxcRemapDescriptions',
          'mxcRemapCategories',
          'mxcComputeCategories',
          'mxcSetReferencePrices',
          'mxcCheckSupplierLogo',
          'mxcPullAssociatedProducts',
          'mxcPushAssociatedProducts',
          'mxcComputeAssociatedProducts',
          'mxcUpdateAssociatedLiguids',

          'mxcUpdateImages',
          'mxcPullShopwareDescriptions',

          'mxcSaveEmailTemplates',
          'mxcRestoreEmailTemplates',

          'mxcUpdateStockInfo',
          'mxcRemoveEmptyCategories',
          'mxcDeleteAll',
          'mxcCreateAll',
          'mxcSetActiveSelected',
          'mxcSetLinkedSelected',
          'mxcSetAcceptedSelected',
          'mxcImportItems',
          'mxcImportItemsSequential',
          'mxcUpdatePrices',
          'mxcUpdateVat',
          'mxcRefreshItems',

          'mxcCheckRegularExpressions',
          'mxcCheckMissingModels',
          'mxcCheckNameMappingConsistency',
          'mxcCheckVariantMappingConsistency',
          'mxcCheckVariantsWithoutOptions',
          'mxcCheckArticlesWithoutProducts',

          'mxcExportConfig',
          'mxcExcelExportPrices',
          'mxcExcelExportPriceIssues',
          'mxcExcelExportEcigMetaData',
          'mxcExcelImportPrices',

          'mxcCsvExportCustomers',
          'mxcArrayExportDocumentationTodos',

          'mxcTestImport1',
          'mxcTestImport2',
          'mxcTestImport3',
          'mxcTestImport4',
          'mxcTestImport5',
          'mxcTestImport6',

          'mxcDev1',
          'mxcDev2',
          'mxcDev3',
          'mxcDev4',
          'mxcDev5',
          'mxcDev6',
          'mxcDev7',
          'mxcDev8',
        );
        me.callParent(arguments);
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            me.createActionsButton(),
            me.createExcelButton(),
            me.createProductsButton(),
            //me.createSelectionButton(),
            me.createToolsButton(),
            me.createTestButton(),
            me.createDevButton(),
            me.createAllButton()
        ]);
        return items;
    },

    handleRelink: function() {
        let me = this;
        let selModel = me.getSelectionModel();
        if (selModel.getCount() < 1) {
            Ext.MessageBox.alert('Selection', 'No products selected.');
            return;
        }

        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            // deselect records which already have the target states
            // set the target state otherwise
            if (record.get('linked') === false || record.get('accepted') === false) {
                selModel.deselect(record);
            } else {
                record.set('linked', true);
            }
        });
        if (selModel.getCount() > 0) {
            me.fireEvent('mxcRelink', me);
        } else {
            Ext.MessageBox.alert('Selection', 'Selected products do not have shopware products assoiciated.');
        }
    },

    handleLinkedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        if (selModel.getCount() < 1) {
            Ext.MessageBox.alert('Selection', 'No products selected.');
            return;
        }

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
        } else {
            Ext.MessageBox.alert('Selection', 'Nothing to do on selection.');
        }
    },

    handleActiveState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        if (selModel.getCount() < 1) {
            Ext.MessageBox.alert('Selection', 'No products selected.');
            return;
        }
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
        } else {
            Ext.MessageBox.alert('Selection', 'Nothing to do on selection.');
        }
    },

    handleAcceptedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        if (selModel.getCount() < 1) {
            Ext.MessageBox.alert('Selection', 'No products selected.');
            return;
        }
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
        } else {
            Ext.MessageBox.alert('Selection', 'Nothing to do on selection.');
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
                {
                    text : 'Look for variants without options',
                    handler: function() {
                        me.fireEvent('mxcCheckVariantsWithoutOptions', me);
                    }
                },
                {
                    text : 'Look for variants without model',
                    handler: function() {
                        me.fireEvent('mxcCheckMissingModels', me);
                    }
                },
                '-',
                {
                    text : 'Find articles without product',
                    handler: function() {
                        me.fireEvent('mxcCheckArticlesWithoutProducts', me);
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
                    text: 'Export prices',
                    iconCls: 'sprite-table-export',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/excelExportPrices');
                    }
                },
                {
                    text: 'Export price issues',
                    iconCls: 'sprite-table-export',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/excelExportPriceIssues');
                    }
                },
                {
                    text: 'Export ecig meta data',
                    iconCls: 'sprite-table-export',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/excelExportEcigMetaData');
                    }
                },
                '-',
                {
                    text: 'CSV Export Customers',
                    iconCls: 'sprite-table-export',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/csvExportCustomers');
                    }
                },
                '-',
                {
                    text: 'Export Documentation Todos',
                    iconCls: 'sprite-table-export',
                    handler: function () {
                        window.open('/backend/MxcDsiProduct/arrayExportDocumentationTodos');
                    }
                },
                '-',
                me.createImportMenuItem('Import prices', 'mxcExcelImportPrices'),
                me.createImportFileField('mxcExcelImportPrices', me),
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

    createProductsButton: function() {
        let me = this;

        let menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiProductMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text : 'Create Shopware articles from selected',
                    iconCls: 'sprite-plus-circle',
                    handler: function() {
                        me.handleLinkedState(true);
                    }
                },
                {
                    text : 'Delete Shopware articles from selected',
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
                    text: 'Remap properties',
                    iconCls: 'sprite-maps',
                    handler: function() {
                        me.fireEvent('mxcRemapProperties', me);
                    }
                },
                {
                    text: 'Remap descriptions',
                    iconCls: 'sprite-maps',
                    handler: function() {
                        me.fireEvent('mxcRemapDescriptions', me);
                    }
                },
                '-',
                {
                    text: 'Compute categories',
                    iconCls: 'sprite-category',
                    handler: function() {
                        me.fireEvent('mxcComputeCategories', me);
                    }
                },
                {
                    text: 'Remap categories',
                    iconCls: 'sprite-category',
                    handler: function() {
                        me.fireEvent('mxcRemapCategories', me);
                    }
                },
                '-',
                {
                    text : 'Pull Shopware descriptions',
                    iconCls: 'sprite-blue-document-horizontal-text',
                    handler: function() {
                        me.fireEvent('mxcPullShopwareDescriptions', me);
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
                '-',
                {
                    text: 'Rebuild article SEO items',
                    handler: function() {
                        me.fireEvent('mxcRebuildArticleSeo', me);
                    }
                },
                {
                    text: 'Update article SEO items',
                    handler: function() {
                        me.fireEvent('mxcUpdateArticleSeo', me);
                    }
                },

                '-',
                {
                    text : 'Recreate Shopware articles',
                    handler: function() {
                        me.handleRelink();
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Products',
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
                {
                    text: 'Import/Update Sequential',
                    iconCls: 'sprite-download-cloud',
                    handler: function() {
                        me.fireEvent('mxcImportItemsSequential', me);
                    }
                },
                '-',
                {
                    text: 'Update InnoCigs stock info',
                    iconCls: 'sprite-money--arrow',
                    handler: function() {
                        me.fireEvent('mxcUpdateStockInfo', me);
                    }
                },
                {
                    text: 'Update InnoCigs prices',
                    iconCls: 'sprite-money--arrow',
                    handler: function() {
                        me.fireEvent('mxcUpdatePrices', me);
                    }
                },
                {
                    text: 'Update VAT',
                    iconCls: 'sprite-money--arrow',
                    handler: function() {
                        me.fireEvent('mxcUpdateVat', me);
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
                {
                    text : 'Pull associated products',
                    iconCls: 'sprite-document-export',
                    handler: function() {
                        me.fireEvent('mxcPullAssociatedProducts', me);
                    }
                },
                {
                    text : 'Push associated products',
                    iconCls: 'sprite-document-export',
                    handler: function() {
                        me.fireEvent('mxcPushAssociatedProducts', me);
                    }
                },
                '-',
                {
                    text: 'Update supplier SEO items',
                    iconCls: 'sprite-building-medium',
                    handler: function() {
                        me.fireEvent('mxcUpdateSupplierSeo', me);
                    }
                },
                {
                    text: 'Update category SEO items',
                    iconCls: 'sprite-folder-tree',
                    handler: function() {
                        me.fireEvent('mxcUpdateCategorySeo', me);
                    }
                },
                {
                    text: 'Save category SEO items',
                    iconCls: 'sprite-folder-tree',
                    handler: function() {
                        me.fireEvent('mxcSaveCategorySeo', me);
                    }
                },
                {
                    text: 'Remove empty categories',
                    iconCls: 'sprite-bin-metal-full',
                    handler: function() {
                        me.fireEvent('mxcRemoveEmptyCategories', me);
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
                    text: 'Set reference prices',
                    handler: function() {
                        me.fireEvent('mxcSetReferencePrices', me);
                    }
                },
                {
                    text: 'Get suppliers without Logo',
                    handler: function() {
                        me.fireEvent('mxcCheckSupplierLogo', me);
                    }
                },
                {
                    text: 'Globally set laststock',
                    handler: function() {
                        me.fireEvent('mxcSetLastStock', me);
                    }
                },
                {
                    text: 'Compute associated products',
                    handler: function() {
                        me.fireEvent('mxcComputeAssociatedProducts', me);
                    }
                },
                {
                    text: 'Update associated products for liquids',
                    handler: function() {
                        me.fireEvent('mxcUpdateAssociatedLiguids', me);
                    }
                },
                '-',
                {
                    text: 'Save email templates',
                    handler: function() {
                        me.fireEvent('mxcSaveEmailTemplates', me);
                    }
                },
                {
                    text: 'Restore email templates',
                    handler: function() {
                        me.fireEvent('mxcRestoreEmailTemplates', me);
                    }
                },
                '-',
                {
                    text: 'Update database schema',
                    handler: function() {
                        me.fireEvent('mxcUpdateSchema', me);
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
                '-',
                {
                    text: 'Import Huge File Sequential',
                    handler: function() {
                        me.fireEvent('mxcTestImport5', me);
                    }
                },
                {
                    text: 'Import Huge File',
                    handler: function() {
                        me.fireEvent('mxcTestImport6', me);
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
                        // editable columns
                        header === 'Brand'
                        || header === 'Supplier'
                        || header === 'Cell Changeable'
                        || header === 'Head Changeable'
                        || header === 'Cell Capacity'
                        || header === 'Type'
                        || header === 'Common'
                        || header ===  'Release'

                        || header === '# Cells'
                        || header === 'Power'
                        || header === 'Flavor'
                        || header === '+ Category'
                        || header === 'Capacity'
                        || header === 'Content'
                        || header === 'Dosage'
                        || header === 'new'
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

    createImportMenuItem: function(menuText, eventName){ //, scope) {
        return {
            text: menuText, //'Excel Import',
            iconCls: 'sprite-table-import',
            listeners: {
                click: function (event) {
                    var filefield = Ext.ComponentQuery.query('#'+eventName+'Field');
                    var button = filefield[0].el.query('input[type=file]');
                    button[0].click();
                }

            }
        };
    },

    createImportFileField: function(eventName, scope){
        return {
            xtype: 'filefield',
            name: 'excelFile',
            itemId: eventName + 'Field',
            accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            hidden: true,

            listeners: {
                scope: scope,
                change: function (fileSelection) {
                    if (fileSelection !== '') {
                        scope.fireEvent(eventName, scope, fileSelection.fileInputEl.dom.files[0]);

                        //clear filefield
                        fileSelection.fileInputEl.dom.value = '';
                    }
                },
                afterrender: function (cmp) {
                    cmp.fileInputEl.set({
                        accept: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                    });
                }
            }
        };
    },

    onSelectionChange: function(selModel, selection) {
        let me = this;
//        me.selectionButton.setDisabled(selection.length === 0);
    },

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }

});
