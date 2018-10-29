Ext.define('Shopware.apps.MxcDropshipInnocigs.model.InnocigsArticle', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs',
            detail: 'Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsArticle'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'code', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'active', type: 'boolean' },
        { name : 'created', type: 'date' },
        { name : 'updated', type: 'date' }
    ],

    associations: [{
        relation: 'OneToMany',
        type: 'hasMany',
        model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant',
        associationKey: 'variants'
    }]
});
