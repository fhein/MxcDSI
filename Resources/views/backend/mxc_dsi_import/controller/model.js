Ext.define('Shopware.apps.MxcDsiImport.controller.Model', {
    extend: 'Enlight.app.Controller',

    init: function() {
        let me = this;
        me.control({
            'mxc-dsi-model-listing-grid': {
                mxcImport: me.onImport,
            }
        });

        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    onImport: function(grid) {
        let mask = new Ext.LoadMask(grid, { msg: 'Importing items ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiImport action=import}',
            params: {},
            callback: function(responseData, operation) {
                mask.hide();
                if(!operation) {
                    Shopware.Notification.createGrowlMessage('Import', 'An error occured while importing items.');
                    return false;
                } else {
                    Shopware.Notification.createGrowlMessage('Import', 'Items were successfully imported.');
                    grid.store.load();
                }
            }
        });
    },

});
