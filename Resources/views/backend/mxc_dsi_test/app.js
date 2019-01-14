//

Ext.define('Shopware.apps.MxcDsiTest', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiTest',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
        'list.InnocigsGroup',

        'detail.InnocigsGroup',
        'detail.Window',

        'detail.InnocigsOption'
    ],

    models: [ 'InnocigsGroup', 'InnocigsOption' ],
    stores: [ 'InnocigsGroup', 'InnocigsOption' ],

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});