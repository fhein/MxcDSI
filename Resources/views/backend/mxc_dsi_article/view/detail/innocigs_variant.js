//

Ext.define('Shopware.apps.MxcDsiArticle.view.detail.InnocigsVariant', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-innocigs-variant-grid',
    title: 'Variants',
    height : 300,

    configure: function() {
        return {
             columns: {
                 // active:     { header: 'active', width: 60, flex: 0 }
                 code:          { header: 'Code', width: 150, flex: 0},
                 description:   { header: 'Description'},
                 accepted:      { header: 'accept', width: 45, flex: 0}
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