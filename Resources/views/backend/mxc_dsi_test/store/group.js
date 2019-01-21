//

Ext.define('Shopware.apps.MxcDsiTest.store.Group', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiTest.model.Group',

    remoteSort: false,
    groupField: 'accepted',
    sorters: [{ property: 'accepted', direction: 'DESC'}],

    configure: function() {
        return {
            controller: 'MxcDsiTest'
        };
    }
});