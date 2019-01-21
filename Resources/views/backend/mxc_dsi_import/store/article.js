//

Ext.define('Shopware.apps.MxcDsiImport.store.Article', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiImport.model.Article',

    configure: function() {
        return {
            controller: 'MxcDsiImport'
        };
    }
});