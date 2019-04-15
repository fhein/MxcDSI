//

Ext.define('Shopware.apps.MxcDsiProduct.store.Product', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiProduct.model.Product',

    configure: function() {
        return {
            controller: 'MxcDsiProduct'
        };
    }
});