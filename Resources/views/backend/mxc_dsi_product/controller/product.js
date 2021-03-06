Ext.define('Shopware.apps.MxcDsiProduct.controller.Product', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'productListing', selector: 'mxc-dsi-product-list-window mxc-dsi-product-listing-grid' },
    ],

    init: function () {
        let me = this;

        me.control({
            'mxc-dsi-product-listing-grid': {
                mxcSaveProduct:                      me.onSaveProduct,
                mxcImportProducts:                   me.onImportProducts,

                mxcRemoveObsoleteProducts:           me.onRemoveObsoleteProducts,

                // meta information

                mxcUpdateSupplierSeo:                me.onUpdateSupplierSeo,
                mxcUpdateCategorySeo:                me.onUpdateCategorySeo,
                mxcSaveCategorySeo:                  me.onSaveCategorySeo,
                mxcRebuildArticleSeo:                me.onRebuildArticleSeo,
                mxcUpdateArticleSeo:                 me.onUpdateArticleSeo,
                mxcSetLastStock:                     me.onSetLastStock,

                mxcEnableDropship:                  me.onEnableDropship,

                // Remapping

                mxcRemapCategories:                  me.onRemapCategories,
                mxcComputeCategories:                me.onComputeCategories,
                mxcRemapProperties:                  me.onRemapProperties,
                mxcRemapDescriptions:                me.onRemapDescriptions,
                mxcPushAssociatedProducts:           me.onPushAssociatedProducts,
                mxcPullAssociatedProducts:           me.onPullAssociatedProducts,
                mxcComputeAssociatedProducts:        me.onComputeAssociatedProducts,
                mxcUpdateAssociatedLiguids:          me.onUpdateAssociatedLiquids,
                mxcSetReferencePrices:               me.onSetReferencePrices,
                mxcCheckSupplierLogo:                me.onCheckSupplierLogo,

                mxcPullShopwareDescriptions:         me.onPullShopwareDescriptions,
                mxcUpdateImages:                     me.onUpdateImages,
                mxcRelink:                           me.onRelink,

                mxcSetActiveSelected:                me.onSetActiveSelected,
                mxcSetAcceptedSelected:              me.onSetAcceptedSelected,
                mxcSetLinkedSelected:                me.onSetLinkedSelected,

                mxcSaveEmailTemplates:               me.onSaveEmailTemplates,
                mxcRestoreEmailTemplates:            me.onRestoreEmailTemplates,

                mxcUpdatePrices:                     me.onUpdatePrices,
                mxcUpdateStockInfo:                  me.onUpdateStockInfo,
                mxcUpdateVat:                        me.onUpdateVat,
                mxcCreateAll:                        me.onCreateAll,
                mxcDeleteAll:                        me.onDeleteAll,
                mxcRemoveEmptyCategories:            me.onRemoveEmptyCategories,
                mxcRefreshItems:                     me.onRefreshItems,
                mxcExportConfig:                     me.onExportConfig,

                // Consistency checks

                mxcCheckVariantsWithoutOptions:      me.onCheckVariantsWithoutOptions,
                mxcCheckVariantMappingConsistency:   me.onCheckVariantMappingConsistency,
                mxcCheckNameMappingConsistency:      me.onCheckNameMappingConsistency,
                mxcCheckRegularExpressions:          me.onCheckRegularExpressions,
                mxcCheckMissingModels:               me.onCheckMissingModels,
                mxcCheckArticlesWithoutProducts:     me.onCheckArticlesWithoutProducts,

                // Excel importFromApi/export

                mxcExcelExportPrices:                me.onExcelExportPrices,
                mxcExcelExportPriceIssues:           me.onExcelExportPriceIssues,
                mxcZipExportSupplierLogos:           me.onZipExportSupplierLogos,
                mxcExcelExportEcigMetaData:          me.onExcelExportEcigMetaData,
                mxcExcelImportPrices:                me.onExcelImportPrices,
                mxcCsvExportCustomers:               me.onCsvExportCustomers,
                mxcArrayExportDocumentationTodos:    me.onArrayExportDocumentationTodos,

                // InnoCigs importFromApi tests

                mxcTestImport1:                      me.onTestImport1,
                mxcTestImport2:                      me.onTestImport2,
                mxcTestImport3:                      me.onTestImport3,
                mxcTestImport4:                      me.onTestImport4,
                mxcTestImport5:                      me.onTestImport5,
                mxcTestImport6:                      me.onTestImport6,

                // database schema
                mxcUpdateSchema:                     me.onUpdateSchema,

                // for development/test purposes
                mxcDev1:                             me.onDev1,
                mxcDev2:                             me.onDev2,
                mxcDev3:                             me.onDev3,
                mxcDev4:                             me.onDev4,
                mxcDev5:                             me.onDev5,
                mxcDev6:                             me.onDev6,
                mxcDev7:                             me.onDev7,
                mxcDev8:                             me.onDev8,
            }
        });
        me.mainWindow = me.getView('list.Window').create({}).show();
    },

    onRemoveObsoleteProducts: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=removeObsoleteProducts}';
        let params = {};
        let growlTitle = 'Remove obsolete products';
        let maskText = 'Removing obsolete products ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onEnableDropship: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=enableDropship}';
        let params = {};
        let growlTitle = 'Enable dropship';
        let maskText = 'Enabling dropship for all imported variants ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExportConfig: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=exportConfig}';
        let params = {};
        let growlTitle = 'Export product configuration';
        let maskText = 'Exporting product configuration ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateSchema: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updateSchema}';
        let params = {};
        let growlTitle = 'Update database schema';
        let maskText = 'Updating database schema ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelExportPrices: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=excelExportPrices}';
        let params = {};
        let growlTitle = 'Prices: Excel export';
        let maskText = 'Exporting prices to Excel ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onZipExportSupplierLogos: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=zipExportSupplierLogos}';
        let params = {};
        let growlTitle = 'Supplier Logos: Zip export';
        let maskText = 'Exporting supplier logos to Zip ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onCsvExportCustomers: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=csvExportCustomers}';
        let params = {};
        let growlTitle = 'Customers CSV Export';
        let maskText = 'Exporting customer to CSV ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onArrayExportDocumentationTodos: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=arrayExportDocumentationTodos}';
        let params = {};
        let growlTitle = 'Export documentation todos';
        let maskText = 'Exporting documentation todos ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelExportPriceIssues: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=excelExportPriceIssues}';
        let params = {};
        let growlTitle = 'Price Issues: Excel export';
        let maskText = 'Exporting price issues to Excel ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelExportEcigMetaData: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=excelExportEcigMetaData}';
        let params = {};
        let growlTitle = 'ECig Meta Data: Excel export';
        let maskText = 'Exporting ecig metadata to Excel ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onExcelImportPrices: function(grid, file) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=excelImportPrices}';
        let growlTitle = 'Import prices';
        let maskText = 'Importing prices from Excel file ...';
        me.excelImportSheet(grid, file, url, growlTitle, maskText)
    },

    excelImportSheet: function (grid, file, url, growlTitle, maskText) {
        let me = this;

        if (file.type !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'){
            me.showError('Please select a valid importFromApi file (.xlsx)');
            return;
        }

        let fileForm = new FormData();
        fileForm.append('file', file, file.name);

        me.doSubmit(grid, url, fileForm, growlTitle, maskText, true);
    },

    onRefreshItems: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=refresh}';
        let params = {};
        let growlTitle = 'Refresh link state';
        let maskText = 'Refreshing products ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onSaveEmailTemplates: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=saveEmailTemplates}';
        let params = {};
        let growlTitle = 'Save email templates';
        let maskText = 'Saving email templates ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRestoreEmailTemplates: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=restoreEmailTemplates}';
        let params = {};
        let growlTitle = 'Restore email templates';
        let maskText = 'Restoring email templates ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateCategorySeo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapCategorySeoInformation}';
        let params = {};
        let growlTitle = 'Update category SEO information';
        let maskText = 'Updating category SEO information ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onSaveCategorySeo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=pullCategorySeoInformation}';
        let params = {};
        let growlTitle = 'Save category SEO information';
        let maskText = 'Saving category SEO information ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateSupplierSeo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapSupplierSeoInformation}';
        let params = {};
        let growlTitle = 'Update supplier SEO information';
        let maskText = 'Updating supplier SEO information ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRebuildArticleSeo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=rebuildProductSeoInformation}';
        let growlTitle = 'Rebuild product SEO information';
        let maskText = 'Rebuilding product SEO information ...';
        let params = {};
        let selModel = grid.getSelectionModel();
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onUpdateArticleSeo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapProductSeoInformation}';
        let growlTitle = 'Update product SEO information';
        let maskText = 'Updating product SEO information ...';
        let params = {};
        let selModel = grid.getSelectionModel();
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onSetLastStock: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=setLastStock}';
        let growlTitle = 'Globally set laststock';
        let maskText = 'Setting laststock globally ...';
        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onImportProducts: function (grid, sequential) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=import}';
        let params = {
            sequential: sequential ? 1 : 0
        };
        let growlTitle = 'Update';
        let maskText = 'Updating products from InnoCigs ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdatePrices: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updatePrices}';
        let params = {};
        let growlTitle = 'Update';
        let maskText = 'Updating product prices from InnoCigs ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateVat: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updateVat}';
        let params = {};
        let growlTitle = 'Update VAT';
        let maskText = 'Updating VAT for all products ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateStockInfo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updateStockInfo}';
        let params = {};
        let growlTitle = 'Update';
        let maskText = 'Updating stock info from InnoCigs ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },


    onCreateAll: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=createAll}';
        let params = {};
        let growlTitle = 'Create all Shopware articles';
        let maskText = 'Creating all Shopware articles ...';
        me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
    },

    onDeleteAll: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=deleteAll}';
        let params = {};
        let growlTitle = 'Delete all Shopware articles';
        let maskText = 'Delete all Shopware articles ...';
        me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
    },

    onRemapProperties: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapSelected}';
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying product property mapping ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onPullAssociatedProducts: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=pullAssociatedProducts}';
        let growlTitle = 'Pull associated products';
        let maskText = 'Pulling associated products ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onPushAssociatedProducts: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=pushAssociatedProducts}';
        let growlTitle = 'Push associated products';
        let maskText = 'Pushing associated products ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onComputeAssociatedProducts: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=computeAssociatedProducts}';
        let growlTitle = 'Compute associated products';
        let maskText = 'Computing associated products ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateAssociatedLiquids: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updateAssociatedLiquids}';
        let growlTitle = 'Update associated products for liquids';
        let maskText = 'Updating associated products for liquids ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemapDescriptions: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapDescriptions}';
        let growlTitle = 'Remap properties';
        let maskText = 'Reapplying product property mapping ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onSetReferencePrices: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=setReferencePrices}';
        let growlTitle = 'Set reference prices';
        let maskText = 'Setting reference prices ...';

        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onCheckSupplierLogo: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkSupplierLogo}';
        let growlTitle = 'Get suppliers without logo';
        let maskText = 'Getting suppliers without Logo ...';

        let params = {};
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onPullShopwareDescriptions: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=pullShopwareDescriptions}';
        let growlTitle = 'Pull Shopware descriptions';
        let maskText = 'Pulling Shopware descriptions ...';

        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
        }
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onUpdateImages: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=updateImages}';
        let growlTitle = 'Update images';
        let maskText = 'Updating images ...';
        let selModel = grid.getSelectionModel();

        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
        }
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onRemoveEmptyCategories: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=removeEmptyCategories}';
        let params = {};
        let growlTitle = 'Remove empty categories';
        let maskText = 'Removing empty categories ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onComputeCategories: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=computeCategories}';
        let growlTitle = 'Compute categories';
        let maskText = 'Recalculatings categories ...';
        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onRemapCategories: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=remapCategories}';
        let growlTitle = 'Remap categories';
        let maskText = 'Remapping categories ...';
        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    onCheckArticlesWithoutProducts: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkArticlesWithoutProducts}';
        let params = {};
        let growlTitle = 'Find articles without $products';
        let maskText = 'Searching articles without $products ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onCheckRegularExpressions: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkRegularExpressions}';
        let params = {};
        let growlTitle = 'Check regular expresions';
        let maskText = 'Checking regular expresions ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onCheckMissingModels: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkMissingModels}';
        let params = {};
        let growlTitle = 'Check missing models';
        let maskText = 'Checking missing models ...';
        me.doRequest(grid, url, params, growlTitle, maskText, false);
    },

    onCheckVariantsWithoutOptions: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkVariantsWithoutOptions}';
        let params = {};
        let growlTitle = 'Check variants without options';
        let maskText = 'Looking for variants without options ...';
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

    onCheckVariantMappingConsistency: function(grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=checkVariantMappingConsistency}';
        let params = {};
        let growlTitle = 'Check variant mapping consistency';
        let maskText = 'Checking variant mapping consistency ...';
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

    onTestImport5: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport5}';
        let params = {};
        let growlTitle = 'Importing test data';
        let maskText = 'Importing test data  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onTestImport6: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=testImport6}';
        let params = {};
        let growlTitle = 'Importing test data';
        let maskText = 'Importing test data  ...';
        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onSetActiveSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'active';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Activating products.' : 'Deactivating products.';
        let growlTitle = value ? 'Activate selected' : 'Deactivate selected';
        let url = '{url controller=MxcDsiProduct action=activateSelectedProducts}';
        me.setStateSelected(grid, field, value, growlTitle, maskText, url);
    },

    onSetAcceptedSelected: function (grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'accepted';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Setting products to accepted ...' : 'Setting products to ignored ...';
        let growlTitle = value ? 'Accept selected' : 'Ignore selected';
        let url = '{url controller=MxcDsiProduct action=acceptSelectedProducts}';
        me.setStateSelected(grid, field, value, growlTitle, maskText, url);
    },

    onSetLinkedSelected: function(grid) {
        let me = this;
        let selectionModel = grid.getSelectionModel();
        let field = 'linked';
        let value = selectionModel.getSelection()[0].get(field);
        let maskText = value ? 'Creating Shopware articles ...' : 'Deleting Shopware articles ...';
        let growlTitle = value ? 'Create Shopware Article' : 'Delete Shopware Article';
        let url = '{url controller=MxcDsiProduct action=linkSelectedProducts}';
        me.setStateSelected(grid, field, value, growlTitle, maskText, url);
    },

    onRelink: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=relinkProducts}';
        let growlTitle = 'Recreate Shopware articles';
        let maskText = 'Recreating Shopware articles ...';
        let selModel = grid.getSelectionModel();
        let params = {};
        if (selModel.getCount() > 0) {
            params = {
                ids: me.getSelectedIds(grid.getSelectionModel())
            };
            me.doRequest(grid, url, params, growlTitle, maskText, true);
        } else {
            me.doRequestConfirm(grid, url, params, growlTitle, maskText, true);
        }
    },

    setStateSelected: function (grid, field, value, growlTitle, maskText, url) {
        let me = this;

        let params = {
            field: field,
            value: value,
            ids: me.getSelectedIds(grid.getSelectionModel())
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
        let url = '{url controller=MxcDsiProduct action=dev5}';
        let growlTitle = 'Development 5';
        let maskText = 'Development 5 on selected ...';

        let params = {
            ids: me.getSelectedIds(grid.getSelectionModel())
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onDev6: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev6}';
        let growlTitle = 'Development 6';
        let maskText = 'Development 6 on selected ...';

        let params = {
            ids: me.getSelectedIds(grid.getSelectionModel())
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onDev7: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev7}';
        let growlTitle = 'Development 7';
        let maskText = 'Development 7 on selected ...';

        let params = {
            ids: me.getSelectedIds(grid.getSelectionModel())
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    onDev8: function (grid) {
        let me = this;
        let url = '{url controller=MxcDsiProduct action=dev8}';
        let growlTitle = 'Development 8';
        let maskText = 'Development 8 on selected ...';

        let params = {
            ids: me.getSelectedIds(grid.getSelectionModel())
        };

        me.doRequest(grid, url, params, growlTitle, maskText, true);
    },

    getSelectedIds: function (selectionModel) {
        let ids = [];
        Ext.each(selectionModel.getSelection(), function (record) {
            ids.push(record.get('id'));
        });
        console.log(ids);
        return Ext.JSON.encode(ids);
    },

    doRequestConfirm: function(grid, url, params, growlTitle, maskText, reloadGrid) {
        let me = this;
        Ext.MessageBox.confirm(growlTitle, 'Für alle Produkte durchführen?', function (response) {
            if (response !== 'yes') {
                return false;
            }
            me.doRequest(grid, url, params, growlTitle, maskText, reloadGrid);
        });
    },

    doRequest: function(grid, url, params, growlTitle, maskText, reloadGrid) {
        let me = this;
        let mask = new Ext.LoadMask(grid, { msg: maskText });
        console.log(url);
        mask.show();
        Ext.Ajax.request({
            method: 'POST',
            url: url,
            params: params,

            success: function (response) {
                mask.hide();
                console.log(response.responseText);
                let result = Ext.JSON.decode(response.responseText);
                console.log(result);
                if (!result) {
                    me.showError(response.responseText);
                } else if (result.success) {
                    if (result.value != null) {
                        let textvalue = Array.isArray(result.value)? result.value.join('<br>') : result.value;

                        resultWindow = new Ext.Window({
                            height: 400,
                            width: 300,
                            autoScroll: true,
                            title: result.message,
                            buttons: [
                                {
                                    text: 'OK',
                                    handler: function () { this.up('window').close(); }
                                }
                            ],
                            html: textvalue
                        });
                        resultWindow.show();
                    } else {
                        Shopware.Notification.createGrowlMessage(growlTitle, result.message);
                    }
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

    doSubmit: function(grid, url, form, growlTitle, maskText, reloadGrid) {
        let me = this,
          request = new XMLHttpRequest(),
          responseText;

        let mask = new Ext.LoadMask(grid, { msg: maskText });
        mask.show();
        console.log(url);

        request.onload = function() {
            mask.hide();

            if (request.status === 200) {
                try {
                    responseText = Ext.JSON.decode(request.response);
                    console.log(responseText);
                } catch (exception) {
                    me.showError('An unknown error occurred, please check your server logs.');
                    return;
                }

                if (!responseText.success) {
                    if (responseText.message) {
                        me.showError(responseText.message);
                    } else {
                        me.showError('An unknown error occurred, please check your server logs.');
                    }
                } else {
                    Shopware.Notification.createGrowlMessage(growlTitle, responseText.message);
                    if (reloadGrid === true) {
                        grid.store.load();
                    }
                }
            }
        };

        request.open('POST', url, true);
        request.setRequestHeader('X-CSRF-Token', Ext.CSRFService.getToken());
        request.send(form);
    },

    showError: function (message) {
        Shopware.Notification.createStickyGrowlMessage({
              title: 'Error',
              text: message,
              log: true
          },
          'MxcDropshipIntegrator');
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
    }

});
