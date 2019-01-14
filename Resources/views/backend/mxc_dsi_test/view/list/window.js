//

Ext.define('Shopware.apps.MxcDsiTest.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-group-list-window',
    height: 450,
    width: 624,
    title : '{s name=window_title}InnoCigs Configurator Groups{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiTest.view.list.InnocigsGroup',
            listingStore: 'Shopware.apps.MxcDsiTest.store.InnocigsGroup',
        };
    }
});