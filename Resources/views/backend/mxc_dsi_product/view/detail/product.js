//

Ext.define('Shopware.apps.MxcDsiProduct.view.detail.Product', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-product-detail-container',
    title: 'InnoCigs Product',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDsiProduct',
            associations: [ 'variants' ]
        };
    }
});