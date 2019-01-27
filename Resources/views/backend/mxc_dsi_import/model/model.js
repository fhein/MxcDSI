Ext.define('Shopware.apps.MxcDsiImport.model.Model', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDsiImport',
            detail: 'Shopware.apps.MxcDsiImport.view.detail.Model'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'master', type: 'string' },
        { name : 'purchasePrice', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'imageUrl', type: 'string' },
        { name : 'category', type: 'string' },
        { name : 'model', type: 'string' },
        { name : 'retailPrice', type: 'string' },
        { name : 'manufacturer', type: 'string' },
        { name : 'additionalImages', type: 'string' },
        { name : 'ean', type: 'string' },
    ],
});
