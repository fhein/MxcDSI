//

Ext.define('Shopware.apps.MxcDsiGroup.store.InnocigsGroup', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiGroup.model.InnocigsGroup',

    configure: function() {
        return {
            controller: 'MxcDsiGroup'
        };
    }
});