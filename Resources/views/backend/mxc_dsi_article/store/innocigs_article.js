//

Ext.define('Shopware.apps.MxcDsiArticle.store.InnocigsArticle', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiArticle.model.InnocigsArticle',

    configure: function() {
        return {
            controller: 'MxcDsiArticle'
        };
    }
});