//

Ext.define('Shopware.apps.InnocigsGroup.view.list.InnocigsGroup', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-innocigs-group-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.InnocigsGroup.view.detail.Window',
            columns: {
                name:       { header: 'Name', flex: 3 },
                accepted:   { header: 'accept', width:60, flex: 0}
            },
            toolbar: false,
            deleteColumn: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            /**
             * @event mxcSaveGroup
             */
            'mxcSaveGroup',
        );
    },

    createPlugins: function () {
        let me = this;
        let items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, e) {
                    return (e.column.text === 'accept');
                },
                edit: function(editor, e) {
                    // the 'edit' event gets fired even if the new value equals the old value
                    if (e.originalValue === e.value) {
                        return;
                    }
                    me.fireEvent('mxcSaveGroup', e.record);
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
