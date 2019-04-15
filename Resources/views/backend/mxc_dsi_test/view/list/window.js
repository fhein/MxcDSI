//

Ext.define('Shopware.apps.MxcDsiTest.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-dsi-test-group-list-window',
    height: 450,
    width: 624,
    title : 'InnoCigs Configurator Groups',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiTest.view.list.Group',
            listingStore: 'Shopware.apps.MxcDsiTest.store.Group',
        };
    }
});