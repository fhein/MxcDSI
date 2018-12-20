//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsGroup', {
    extend:'Shopware.store.Listing',

    configure: function() {
        return {
            controller: 'InnocigsGroup'
        };
    },

    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsGroup'
});