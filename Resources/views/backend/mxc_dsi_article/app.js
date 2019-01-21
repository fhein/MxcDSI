//

Ext.define('Shopware.apps.MxcDsiArticle', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiArticle',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Article' ],

    views: [
        'list.Article',
        'list.extensions.Filter',
        'list.Window',

        'detail.Article',
        'detail.Variant',
        'detail.Window'
    ],

    models: [ 'Article', 'Variant' ],
    stores: [ 'Article', 'Variant' ],

    launch: function() {
        return this.getController('Article').mainWindow;
    }
});