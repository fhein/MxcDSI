//

Ext.define('Shopware.apps.MxcDsiArticle.store.InnocigsVariant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiArticle.model.InnocigsVariant',

    configure: function() {
        return {
            controller: 'MxcDsiArticle'
        };
    }
});