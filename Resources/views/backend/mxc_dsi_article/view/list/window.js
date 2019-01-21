//

Ext.define('Shopware.apps.MxcDsiArticle.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-dsi-article-list-window',
    height: 450,
    width: 1200,
    title : '{s name=window_title}InnoCigs Articles{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiArticle.view.list.Article',
            listingStore: 'Shopware.apps.MxcDsiArticle.store.Article',
            extensions: [
                { xtype: 'mxc-dsi-article-listing-filter-panel' }
            ]
        };
    }
});