//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.list.InnocigsArticle', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-innocigs-article-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDropshipInnocigs.view.detail.Window'
        };
    }
});
