//

Ext.define('Shopware.apps.MxcDropshipInnocigs.view.detail.InnocigsVariant', {
    extend: 'Shopware.grid.Panel',
    alias: 'widget.mxc-innocigs-variant-grid',
    title: 'Variants',
    height : 300,

    configure: function() {
        return {
             columns: {
                   active:     { header: 'active', width: 60, flex: 0 },
                   code:       { header: 'Code'},
                   accepted:   { header: 'accepted', width:60, flex: 0}
             },
            addButton: false,
            deleteButton: false,
            deleteColumn: false,
            editColumn: false
        };
    },

});