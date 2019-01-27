//

Ext.define('Shopware.apps.MxcDsiImport', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiImport',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Model' ],

    views: [
        'list.Model',
        'list.Window',

        'detail.Model',
        'detail.Window',
    ],

    models: [ 'Model' ],
    stores: [ 'Model' ],

    launch: function() {
        return this.getController('Model').mainWindow;
    }
});