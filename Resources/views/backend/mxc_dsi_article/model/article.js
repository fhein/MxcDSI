Ext.define('Shopware.apps.MxcDsiArticle.model.Article', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiArticle',
            detail: 'Shopware.apps.MxcDsiArticle.view.detail.Article'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'number', type: 'string' },
        { name : 'brand', type: 'string' },
        { name : 'category', type: 'string'},
        { name : 'active', type: 'boolean' },
        { name : 'manufacturer', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'supplier', type: 'string' },
        { name : 'accepted', type: 'boolean' },
        { name : 'new', type: 'boolean' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiArticle.model.Variant',
            storeClass: 'Shopware.apps.MxcDsiArticle.store.Variant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
