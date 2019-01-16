Ext.define('Shopware.apps.MxcDsiArticle.controller.InnocigsArticle', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'articleListing', selector: 'mxc-innocigs-article-list-window mxc-innocigs-article-listing-grid' },
    ],

    init: function() {
        let me = this;

        me.control({
            'mxc-innocigs-article-listing-grid': {
                mxcSaveArticle:  me.onSaveArticle,
                mxcSaveMultiple:  me.onSaveMultiple,
                mxcImportItems: me.onImportItems,
                mxcApplyFilter: me.onApplyFilter
            }
        });
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    onImportItems: function(grid) {
        let mask = new Ext.LoadMask(grid, { msg: 'Importing items ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiArticle action=import}',
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

    onApplyFilter: function(grid) {
        let mask = new Ext.LoadMask(grid, { msg: 'Applying filters ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiArticle action=filter}',
            params: {},
            callback: function(responseData, operation) {
                mask.hide();
                if(!operation) {
                    Shopware.Notification.createGrowlMessage('Import', 'An error occured while applying filters.');
                    return false;
                } else {
                    Shopware.Notification.createGrowlMessage('Import', 'Filters successfully applied.');
                    grid.store.load();
                }
            }
        });
    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onSaveArticle: function(record) {
        let me = this;
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
                me.handleError(record, operation);
            }
        });
    },

    onSaveMultiple: function(grid, selectionModel) {
        let me = this;
        let records = selectionModel.getSelection();
        if (records.length > 0) {
            let mask = new Ext.LoadMask(grid, { msg: 'Applying changes ...'});
            mask.show();
            me.save(records, mask, function() {
                selectionModel.deselectAll();
                Shopware.Notification.createGrowlMessage('InnoCigs Dropship', 'Changes successfully applied.', 'InnocigsArticle');
                mask.hide();
            });
        }
    },

    save: function(records, mask, callback) {
        let me = this,
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
                        me.save(records, mask, callback);
                    }
                } else {
                    me.handleError(operation);
                    mask.hide();
                }
            },
            failure: function(record, operation) {
                me.handleError(record, operation);
                mask.hide();
                me.getArticleListing().getStore().load();
            }
        })
    },
    handleError: function(record, operation) {
        let rawData = operation.records[0].proxy.reader.rawData;
        let message = '{s name=unknownError}An unknown error occurred, please check your server logs.{/s}';
        if (rawData.message) {
            record.set('active', false);
            message = rawData.message;
        }

        Shopware.Notification.createStickyGrowlMessage({
                title: '{s name=error}Error{/s}',
                text: message,
                log: true
            },
            'InnocigsArticle'
        );
    }
});
