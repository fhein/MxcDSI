
Ext.define('Shopware.apps.MxcDropshipInnocigs.model.InnocigsOption', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            controller: 'InnocigsOption',
            detail: 'Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsOption'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string' },
        { name : 'group', type: 'int'},
        { name : 'ignored', type: 'boolean'},
        { name : 'created', type: 'date' },
        { name : 'updated', type: 'date' }
    ]
});

