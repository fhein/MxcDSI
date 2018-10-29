//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsArticle', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs'
        };
    },

    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsArticle'
});