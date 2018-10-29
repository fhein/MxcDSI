
Ext.define('Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs',
            detail: 'Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsVariant'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'article', type: 'int' },
        { name : 'code', type: 'string' },
        { name : 'ean', type: 'string' },
        { name : 'priceNet', type: 'float' },
        { name : 'priceRecommended', type: 'float' },
        { name : 'active', type: 'boolean' },
        { name : 'created', type: 'date' },
        { name : 'updated', type: 'date' }
    ]
});

