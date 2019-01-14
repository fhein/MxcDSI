//

Ext.define('Shopware.apps.MxcDsiTest.store.InnocigsGroup', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.MxcDsiTest.model.InnocigsGroup',

    remoteSort: false,
    groupField: 'accepted',
    sorters: [{ property: 'accepted', direction: 'DESC'}],

    configure: function() {
        return {
            controller: 'MxcDsiTest'
        };
    }
});