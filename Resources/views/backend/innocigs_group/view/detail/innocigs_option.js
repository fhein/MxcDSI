//

Ext.define('Shopware.apps.InnocigsGroup.view.detail.InnocigsOption', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-innocigs-option-grid',
    title: 'Options',
    height : 300,

    configure: function() {
        return {
             columns: {
                 name:          { header: 'Name'},
                 accepted:      { header: 'accept', width: 40, flex: 0}
             },
            addButton: false,
            deleteButton: false,
            actionColumn: false,
            searchField: false
        };
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            me.createAcceptButton(),
            me.createIgnoreButton(),
        ]);
        return items;
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
                    // disable cell editing for all columns but the 'accept' column
                    return (e.column.text === 'accept');
                },
                edit: function(editor, e) {
                    // prevent the red dirty cell marker
                    e.record.commit();
                }
            }
        });
        items.push(me.cellEditor);

        return items;
    },

    handleAcceptedState: function(changeTo) {
        let me = this;
        let selModel = me.getSelectionModel();
        let records = selModel.getSelection();
        Ext.each(records, function(record) {
            record.set('accepted', changeTo);
            record.commit();
        });
    },

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }
});