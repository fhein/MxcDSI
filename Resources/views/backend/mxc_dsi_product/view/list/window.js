//

Ext.define('Shopware.apps.MxcDsiProduct.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-dsi-product-list-window',
    height: 450,
    width: 1200,
    title : 'InnoCigs Products',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiProduct.view.list.Product',
            listingStore: 'Shopware.apps.MxcDsiProduct.store.Product',
            extensions: [
                { xtype: 'mxc-dsi-product-listing-filter-panel' }
            ]
        };
    }
});