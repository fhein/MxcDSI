//

Ext.define('Shopware.apps.MxcDsiImport.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-dsi-import-article-list-window',
    height: 450,
    width: 1200,
    title : '{s name=window_title}InnoCigs Articles{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiImport.view.list.Article',
            listingStore: 'Shopware.apps.MxcDsiImport.store.Article',
        };
    }
});