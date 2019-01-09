//

Ext.define('Shopware.apps.InnocigsGroup.store.InnocigsGroup', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.InnocigsGroup.model.InnocigsGroup',

    configure: function() {
        return {
            controller: 'InnocigsGroup'
        };
    }
});