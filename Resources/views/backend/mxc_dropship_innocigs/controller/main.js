
Ext.define('Shopware.apps.MxcDropshipInnocigs.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function() {
        var me = this;

        me.control({
            'mxc-innocigs-article-listing-grid': {
                mxcSaveArticle:  me.onSaveArticle,
                mxcSaveMultiple:  me.onSaveMultiple,
                mxcImportItems: me.onImportItems
            },
        });
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    onImportItems: function(grid) {
        var mask = new Ext.LoadMask(grid, { msg: 'Importing items ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDropshipInnocigs action=import}',
            params: {},
            callback: function(responseData, operation) {
                if(!operation) {
                    Shopware.Notification.createGrowlMessage('Import', 'An error occured while importing items.');
                    return false;
                } else {
                    Shopware.Notification.createGrowlMessage('Import', 'Items were successfully imported.');
                    grid.store.load();
                    mask.hide();
                }
            }
        });
    },

    onApplyFilter: function() {

    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onSaveArticle: function(record) {
        if (record.get('active') === true) {
            record.set('accepted', true);
        }
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
                    'MxcDropshipInnocigs'
                );
            }
        });
    },

    onSaveMultiple: function(grid, selectionModel) {
        var me = this;
        var records = selectionModel.getSelection();
        if (records.length > 0) {
            var mask = new Ext.LoadMask(grid, { msg: 'Applying changes ...'});
            mask.show();
            me.save(records, function() {
                selectionModel.deselectAll();
                Shopware.Notification.createGrowlMessage('InnoCigs Dropship', 'Changes successfully applied.', 'MxcDropshipInnocigs');
                mask.hide();
            });
        };
    },

    save: function(records, callback) {
        var me = this,
            record = records.pop();

        record.save({
            success: function(record, operation) {
                if (operation.success) {
                    // Update the modified record by the data, the controller returned
                    // This way we make sure, that the record shows the data which is stored
                    // in the database
                    Ext.each(Object.keys(record.getData()), function (key) {
                        record.set(key, operation.records[0].data[key]);
                    });

                    if (records.length === 0) {
                        callback();
                    } else {
                        me.save(records, callback);
                    }
                } else {
                    Shopware.Notification.createStickyGrowlMessage({
                            title: '{s name=error}Error{/s}',
                            text: '{s name=unknownError}An unknown error occurred, please check your server logs{/s}',
                            log: true
                        },
                        'MxcDropshipInnocigs'
                    );
                }
            },
            failure: function(record, operation) {
                Shopware.Notification.createStickyGrowlMessage({
                        title: '{s name=error}Error{/s}',
                        text: '{s name=unknownError}An unknown error occurred, please check your server logs{/s}',
                        log: true
                    },
                    'MxcDropshipInnocigs'
                );
            }
        })
    },
});