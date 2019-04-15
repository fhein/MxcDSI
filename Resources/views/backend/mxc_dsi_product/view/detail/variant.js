//

Ext.define('Shopware.apps.MxcDsiProduct.view.detail.Variant', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-dsi-variant-grid',
    title: 'Variants',
    height : 300,

    configure: function() {
        return {
             columns: {
                 new:           { header: 'new', width: 40, flex: 0 },
                 number:        { header: 'Number', width: 150, flex: 0 },
                 description:   { header: 'Description'},
                 active:        { header: 'active', width: 45, flex: 0 },
                 accepted:      { header: 'accept', width: 45, flex: 0 }
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

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }
});