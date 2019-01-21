//

Ext.define('Shopware.apps.MxcDsiArticle.view.detail.Article', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-article-detail-container',
    title: 'InnoCigs Article',
    height : 300,

    configure: function() {
        return {
            controller: 'MxcDsiArticle',
            associations: [ 'variants' ]
        };
    }
});