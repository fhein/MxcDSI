//

Ext.define('Shopware.apps.MxcDsiGroup', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiGroup',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Group' ],

    views: [
        'list.Group',
        'list.Window',

        'detail.Group',
        'detail.Option',
        'detail.Window'
    ],

    models: [ 'Group', 'Option' ],
    stores: [ 'Group', 'Option' ],

    launch: function() {
        return this.getController('Group').mainWindow;
    }
});