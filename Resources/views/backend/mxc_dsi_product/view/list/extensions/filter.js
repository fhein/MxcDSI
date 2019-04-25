Ext.define('Shopware.apps.MxcDsiProduct.view.list.extensions.Filter', {
    extend: 'Shopware.listing.FilterPanel',
    alias: 'widget.mxc-dsi-product-listing-filter-panel',
    width: 270,

    configure: function () {
        return {
            controller: 'Product',
            model: 'Shopware.apps.MxcDsiProduct.model.Product',
            fields: {
                number: {
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
                type: {
                    expression: 'LIKE',
                },
                manufacturer: {
                    expression: 'LIKE',
                },
                category: {
                    expression: 'LIKE',
                },
                flavor: {
                    expression: 'LIKE',
                },
                linked: { },
                active: { },
                accepted: { },
                new: { },
            }
        };
    }
});