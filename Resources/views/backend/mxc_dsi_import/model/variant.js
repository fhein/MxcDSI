
Ext.define('Shopware.apps.MxcDsiImport.model.Variant', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            listing: 'Shopware.apps.MxcDsiImport.view.variant.detail.VariantList',
            detail: 'Shopware.apps.MxcDsiImport.view.variant.detail.VariantDetail',
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'category', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'image', type: 'string' },
        { name : 'number', type: 'string' },
        { name : 'ean', type: 'string' },
        { name : 'purchasePrice', type: 'string' },
        { name : 'retailPrice', type: 'string' },
        { name : 'manufacturer', type: 'string' },
    ]
});

