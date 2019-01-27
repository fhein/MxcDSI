//

Ext.define('Shopware.apps.MxcDsiImport.store.Model', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiImport.model.Model',

    configure: function() {
        return {
            controller: 'MxcDsiImport'
        };
    }
});