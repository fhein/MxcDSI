//

Ext.define('Shopware.apps.InnocigsArticle.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-article-list-window',
    height: 450,
    width: 1200,
    title : '{s name=window_title}InnoCigs Articles{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.InnocigsArticle.view.list.InnocigsArticle',
            listingStore: 'Shopware.apps.InnocigsArticle.store.InnocigsArticle',
            extensions: [
                { xtype: 'mxc-innocigs-article-listing-filter-panel' }
            ]
        };
    }
});