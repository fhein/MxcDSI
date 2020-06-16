Ext.define('Shopware.apps.MxcDsiProduct.model.Product', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiProduct',
            detail: 'Shopware.apps.MxcDsiProduct.view.detail.Product'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'number', type: 'string' },
        { name : 'brand', type: 'string' },
        { name : 'type', type: 'string' },
        { name : 'category', type: 'string'},
        { name : 'active', type: 'boolean' },
        { name : 'manufacturer', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'commonName', type: 'string' },
        { name : 'flavor', type: 'string' },
        { name : 'dosage', type: 'string'},
        { name : 'content', type: 'string'},
        { name : 'capacity', type: 'string'},
        { name : 'supplier', type: 'string' },
        { name : 'power', type: 'integer' },
        { name : 'cellCapacity', type: 'integer' },
        { name : 'cellChangeable', type: 'boolean' },
        { name : 'numberOfCells', type: 'integer' },
        { name : 'headChangeable', type: 'boolean' },
        { name : 'accepted', type: 'boolean' },
        { name : 'new', type: 'boolean' },
        { name : 'linked', type: 'boolean' },
        //    { name : 'releaseDate', type: 'date', dateFormat: 'd.m.Y' },
        //    { name : 'created', type: 'date', dateFormat: 'd.m.Y' },
    ],

    associations: [
        {
            relation: 'OneToMany',
            type: 'hasMany',
            model: 'Shopware.apps.MxcDsiProduct.model.Variant',
            storeClass: 'Shopware.apps.MxcDsiProduct.store.Variant',
            name: 'getVariants',
            associationKey: 'variants'
        }
    ]
});
