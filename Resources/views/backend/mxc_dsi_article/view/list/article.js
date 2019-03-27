//

Ext.define('Shopware.apps.MxcDsiArticle.view.list.Article', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-article-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiArticle.view.detail.Window',
            columns: {
                new:                        { header: 'new', width: 40, flex: 0 },
                active:                     { header: 'active', width: 40, flex: 0 },
                createRelatedArticles:      { header: 'related', width: 50, flex: 0 },
                createSimilarArticles:      { header: 'similar', width: 50, flex: 0 },
                number:                     { header: 'Number'},
                manufacturer:               { header: 'Manufacturer' },
                supplier:                   { header: 'Supplier'},
                brand:                      { header: 'Brand'},
                type:                       { header: 'Type'},
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
            /** @event mxcSaveArticle */
            'mxcSaveArticle',

            /** @event mxcRemapProperties */
            'mxcRemapProperties',

            /** @event mxcSetActiveMultiple */
            'mxcSetActiveMultiple',

            /** @event mxcSetActiveMultiple */
            'mxcSetAcceptedMultiple',

            /** @event mxcImportItems */
            'mxcImportItems',

            /** @event mxcRefreshItems */
            'mxcRefreshItems',

            /** @event mxcCheckRegularExpressions */
            'mxcCheckRegularExpressions',

            /** @event mxcCheckNameMappingConsistency */
            'mxcCheckNameMappingConsistency'
        );
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            me.createImportButton(),
            me.createToolsButton(),
            me.createSelectionButton()
        ]);
        return items;
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
        me.fireEvent('mxcSetActiveMultiple', me, selModel);
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
        me.fireEvent('mxcSetAcceptedMultiple', me, selModel);
    },

    createSelectionButton: function() {
        let me = this;

        var menu = Ext.create('Ext.menu.Menu', {
            id: 'mxcDsiArticleSelectionMenu',
            style: {
                overflow: 'visible'
            },
            items: [
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
                }
            ]
        });
        me.selectionButton = Ext.create('Ext.button.Button', {
            text: 'Selection',
            disabled: true,
            iconCls: 'sprite-ui-check-box',
            menu: menu
        });
        return me.selectionButton;
    },

    createToolsButton: function() {
        let me = this;

        var menu = Ext.create('Ext.menu.Menu', {
          id: 'mxcDsiToolsMenu',
            style: {
                overflow: 'visible'
            },
            items: [
                {
                    text: 'Deactivate unlinked',
                    iconCls: 'sprite-arrow-circle',
                    handler: function() {
                        me.fireEvent('mxcRefreshItems', me);
                    }
                },
                '-',
                {
                    text: 'Remap Properties',
                    iconCls: 'sprite-maps',
                    handler: function() {
                        me.fireEvent('mxcRemapProperties', me);
                    }
                },
                '-',
                {
                    text : 'Check regular expressions',
                    handler: function() {
                        me.fireEvent('mxcCheckRegularExpressions', me);
                    }
                },
                {
                    text : 'Check name mapping consistency',
                    handler: function() {
                        me.fireEvent('mxcCheckNameMappingConsistency', me);
                    }
                },
            ]
        });
        return Ext.create('Ext.button.Button', {
            text: 'Tools',
            iconCls: 'sprite-wrench-screwdriver',
            menu: menu
        });
    },

    createImportButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Import/Update',
            iconCls: 'sprite-download-cloud',
            handler: function() {
                me.fireEvent('mxcImportItems', me);
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
                    }/* else if (e.column.text === 'accept') {
                        return e.record.get('active') === false;
                    }*/
                    return (
                        e.column.text === 'Brand'
                        || e.column.text === 'Supplier'
                        || e.column.text === 'Flavor'
                        || e.column.text === 'new'
                        || e.column.text === 'related'
                        || e.column.text === 'similar'
                        || e.column.text === 'accept'
                    );
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

    onSelectionChange: function(selModel, selection) {
        var me = this;
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
