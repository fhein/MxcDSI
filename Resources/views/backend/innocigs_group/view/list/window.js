//

Ext.define('Shopware.apps.InnocigsGroup.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-group-list-window',
    height: 450,
    width: 500,
    title : '{s name=window_title}InnoCigs Configurator Groups{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.InnocigsGroup.view.list.InnocigsGroup',
            listingStore: 'Shopware.apps.InnocigsGroup.store.InnocigsGroup',
        };
    }
});