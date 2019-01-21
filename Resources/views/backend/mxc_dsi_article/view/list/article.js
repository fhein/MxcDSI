//

Ext.define('Shopware.apps.MxcDsiArticle.view.list.Article', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-article-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiArticle.view.detail.Window',
            columns: {
                active:     { header: 'active', width: 40, flex: 0 },
                code:       { header: 'Code'},
                supplier:   { header: 'Supplier'},
                brand:      { header: 'Brand'},
                manufacturer: { header: 'Manufacturer' },
                category:   { header: 'Category'},
                name:       { header: 'Name', flex: 3 },
                accepted:   { header: 'accept', width:45, flex: 0}
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
            /**
             * @event mxcSaveArticle
             */
            'mxcSaveArticle',
            /**
             * @event mxcSaveMultiple
             */
            'mxcSaveMultiple',
            /**
             * @event mxcApplyFilter
             */
            'mxcApplyFilter',
            /**
             * @event mxcImportItems
             */
            'mxcImportItems'
        );
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            // me.createImportItemsButton(),
            me.createFilterButton(),
            me.createAcceptButton(),
            me.createIgnoreButton(),
            me.createActivateButton(),
            me.createDeactivateButton()
        ]);
        return items;
    },

    handleActiveStateChanges: function(changeTo) {
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
        me.fireEvent('mxcSaveMultiple', me, selModel);
    },

    handleAcceptedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            // deselect records which already have the target states
            // set the target state otherwise
            if (record.get('accepted') === changeTo || (changeTo === false && record.get('active') === true)) {
                selModel.deselect(record);
            } else {
                record.set('accepted', changeTo)
            }
        });
        me.fireEvent('mxcSaveMultiple', me, selModel);
    },

    createActivateButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Activate selected',
            iconCls: 'sprite-tick',
            handler: function() {
                me.handleActiveStateChanges(true);
            }
        });
    },

    createDeactivateButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Deactivate selected',
            iconCls: 'sprite-cross',
            handler: function() {
                me.handleActiveStateChanges(false);
            }
        });
    },

    createFilterButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Apply filter',
            iconCls: 'sprite-filter',
            handler: function() {
                me.fireEvent('mxcApplyFilter', me);
            }
        });
    },

    createImportItemsButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Import Articles',
            iconCls: 'sprite-download-cloud',
            handler: function() {
                me.fireEvent('mxcImportItems', me);
            }
        });
    },

    createIgnoreButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Ignore selected',
            iconCls: 'sprite-cross-circle',
            handler: function() {
                me.handleAcceptedState(false);
            }
        });
    },

    createAcceptButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Accept selected',
            iconCls: 'sprite-tick-circle',
            handler: function() {
                me.handleAcceptedState(true);
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
                    if (e.column.text === 'active') {
                        return e.record.get('accepted') === true;
                    } else if (e.column.text === 'accept') {
                        return e.record.get('active') === false;
                    }
                    return (e.column.text === 'Brand' || e.column.text === 'Supplier');
                },
                edit: function(editor, e) {
                    // the 'edit' event gets fired even if the new value equals the old value
                    if (e.originalValue === e.value) {
                        return;
                    }
                    me.fireEvent('mxcSaveArticle', e.record);
                }
            }
        });
        items.push(me.cellEditor);

        return items;
    },

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }
});
