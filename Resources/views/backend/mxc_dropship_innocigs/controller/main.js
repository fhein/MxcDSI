
Ext.define('Shopware.apps.MxcDropshipInnocigs.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
            'mxc-innocigs-article-listing-grid': {
                mxcSaveArticle:  me.onSaveArticle
            }
        });
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onSaveArticle: function(record) {
        record.save({
            params: {
                 resource: 'innocigs_article'
            },
            success: function(record, operation) {
                if (operation.success) {
                    // Update the modified record by the data, the controller returned
                    // This way we make sure, that the record shows the data which is stored
                    // in the database
                    Ext.each(Object.keys(record.getData()), function (key) {
                        record.set(key, operation.records[0].data[key]);
                    });
                }
            },
            failure: function(record, operation) {
                Shopware.Notification.createStickyGrowlMessage({
                        title: '{s name=error}Error{/s}',
                        text: '{s name=unknownError}An unknown error occurred, please check your server logs{/s}',
                        log: true
                    },
                    'InnocigsArticleList');
            }
        });
    }
});