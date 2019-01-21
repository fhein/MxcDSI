//

Ext.define('Shopware.apps.MxcDsiImport', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiImport',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Article' ],

    views: [
        'list.Article',
        'list.Window',

        'detail.Article',
        'detail.Window',

        'variant.detail.VariantDetail',
        'variant.detail.VariantList',
        'variant.detail.Window',
    ],

    models: [ 'Article', 'Variant' ],
    stores: [ 'Article', 'Variant' ],

    launch: function() {
        return this.getController('Article').mainWindow;
    }
});