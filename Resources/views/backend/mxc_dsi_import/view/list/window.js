//

Ext.define('Shopware.apps.MxcDsiImport.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-dsi-model-list-window',
    height: 450,
    width: 1200,
    title : 'Models',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiImport.view.list.Model',
            listingStore: 'Shopware.apps.MxcDsiImport.store.Model',
        };
    }
});