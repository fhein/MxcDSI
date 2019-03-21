Ext.define('Shopware.apps.MxcDsiArticle.controller.Article', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'articleListing', selector: 'mxc-dsi-article-list-window mxc-dsi-article-listing-grid' },
    ],

    init: function() {
        let me = this;

        me.control({
            'mxc-dsi-article-listing-grid': {
                mxcSaveArticle:     me.onSaveArticle,
                mxcSaveMultiple:    me.onSaveMultiple,
                mxcImportItems:     me.onImportItems,
                mxcRemapProperties: me.onRemapProperties,
                mxcSetActiveMultiple: me.onSetActiveMultiple,
                mxcSetAcceptedMultiple: me.onSetAcceptedMultiple
            }
        });
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    onImportItems: function(grid) {
        let me = this;
        let mask = new Ext.LoadMask(grid, { msg: 'Updating items ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiArticle action=import}',
            params: {},

            success: function (response) {
                mask.hide();
                let result = Ext.JSON.decode(response.responseText);
                console.log(result);
                if (!result) {
                    me.showError(response.responseText);
                } else if (result.success) {
                    Shopware.Notification.createGrowlMessage('Update', result.message);
                    grid.store.load();
                } else {
                    me.showError(result.message);
                }
            },

            failure: function (response) {
                mask.hide();
                if (response.responseText) {
                    me.showError(response.responseText);
                } else {
                    me.showError('An unknown error occurred, please check your server logs.');
                }
            },
        });
    },

    onRemapProperties: function(grid) {
        let me = this;
        let mask = new Ext.LoadMask(grid, { msg: 'Remapping properties ...'});
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiArticle action=remap}',
            params: {},

            success: function (response) {
                mask.hide();
                let result = Ext.JSON.decode(response.responseText);
                console.log(result);
                if (!result) {
                    me.showError(response.responseText);
                } else if (result.success) {
                    Shopware.Notification.createGrowlMessage('Remap Properties', result.message);
                    grid.store.load();
                } else {
                    me.showError(result.message);
                }
            },

            failure: function (response) {
                mask.hide();
                if (response.responseText) {
                    me.showError(response.responseText);
                } else {
                    me.showError('An unknown error occurred, please check your server logs');
                }
            },
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

    onSetActiveMultiple: function(grid, selectionModel) {
        let me = this;
        let field = 'active';
        let value = selectionModel.getSelection()[0].get(field);
        let maskMessage = value ? 'Activating articles.' : 'Deactivating articles.';
        me.setFieldMultiple(grid, selectionModel, field, value, maskMessage);
    },


    onSetAcceptedMultiple: function(grid, selectionModel) {
        let me = this;
        let field = 'accepted';
        let value = selectionModel.getSelection()[0].get(field);
        let maskMessage = value ? 'Setting articles to accepted.' : 'Setting articles to ignored.';
        me.setFieldMultiple(grid, selectionModel, field, value, maskMessage);
    },

    onSaveMultiple: function(grid, selectionModel) {
        let me = this;
        let records = selectionModel.getSelection();
        if (records.length > 0) {
            let mask = new Ext.LoadMask(grid, { msg: 'Applying changes ...'});
            mask.show();
            me.save(records, mask, function() {
                selectionModel.deselectAll();
                Shopware.Notification.createGrowlMessage('InnoCigs Dropship', 'Changes successfully applied.', 'Article');
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
        let me = this;

        let rawData = operation.records[0].proxy.reader.rawData;
        let message = '{s name=unknownError}An unknown error occurred, please check your server logs.{/s}';
        if (rawData.message) {
            record.set('active', false);
            message = rawData.message;
        }
        me.showError(message);
    },

    showError: function (message) {
        Shopware.Notification.createStickyGrowlMessage({
                title: 'Error',
                text: message,
                log: true
            },
            'MxcDropshipInnoCigs');
    },

    setFieldMultiple: function(grid, selectionModel, field, value, maskMessage) {
        let me = this;
        let mask = new Ext.LoadMask(grid, { msg: maskMessage});
        let ids = [];

        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: '{url controller=MxcDsiArticle action=setStateMultiple}',
            params: {
                field: field,
                value: value,
                ids: Ext.JSON.encode(ids)
            },

            success: function (response) {
                mask.hide();
                let result = Ext.JSON.decode(response.responseText);
                console.log(result);
                if (!result) {
                    me.showError(response.responseText);
                } else if (result.success) {
                    Shopware.Notification.createGrowlMessage('Success', result.message);
                    grid.store.load();
                } else {
                    me.showError(result.message);
                }
            },

            failure: function (response) {
                mask.hide();
                if (response.responseText) {
                    me.showError(response.responseText);
                } else {
                    me.showError('An unknown error occurred, please check your server logs.');
                }
            },
        });

    },

});
