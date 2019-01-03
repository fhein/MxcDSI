//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsArticle', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsArticle',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs'
        };
    }
});