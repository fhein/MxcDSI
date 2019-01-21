//

Ext.define('Shopware.apps.MxcDsiImport.view.variant.detail.VariantList', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-dsi-variant-grid',
    title: 'Variants',
    height : 300,

    configure: function() {
        return {
            columns: {
                number:          { header: 'Code', width: 150, flex: 0},
                name:            { header: 'name' },
            },
            toolbar: false,
            deleteColumn: false,
        };
    },
});