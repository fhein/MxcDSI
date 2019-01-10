//

Ext.define('Shopware.apps.MxcDsiGroup.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-group-list-window',
    height: 450,
    width: 624,
    title : '{s name=window_title}InnoCigs Configurator Groups{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiGroup.view.list.InnocigsGroup',
            listingStore: 'Shopware.apps.MxcDsiGroup.store.InnocigsGroup',
        };
    }
});