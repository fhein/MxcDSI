//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.list.InnocigsArticle', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-innocigs-article-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDropshipInnocigs.view.detail.Window',
            columns: {
                code:   { header: 'Code'},
                name:   { header: 'Name', flex: 3 },
                active: { header: 'active', width: 60, flex: 0 }
            },
            addButton: false,
            deleteButton: false,
            deleteColumn: false
        };
    },
    createToolbarItems: function() {
        var me = this;
        var items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [ me.createActivateButton()]);
        return items;
    },

    createActivateButton: function() {
        return Ext.create('Ext.button.Button', {
            text: 'Activate selected',
            iconCls: 'sprite-tick'
        });
    },

    createPlugins: function () {
        var me = this;
        var items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
        });
        items.push(me.cellEditor);

        return items;
    },

});
