//

Ext.define('Shopware.apps.InnocigsArticle.store.InnocigsArticle', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.InnocigsArticle.model.InnocigsArticle',

    configure: function() {
        return {
            controller: 'InnocigsArticle'
        };
    }
});