Ext.define('Shopware.apps.MxcDsiTest.model.Group', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiTest',
            detail: 'Shopware.apps.MxcDsiTest.view.detail.Group'
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
            model: 'Shopware.apps.MxcDsiTest.model.Option',
            storeClass: 'Shopware.apps.MxcDsiTest.store.Option',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});
