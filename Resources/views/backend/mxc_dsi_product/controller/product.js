Ext.define('Shopware.apps.MxcDsiProduct.controller.Product', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'productListing', selector: 'mxc-dsi-product-list-window mxc-dsi-product-listing-grid' },
    ],

    init: function () {
        let me = this;

        me.control({
            'mxc-dsi-product-listing-grid': {
                mxcSaveProduct:                 me.onSaveProduct,
                mxcImportItems:                 me.onImportItems,
                mxcRemapProperties:             me.onRemapProperties,
                mxcRemapPropertiesSelected:     me.onRemapPropertiesSelected,
                mxcSetActiveSelected:           me.onSetActiveSelected,
                mxcSetAcceptedSelected:         me.onSetAcceptedSelected,
                mxcSetLinkedSelected:           me.onSetLinkedSelected,
                mxcRefreshItems:                me.onRefreshItems,
                mxcCheckNameMappingConsistency: me.onCheckNameMappingConsistency,
                mxcCheckRegularExpressions:     me.onCheckRegularExpressions,
                mxcExportConfig:                me.onExportConfig,
                mxcExcelExport:                 me.onExcelExport,
                mxcExcelImport:                 me.onExcelImport,

                mxcTestImport1:                 me.onTestImport1,
                mxcTestImport2:                 me.onTestImport2,
                mxcTestImport3:                 me.onTestImport3,
                mxcTestImport4:                 me.onTestImport4,

                // for development/test purposes
                mxcDev1:                        me.onDev1,
                mxcDev2:                        me.onDev2,
                mxcDev3:                        me.onDev3,
                mxcDev4:                        me.onDev4,
                mxcDev5:                        me.onDev5,
                mxcDev6:                        me.onDev6,
                mxcDev7:                        me.onDev7,
                mxcDev8:                        me.onDev8,
            }
        });
        me.mainWindow = me.getView('list.Window').create({}).show();
    },

    onExportConfig: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=exportConfig}';
        let params = {};
        let growlTitle = 'Export product configuration';
        let maskText = 'Exporting product configuration ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelExport: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=excelExport}';
        let params = {};
        let growlTitle = 'Excel export';
        let maskText = 'Exporting to Excel ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelImport: function (grid, files) {
        debugger;
        //let me = this;
        //uploadUrl: '{url action=singleReplace controller=MediaManager}'
        let url = '{url controller=MxcDsiProduct action=excelImport}';

        //var testKatrin2 = fileInput.fileInputEl.dom.files;
        //me.value[me.index]
        //var testKatrin = fileSelection.value;
/*
        let params = {
            filename: filePath
        };
        let growlTitle = 'Excel Import';
        let maskText = 'Importing from Excel ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
        */

        let me = this,
            fileForm = new FormData(),
            scope = me,
            request;

        let file = files[0];

        /* necessary if callback function is needed
        if (!Ext.isFunction(callback)) {
            callback = Ext.emptyFn
        }*/

        //request = me.createRequest(scope)//(callback, scope);
        request = new XMLHttpRequest();

        fileForm.append('file', file, file.name);

        request.open('POST', url, true);
        request.setRequestHeader('X-CSRF-Token', Ext.CSRFService.getToken());
        request.send(fileForm);
    },

    createRequest: function(scope){ //(callback, scope) {
        var me = this,
            request = new XMLHttpRequest(),
            responseText;

        request.onload = function() {
            debugger;
            if (request.status === 200) {
                try {
                    responseText = Ext.JSON.decode(request.response);
                } catch (exception) {
                    // me.showMessage(me.config.fileUploadErrorMessageTitle, request.response);
                    me.showMessage(me.config.fileUploadErrorMessageTitle, me.config.fileUploadErrorMessage);
                    me.fireEvent('upload-error', me, me.value[me.index], me.index);
                    return;
                }

                if (!responseText.success) {
                    if (responseText.exception) {
                        switch (responseText.exception['_class']) {
                            case 'Shopware\\Bundle\\MediaBundle\\Exception\\WrongMediaTypeForReplaceException':
                                me.showMessage(
                                    me.config.fileUploadErrorMessageTitle,
                                    Ext.String.format(me.config.fileUploadWrongTypeErrorMessage, responseText.exception.requiredType)
                                );
                                break;
                            case 'Shopware\\Bundle\\MediaBundle\\Exception\\MediaFileExtensionIsBlacklistedException':
                            case 'Shopware\\Bundle\\MediaBundle\\Exception\\MediaFileExtensionNotAllowedException':
                                me.showMessage(
                                    me.config.fileUploadErrorMessageTitle,
                                    Ext.String.format(me.config.fileUploadExtensionError, responseText.exception.extension)
                                );
                                break;
                        }
                    } else {
                        me.showMessage(me.config.fileUploadErrorMessageTitle, responseText.message);
                    }

                    me.fireEvent('upload-error', me, me.value[me.index], me.index);
                    return;
                }

                me.fireEvent('upload-fileUploaded', me, me.value[me.index], me.index);
                //Ext.callback(callback, scope);

            } else {
                me.index = null;
                me.showMessage(me.config.fileUploadErrorMessageTitle, me.config.fileUploadErrorMessage);
                me.fireEvent('upload-error', me, me.value[me.index], me.index);
            }
        };

        return request;
    },

    onRefreshItems: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=refresh}';
        let params = {};
        let growlTitle = 'Refresh link state';
        let maskText = 'Refreshing products ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onImportItems: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=import}';
        let params = {};
        let growlTitle = 'Update';
        let maskText = 'Updating products from InnoCigs ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemapProperties: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remap}';
        let params = {};
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying product property mapping ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemapPropertiesSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiProduct action=remapSelected}';
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying product property mapping ...';

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
        let url = '{url controller=MxcDsiProduct action=checkRegularExpressions}';
        let params = {};
        let growlTitle = 'Check regular expresions';
        let maskText = 'Checking regular expresions ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onCheckNameMappingConsistency: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkNameMappingConsistency}';
        let params = {};
        let growlTitle = 'Check name mapping consistency';
        let maskText = 'Checking name mapping consistency ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onTestImport1: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport1}';
        let params = {};
        let growlTitle = 'Importing test data';
        let maskText = 'Importing test data  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onTestImport2: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport2}';
        let params = {};
        let growlTitle = 'Importing value changes';
        let maskText = 'Importing value changes  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onTestImport3: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport3}';
        let params = {};
        let growlTitle = 'Import variant changes';
        let maskText = 'Importing variant changes  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onTestImport4: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport4}';
        let params = {};
        let growlTitle = 'Import empty product list';
        let maskText = 'Importing empty product list  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },


    onSetActiveSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'active';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Activating products.' : 'Deactivating products.';
        let growlTitle = value ? 'Activate selected' : 'Deactivate selected';
        me.setStateSelected(grid, field, value, growlTitle, maskText);
    },

    onSetAcceptedSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'accepted';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Setting products to accepted ...' : 'Setting products to ignored ...';
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
        let url = '{url controller=MxcDsiProduct action=setStateSelected}';

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
    onSaveProduct: function (record) {
        let me = this;
        record.save({
            success: function (record, operation) {
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
        let message = 'An unknown error occurred, please check your server logs.';
        if (rawData.message) {
            record.set('active', false);
            message = rawData.message;
        }
        me.showError(message);
    },

    onDev1: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev1}';
        let params = {};
        let growlTitle = 'Development 1';
        let maskText = 'Performing development 1  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev2: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev2}';
        let params = {};
        let growlTitle = 'Development 2';
        let maskText = 'Performing development 2  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev3: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev3}';
        let params = {};
        let growlTitle = 'Development 3';
        let maskText = 'Performing development 3  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev4: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev4}';
        let params = {};
        let growlTitle = 'Development 4';
        let maskText = 'Performing development 4  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev5: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiProduct action=dev5}';
        let growlTitle = 'Development 5';
        let maskText = 'Development 5 on selected ...';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev6: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiProduct action=dev6}';
        let growlTitle = 'Development 6';
        let maskText = 'Development 6 on selected ...';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev7: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiProduct action=dev7}';
        let growlTitle = 'Development 7';
        let maskText = 'Development 7 on selected ...';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },
    onDev8: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let url = '{url controller=MxcDsiProduct action=dev8}';
        let growlTitle = 'Development 8';
        let maskText = 'Development 8 on selected ...';

        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });

        let params = {
            ids: Ext.JSON.encode(ids)
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

});
