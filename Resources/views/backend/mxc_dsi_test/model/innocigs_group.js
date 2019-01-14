Ext.define('Shopware.apps.MxcDsiTest.model.InnocigsGroup', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiTest',
            detail: 'Shopware.apps.MxcDsiTest.view.detail.InnocigsGroup'
        };
    },

    fields: [
        { name : 'accepted', type: 'boolean' },
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiTest.model.InnocigsOption',
            storeClass: 'Shopware.apps.MxcDsiTest.store.InnocigsOption',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});
