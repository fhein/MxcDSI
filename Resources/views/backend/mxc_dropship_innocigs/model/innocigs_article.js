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
        { name : 'innocigsCode', type: 'string' },
        { name : 'name', type: 'string' },
        { name : 'innocigsName', type: 'string' },
        { name : 'active', type: 'boolean' },
        { name : 'ignored', type: 'boolean' },
        { name : 'description', type: 'string' },
        { name : 'image', type: 'string' }
    ],

    associations: [{
        relation: 'OneToMany',
        type: 'hasMany',
        model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsVariant',
        associationKey: 'variants'
    }]
});
