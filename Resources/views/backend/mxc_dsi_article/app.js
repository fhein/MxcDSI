//

Ext.define('Shopware.apps.MxcDsiArticle', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiArticle',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'InnocigsArticle' ],

    views: [
        'list.Window',
        'list.InnocigsArticle',
        'list.extensions.Filter',

        'detail.InnocigsArticle',
        'detail.Window',

        'detail.InnocigsVariant'
    ],

    models: [ 'InnocigsArticle', 'InnocigsVariant' ],
    stores: [ 'InnocigsArticle', 'InnocigsVariant' ],

    launch: function() {
        return this.getController('InnocigsArticle').mainWindow;
    }
});