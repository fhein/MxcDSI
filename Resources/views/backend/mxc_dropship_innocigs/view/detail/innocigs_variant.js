//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsVariant', {
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
                 accepted:      { header: 'use', width:40, flex: 0}
             },
            toolbar: false,
            actionColumn: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            /**
             * @event mxcSaveVariant
             */
            'mxcSaveVariant',
        );
    },


    createPlugins: function () {
        let me = this;
        let items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, e) {
                    return (e.column.text === 'use');
                },
                edit: function(editor, e) {
                    // the 'edit' event gets fired even if the new value equals the old value
                    if (e.originalValue === e.value) {
                        return;
                    }
                    //me.fireEvent('mxcSaveVariant', e.record);
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