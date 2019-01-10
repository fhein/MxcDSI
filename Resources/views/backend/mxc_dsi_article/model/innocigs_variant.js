
Ext.define('Shopware.apps.MxcDsiArticle.model.InnocigsVariant', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            listing: 'Shopware.apps.MxcDsiArticle.view.detail.InnocigsVariant'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'code', type: 'string' },
        { name : 'ean', type: 'string' },
        { name : 'priceNet', type: 'float' },
        { name : 'priceRecommended', type: 'float' },
        { name : 'active', type: 'boolean' },
        { name : 'accepted', type: 'boolean'},
        { name : 'created', type: 'date' },
        { name : 'updated', type: 'date' },
        { name : 'description', type: 'string'}
    ]
});

