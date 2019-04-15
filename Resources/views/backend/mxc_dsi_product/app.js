//

Ext.define('Shopware.apps.MxcDsiProduct', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MxcDsiProduct',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Product' ],

    views: [
        'list.Product',
        'list.extensions.Filter',
        'list.Window',

        'detail.Product',
        'detail.Variant',
        'detail.Window'
    ],

    models: [ 'Product', 'Variant' ],
    stores: [ 'Product', 'Variant' ],

    launch: function() {
        return this.getController('Product').mainWindow;
    }
});