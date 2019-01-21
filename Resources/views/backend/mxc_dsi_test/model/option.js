
Ext.define('Shopware.apps.MxcDsiTest.model.Option', {
    extend: 'Shopware.data.Model',

    configure: function() {
        return {
            listing: 'Shopware.apps.MxcDsiTest.view.detail.Option'
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'name', type: 'string' },
        { name : 'accepted', type: 'boolean'},
    ]
});

