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
                ean:            { header: 'EAN'},
                name:           { header: 'Name'},
                manufacturer:   { header: 'Manufacturer'},
                category:       { header: 'Category'},
                options:        { header: 'Options'},
                purchasePrice:  { header: 'Purchase Price'},
                retailPrice:    { header: 'Retail Price'},
                images:         { header: 'Images'},
                manual:         { header: 'Manual'},
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
