Ext.define('Shopware.apps.MxcDropshipInnocigs.model.InnocigsArticle', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs',
            detail: 'Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsArticle'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'code', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'active', type: 'boolean' },
        { name : 'accepted', type: 'boolean' },
        { name : 'description', type: 'string' },
        { name : 'brand', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'image', type: 'string' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            loadOnDemand: true,
            model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant',
            storeClass: 'Shopware.apps.MxcDropshipInnocigs.store.InnocigsVariant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
