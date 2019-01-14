//

Ext.define('Shopware.apps.MxcDsiTest.store.InnocigsOption', {
    extend:'Shopware.store.Association',
    model: 'Shopware.apps.MxcDsiTest.model.InnocigsOption',

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