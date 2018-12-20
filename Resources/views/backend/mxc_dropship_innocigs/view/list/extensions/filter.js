Ext.define('Shopware.apps.MxcDropshipInnocigs.view.list.extensions.Filter', {
    extend: 'Shopware.listing.FilterPanel',
    alias:  'widget.mxc-innocigs-article-listing-filter-panel',
    width: 270,

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs',
            model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsArticle',
            fields: {
                code: {
                    expression: 'LIKE'
                },
                name: {
                    expression: 'LIKE',
                },
                supplier: {
                    expression: 'LIKE',
                },
                brand: {
                    expression: 'LIKE',
                },
                active: { },
                accepted: { }
            }
        };
    }
});