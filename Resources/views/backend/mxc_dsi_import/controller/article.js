Ext.define('Shopware.apps.MxcDsiImport.controller.Article', {
    extend: 'Enlight.app.Controller',

    init: function() {
        let me = this;
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },
});
