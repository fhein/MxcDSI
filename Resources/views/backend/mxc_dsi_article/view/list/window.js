//

Ext.define('Shopware.apps.MxcDsiArticle.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-article-list-window',
    height: 450,
    width: 1200,
    title : '{s name=window_title}InnoCigs Articles{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiArticle.view.list.InnocigsArticle',
            listingStore: 'Shopware.apps.MxcDsiArticle.store.InnocigsArticle',
            extensions: [
                { xtype: 'mxc-innocigs-article-listing-filter-panel' }
            ]
        };
    }
});