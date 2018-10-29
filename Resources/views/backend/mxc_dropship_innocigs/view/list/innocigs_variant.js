//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.list.InnocigsVariant', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-innocigs-variant-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDropshipInnocigs.view.detail.Window'
        };
    }
});
