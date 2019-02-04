//

Ext.define('Shopware.apps.MxcDsiImport.view.list.Model', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-model-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiImport.view.detail.Window',
            columns: {
                master:         { header: 'Master'},
                model:          { header: 'Model'},
                name:           { header: 'Name'},
                category:       { header: 'Category'},
                manufacturer:   { header: 'Manufacturer'},
            },
            addButton: false,
            deleteColumn: false,
            deleteButton: false,
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            /**
             * @event mxcImport
             */
            'mxcImport'
        );
    },

    createToolbarItems: function() {
        let me = this;
        let items = me.callParent(arguments);
        items = Ext.Array.insert(items, 0, [
            me.createImportButton(),
        ]);
        return items;
    },

    createImportButton: function() {
        let me = this;
        return Ext.create('Ext.button.Button', {
            text: 'Import',
            iconCls: 'sprite-download-cloud',
            handler: function() {
                me.fireEvent('mxcImport', me);
            }
        });
    },

});
