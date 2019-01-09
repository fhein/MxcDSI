//

Ext.define('Shopware.apps.InnocigsArticle.store.InnocigsVariant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.InnocigsArticle.model.InnocigsVariant',

    configure: function() {
        return {
            controller: 'InnocigsArticle'
        };
    }
});