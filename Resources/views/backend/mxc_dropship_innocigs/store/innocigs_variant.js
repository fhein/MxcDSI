//

Ext.define('Shopware.apps.MxcDropshipInnocigs.store.InnocigsVariant', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs'
        };
    }
});