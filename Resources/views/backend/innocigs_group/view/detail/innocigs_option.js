//

Ext.define('Shopware.apps.InnocigsGroup.view.detail.InnocigsOption', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-innocigs-option-grid',
    title: 'Options',
    height : 300,

    configure: function() {
        return {
             columns: {
                 // active:     { header: 'active', width: 60, flex: 0 }
                 name:          { header: 'Name', width: 150, flex: 0},
                 accepted:      { header: 'use', width: 40, flex: 0}
             },
            toolbar: false,
            actionColumn: false
        };
    },

    createPlugins: function () {
        let me = this;
        let items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, e) {
                    // disable cell editing for all columns but the 'use' column
                    return (e.column.text === 'use');
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

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }
});