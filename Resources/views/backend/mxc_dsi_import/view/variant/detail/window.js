//

Ext.define('Shopware.apps.MxcDsiImport.view.variant.detail.Window', {
    extend: 'Shopware.window.Detail',
    alias: 'widget.mxc-dsi-import-variant-detail-window',
    title : '{s name=title}Variant Details{/s}',
    height: 520,
    width: 650,
    configure: function() {
        return {
            eventAlias: 'variant-detail'
        }
    }
});
