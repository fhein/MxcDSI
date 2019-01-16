Ext.define('Shopware.apps.MxcDsiArticle.model.InnocigsArticle', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiArticle',
            detail: 'Shopware.apps.MxcDsiArticle.view.detail.InnocigsArticle'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'code', type: 'string' },
        { name : 'brand', type: 'string' },
        { name : 'category', type: 'string'},
        { name : 'active', type: 'boolean' },

        { name : 'name', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'accepted', type: 'boolean' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiArticle.model.InnocigsVariant',
            storeClass: 'Shopware.apps.MxcDsiArticle.store.InnocigsVariant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
