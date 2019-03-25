//

Ext.define('Shopware.apps.MxcDsiGroup.store.Group', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiGroup.model.Group',

   // autoLoad: false,
    remoteSort: false,
    groupField: 'accepted',
    sorters: [{ property: 'accepted', direction: 'DESC'}],

    configure: function() {
        return {
            controller: 'MxcDsiGroup'
        };
    }
});