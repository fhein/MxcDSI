//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsVariant', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'InnocigsVariant'
        };
    },

    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant'
});