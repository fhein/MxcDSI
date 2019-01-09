//

Ext.define('Shopware.apps.InnocigsGroup.store.InnocigsOption', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.InnocigsGroup.model.InnocigsOption',

    configure: function() {
        return {
            controller: 'InnocigsGroup'
        };
    }
});