Ext.define('Shopware.apps.MxcDsiArticle.controller.Article', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'articleListing', selector: 'mxc-dsi-article-list-window mxc-dsi-article-listing-grid' },
    ],

    init: function () {
        let me = this;

        me.control({
            'mxc-dsi-article-listing-grid': {
                mxcSaveArticle:                 me.onSaveArticle,
                mxcImportItems:                 me.onImportItems,
                mxcRemapProperties:             me.onRemapProperties,
                mxcRemapPropertiesSelected:     me.onRemapPropertiesSelected,
                mxcSetActiveSelected:           me.onSetActiveSelected,
                mxcSetAcceptedSelected:         me.onSetAcceptedSelected,
                mxcSetLinkedSelected:           me.onSetLinkedSelected,
                mxcRefreshItems:                me.onRefreshItems,
                mxcCheckNameMappingConsistency: me.onCheckNameMappingConsistency,
                mxcCheckRegularExpressions:     me.onCheckRegularExpressions,
                mxcExportConfig:                me.onExportConfig
            }
        });
        me.mainWindow = me.getView('list.Window').create({}).show();
    },

    onExportConfig: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=exportConfig}';
        let params = {};
        let growlTitle = 'Export article configuration';
        let maskText = 'Exporting article configuration ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRefreshItems: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=refresh}';
        let params = {};
        let growlTitle = 'Refresh link state';
        let maskText = 'Refreshing articles ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onImportItems: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=import}';
        let params = {};
        let growlTitle = 'Update';
        let maskText = 'Updating articles from InnoCigs ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemapProperties: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=remap}';
        let params = {};
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying article property mapping ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemapPropertiesSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiArticle action=remapSelected}';
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying article property mapping ...';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onCheckRegularExpressions: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=checkRegularExpressions}';
        let params = {};
        let growlTitle = 'Check regular expresions';
        let maskText = 'Checking regular expresions ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onCheckNameMappingConsistency: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiArticle action=checkNameMappingConsistency}';
        let params = {};
        let growlTitle = 'Check name mapping consistency';
        let maskText = 'Checking name mapping consistency ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onSetActiveSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'active';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Activating articles.' : 'Deactivating articles.';
        let growlTitle = value ? 'Activate selected' : 'Deactivate selected';
        me.setStateSelected(grid, field, value, growlTitle, maskText);
    },

    onSetAcceptedSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'accepted';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Setting articles to accepted ...' : 'Setting articles to ignored ...';
        let growlTitle = value ? 'Accept selected' : 'Ignore selected';
        me.setStateSelected(grid, field, value, growlTitle, maskText);
    },

    onSetLinkedSelected: function(grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'linked';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Creating Shopware articles ...' : 'Deleting Shopware articles ...';
        let growlTitle = value ? 'Create Shopware Article' : 'Delete Shopware Article';
        me.setStateSelected(grid, field, value, growlTitle, maskText);
    },

    setStateSelected: function (grid, field, value, growlTitle, maskText) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiArticle action=setStateSelected}';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            field: field,
            value: value,
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onSaveArticle: function (record) {
        let me = this;
        record.save({
            success: function (record, operation) {
                console.log('Success (onSaveArticle)');
                console.log(record);
                if (operation.success) {
                    // Update the modified record by the data, the controller returned
                    // This way we make sure, that the record shows the data which is stored
                    // in the database
                    Ext.each(Object.keys(record.getData()), function (key) {
                        record.set(key, operation.records[0].data[key]);
                    });
                }
            },
            failure: function (record, operation) {
                console.log('Failure (onSaveArticle)');
                console.log(record);
                me.handleError(record, operation);
            }
        });
    },

    doRequest: function(grid, url, params, growlTitle, maskText, reloadGrid) {
        let me = this;
        let mask = new Ext.LoadMask(grid, { msg: maskText });
        mask.show();
        console.log(url);
        Ext.Ajax.request({
            method: 'POST',
            url: url,
            params: params,

            success: function (response) {
                mask.hide();
                let result = Ext.JSON.decode(response.responseText);
                console.log(result);
                if (!result) {
                    me.showError(response.responseText);
                } else if (result.success) {
                    Shopware.Notification.createGrowlMessage(growlTitle, result.message);
                    if (reloadGrid === true) {
                        grid.store.load();
                    }
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

    showError: function (message) {
        Shopware.Notification.createStickyGrowlMessage({
                title: 'Error',
                text: message,
                log: true
            },
            'MxcDropshipInnoCigs');
    },

    handleError: function (record, operation) {
        let me = this;

        let rawData = operation.records[0].proxy.reader.rawData;
        let message = '{s name=unknownError}An unknown error occurred, please check your server logs.{/s}';
        if (rawData.message) {
            record.set('active', false);
            message = rawData.message;
        }
        me.showError(message);
    },
});
