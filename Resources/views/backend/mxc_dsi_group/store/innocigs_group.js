//

Ext.define('Shopware.apps.MxcDsiGroup.store.InnocigsGroup', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiGroup.model.InnocigsGroup',

    groupField: 'accepted',
    sorters: [{ property: 'accepted', direction: 'DESC'}],

    configure: function() {
        return {
            controller: 'MxcDsiGroup'
        };
    }
});