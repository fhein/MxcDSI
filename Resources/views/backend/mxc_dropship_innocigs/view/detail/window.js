//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.detail.Window', {
    extend: 'Shopware.window.Detail',
    alias: 'widget.mxc-innocigs-article-detail-window',
    title : '{s name=title}InnoCigs Article{/s}',
    height: 350,
    width: 600,
    configure: function() {
        return {
            associations: [ 'variants' ]
        }
    }
});
