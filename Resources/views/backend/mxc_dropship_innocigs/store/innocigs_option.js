//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsOption', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'InnocigsOption'
        };
    },

    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsOption'
});