//

Ext.define('Shopware.apps.MxcDsiTest.store.Option', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiTest.model.Option',

    remoteSort: false,
    groupField: 'accepted',
    pageSize: 200,
    sorters: [{ property: 'accepted', direction: 'DESC'}],

    configure: function() {
        return {
            controller: 'MxcDsiTest'
        };
    }
});