Ext.define('Shopware.apps.MxcDsiImport.model.Article', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiImport',
            detail: 'Shopware.apps.MxcDsiImport.view.detail.Article'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'number', type: 'string' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiImport.model.Variant',
            storeClass: 'Shopware.apps.MxcDsiImport.store.Variant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
