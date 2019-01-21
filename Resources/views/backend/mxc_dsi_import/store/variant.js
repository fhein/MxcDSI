//

Ext.define('Shopware.apps.MxcDsiImport.store.Variant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiImport.model.Variant',

    configure: function() {
        return {
            controller: 'MxcDsiImport'
        };
    }
});