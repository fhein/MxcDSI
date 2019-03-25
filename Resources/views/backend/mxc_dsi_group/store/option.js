//

Ext.define('Shopware.apps.MxcDsiGroup.store.Option', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiGroup.model.Option',

    configure: function() {
        return {
            controller: 'MxcDsiGroup'
        };
    }
});