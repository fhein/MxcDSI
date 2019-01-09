Ext.define('Shopware.apps.InnocigsArticle.model.InnocigsArticle', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'InnocigsArticle',
            detail: 'Shopware.apps.InnocigsArticle.view.detail.InnocigsArticle'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'code', type: 'string' },
        { name : 'brand', type: 'string' },
        { name : 'active', type: 'boolean' },

        { name : 'name', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'accepted', type: 'boolean' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.InnocigsArticle.model.InnocigsVariant',
            storeClass: 'Shopware.apps.InnocigsArticle.store.InnocigsVariant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
