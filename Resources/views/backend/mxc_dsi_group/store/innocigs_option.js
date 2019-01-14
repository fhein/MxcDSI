//

Ext.define('Shopware.apps.MxcDsiGroup.store.InnocigsOption', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiGroup.model.InnocigsOption',

    configure: function() {
        return {
            controller: 'MxcDsiTest'
        };
    }
});