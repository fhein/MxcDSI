Ext.define('Shopware.apps.InnocigsGroup.model.InnocigsGroup', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'InnocigsGroup',
            detail: 'Shopware.apps.InnocigsGroup.view.detail.InnocigsGroup'
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
            model: 'Shopware.apps.InnocigsGroup.model.InnocigsOption',
            storeClass: 'Shopware.apps.InnocigsGroup.store.InnocigsOption',
            name: 'getOptions',
            associationKey: 'options'
        }
    ]
});
