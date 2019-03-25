Ext.define('Shopware.apps.MxcDsiGroup.model.Group', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiGroup',
            detail: 'Shopware.apps.MxcDsiGroup.view.detail.Group'
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
            model: 'Shopware.apps.MxcDsiGroup.model.Option',
            storeClass: 'Shopware.apps.MxcDsiGroup.store.Option',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});
