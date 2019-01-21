//

Ext.define('Shopware.apps.MxcDsiImport.view.variant.detail.VariantDetail', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-import-variant-detail-container',
    title: 'Variant Details',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDsiImport',
        };
    }
});