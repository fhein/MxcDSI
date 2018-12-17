//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.mxc-innocigs-article-list-window',
    height: 450,
    title : '{s name=window_title}InnoCigs Articles{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDropshipInnocigs.view.list.InnocigsArticle',
            listingStore: 'Shopware.apps.MxcDropshipInnocigs.store.InnocigsArticle',
            extensions: [
                { xtype: 'mxc-innocigs-article-listing-filter-panel' }
            ]
        };
    }
});