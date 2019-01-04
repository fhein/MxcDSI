//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsArticle', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-innocigs-article-detail-container',
    title: 'InnoCigs Article',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDropshipInnocigs',
            associations: [ 'variants' ]
        };
    }
});