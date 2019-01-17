Ext.define('Shopware.apps.MxcDsiGroup.model.InnocigsGroup', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiTest',
            detail: 'Shopware.apps.MxcDsiGroup.view.detail.InnocigsGroup'
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
            model: 'Shopware.apps.MxcDsiGroup.model.InnocigsOption',
            storeClass: 'Shopware.apps.MxcDsiGroup.store.InnocigsOption',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});