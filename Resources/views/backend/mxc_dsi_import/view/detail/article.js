//

Ext.define('Shopware.apps.MxcDsiImport.view.detail.Article', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-import-article-detail-container',
    title: 'InnoCigs Import',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDsiImport',
            associations: [ 'variants' ]
        };
    }
});