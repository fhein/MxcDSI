//

Ext.define('Shopware.apps.MxcDropshipInnocigs', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDropshipInnocigs',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
        'list.InnocigsArticle',
        'list.InnocigsVariant',

        'detail.InnocigsArticle',
        'detail.InnocigsVariant',
        'detail.Window'
    ],

    models: [ 'InnocigsArticle', 'InnocigsVariant' ],
    stores: [ 'InnocigsArticle', 'InnocigsVariant' ],

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});