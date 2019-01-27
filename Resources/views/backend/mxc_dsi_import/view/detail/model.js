//

Ext.define('Shopware.apps.MxcDsiImport.view.detail.Model', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-model-detail-container',
    title: 'InnoCigs Import',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDsiImport',
        };
    }
});