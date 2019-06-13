//

Ext.define('Shopware.apps.MxcDsiProduct.view.detail.Product', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-product-detail-container',
    title: 'InnoCigs Product',
    height : 300,

    configure: function() {
        return {
            items: {
                new:           { header: 'new', width: 40, flex: 0 },
                number:        { header: 'Number', width: 150, flex: 0 },
                description:   { header: 'Description'},
                active:        { header: 'active', width: 45, flex: 0 },
                accepted:      { header: 'accept', width: 45, flex: 0 }
            },
            controller: 'MxcDsiProduct',
            associations: [ 'variants' ]
        };
    }

});