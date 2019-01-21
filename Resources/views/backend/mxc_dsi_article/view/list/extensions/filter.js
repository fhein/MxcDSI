Ext.define('Shopware.apps.MxcDsiArticle.view.list.extensions.Filter', {
    extend: 'Shopware.listing.FilterPanel',
    alias:  'widget.mxc-dsi-article-listing-filter-panel',
    width: 270,

    configure: function() {
        return {
            controller: 'Article',
            model: 'Shopware.apps.MxcDsiArticle.model.Article',
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
                manufacturer: {
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