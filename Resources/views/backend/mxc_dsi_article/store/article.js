//

Ext.define('Shopware.apps.MxcDsiArticle.store.Article', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiArticle.model.Article',

    configure: function() {
        return {
            controller: 'MxcDsiArticle'
        };
    }
});