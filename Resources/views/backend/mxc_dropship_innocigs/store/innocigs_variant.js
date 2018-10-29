//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsVariant', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs'
        };
    },

    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant'
});