Ext.define('Shopware.apps.MxcDsiGroup.model.InnocigsGroup', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiGroup',
            detail: 'Shopware.apps.MxcDsiGroup.view.detail.InnocigsGroup'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string' },
        { name : 'accepted', type: 'boolean' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiGroup.model.InnocigsOption',
            storeClass: 'Shopware.apps.MxcDsiGroup.store.InnocigsOption',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});
