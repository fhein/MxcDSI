//

Ext.define('Shopware.apps.MxcDsiArticle.store.Variant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiArticle.model.Variant',

    configure: function() {
        return {
            controller: 'MxcDsiArticle'
        };
    }
});