
Ext.define('Shopware.apps.MxcDsiArticle.model.Variant', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            listing: 'Shopware.apps.MxcDsiArticle.view.detail.Variant'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'number', type: 'string' },
        { name : 'ean', type: 'string' },
        { name : 'priceNet', type: 'float' },
        { name : 'priceRecommended', type: 'float' },
        { name : 'active', type: 'boolean' },
        { name : 'accepted', type: 'boolean'},
        { name : 'created', type: 'date' },
        { name : 'updated', type: 'date' },
        { name : 'description', type: 'string'},
        { name : 'new', type: 'boolean' },
    ]
});

