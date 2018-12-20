Ext.define('Shopware.apps.MxcDropshipInnocigs.model.InnocigsGroup', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'InnocigsGroup',
            detail: 'Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsGroup'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string' },
        { name : 'ignored', type: 'boolean' },
    ],

    associations: [{
        relation: 'OneToMany',
        type: 'hasMany',
        model: 'Shopware.apps.MxcDropshipInnocigs.model.InnocigsOptions',
        associationKey: 'options'
    }]
});
