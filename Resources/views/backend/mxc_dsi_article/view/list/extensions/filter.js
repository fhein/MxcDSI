Ext.define('Shopware.apps.MxcDsiArticle.view.list.extensions.Filter', {
    extend: 'Shopware.listing.FilterPanel',
    alias:  'widget.mxc-innocigs-article-listing-filter-panel',
    width: 270,

    configure: function() {
        return {
            controller: 'InnocigsArticle',
            model: 'Shopware.apps.MxcDsiArticle.model.InnocigsArticle',
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
                category: {
                    expression: 'LIKE',
                },
                active: { },
                accepted: { }
            }
        };
    }
});