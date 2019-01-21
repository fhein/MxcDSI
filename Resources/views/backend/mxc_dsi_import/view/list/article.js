//

Ext.define('Shopware.apps.MxcDsiImport.view.list.Article', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-import-article-listing-grid',
    region: 'center',

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiImport.view.detail.Window',
            columns: {
                number:       { header: 'Code'},
            },
            addButton: false,
            deleteButton: false,
            deleteColumn: false
        };
    },
});
