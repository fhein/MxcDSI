//

Ext.define('Shopware.apps.MxcDsiProduct.store.Variant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiProduct.model.Variant',

    configure: function() {
        return {
            controller: 'MxcDsiProduct'
        };
    }
});