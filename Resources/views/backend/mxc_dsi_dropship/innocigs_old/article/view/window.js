//{block name="backend/article/view/detail/window"}
//{$smarty.block.parent}
Ext.define('Shopware.apps.Article.view.detail.MxcDropshipIntegrator', {
    override: 'Shopware.apps.Article.view.detail.Window',
    createBaseTab: function() {

        let me = this,
            panelTab = me.callParent(arguments);

        me.mxc_dsi_ic_active = Ext.create('Ext.form.field.Checkbox', {
            name: 'attribute[mxc_dsi_ic_active]',
            fieldLabel: 'Aktivieren',
            inputValue: true,
            uncheckedValue: false,
            labelWidth: 155
        });

        me.mxc_dsi_ic_preferownstock = Ext.create('Ext.form.field.Checkbox', {
            name: 'attribute[mxc_dsi_ic_preferownstock]',
            fieldLabel: 'Eigenes Lager bevorzugen',
            inputValue: true,
            uncheckedValue: false,
            labelWidth: 155
        });

        me.mxc_dsi_ic_productnumber = Ext.create('Ext.form.field.Text', {
            name: 'attribute[mxc_dsi_ic_productnumber]',
            fieldLabel: 'Artikelnummer',
            labelWidth: 155
        });

        me.mxc_dsi_ic_productname = Ext.create('Ext.form.field.Text', {
            name: 'attribute[mxc_dsi_ic_productname]',
            readOnly: true,
            fieldLabel: 'Artikelbezeichnung',
            labelWidth: 155,
            width: 450
        });

        me.mxc_dsi_ic_purchaseprice = Ext.create('Ext.form.field.Text', {
            name: 'attribute[mxc_dsi_ic_purchaseprice]',
            readOnly: true,
            fieldLabel: 'Einkaufspreis',
            labelWidth: 155
        });

        me.mxc_dsi_ic_retailprice = Ext.create('Ext.form.field.Text', {
            name: 'attribute[mxc_dsi_ic_retailprice]',
            readOnly: true,
            fieldLabel: 'Unverbindliche Preisempfehlung',
            labelWidth: 155
        });

        me.mxc_dsi_ic_instock = Ext.create('Ext.form.field.Text', {
            name: 'attribute[mxc_dsi_ic_instock]',
            decimalPrecision: 0,
            readOnly: true,
            fieldLabel: 'Bestand',
            labelWidth: 155
        });

        me.saveButton = Ext.create('Ext.button.Button', {
            text: 'Übernehmen',
            cls: 'primary',
            style : {
                'float' : 'right'
            },
            listeners: {
                click: function(editor, e) {

                    if (me.article.data.mainDetailId == null) {
                        Shopware.Notification.createGrowlMessage('Fehler', 'Sie haben einen neuen Artikel angelegt aber nicht nicht gespeichert. Sie können einen Dropshipping-Artikel erst hinzufügen, sobald Sie den Artikel gespeichert haben.', 'MxcDropshipIntegrator');
                        return;
                    }

                    let productNumber = me.mxc_dsi_ic_productnumber.getValue();
                    let active = me.mxc_dsi_ic_active.getValue();
                    active = active ? 1 : 0;
                    let preferOwnStock = me.mxc_dsi_ic_preferownstock.getValue();
                    preferOwnStock = preferOwnStock ? 1 : 0;
                    console.log(preferOwnStock);

                    if (productNumber === '') {
                        Shopware.Notification.createGrowlMessage('Fehler', 'Bitte geben Sie eine Artikelnummer an', 'MxcDropshipIntegrator');
                        me.mxc_dsi_ic_ordernumber.focus();
                        return;
                    }

                    me.setLoading(true);

                    Ext.Ajax.request({
                        method: 'POST',
                        url: '{url controller=MxcDsiArticleInnocigs action=register}',
                        params: {
                            articleId: me.article.data.mainDetailId,
                            productNumber: productNumber,
                            active: active,
                            preferOwnStock: preferOwnStock
                        },
                        success: function(responseData, request) {
                            let response = Ext.JSON.decode(responseData.responseText);
                            me.setLoading(false);
                            if (response.success === false) {
                                if (response.info !== '') {
                                    Shopware.Notification.createGrowlMessage(response.info.title, response.info.message, 'MxcDropshipIntegrator');
                                }
                            } else {

                                let overwritePurchaseprice = true;
                                if (overwritePurchaseprice) {
                                    document.getElementsByName('mainDetail[purchasePrice]')[0].value = response.data.mxc_dsi_ic_purchaseprice;
                                }

                                let mxc_dsi_ic_purchaseprice = response.data.mxc_dsi_ic_purchaseprice;
                                if (mxc_dsi_ic_purchaseprice == null) {
                                    mxc_dsi_ic_purchaseprice = 'Kein Preis gefunden!';
                                }

                                let mxc_dsi_ic_retailprice = response.data.mxc_dsi_ic_retailprice;
                                if (mxc_dsi_ic_retailprice == null) {
                                    mxc_dsi_ic_retailprice = 'Kein Preis gefunden!';
                                }

                                me.mxc_dsi_ic_productname.setValue(response.data.mxc_dsi_ic_productname);
                                me.mxc_dsi_ic_purchaseprice.setValue(mxc_dsi_ic_purchaseprice);
                                me.mxc_dsi_ic_retailprice.setValue(mxc_dsi_ic_retailprice);
                                me.mxc_dsi_ic_instock.setValue(response.data.mxc_dsi_ic_instock);
                                Shopware.Notification.createGrowlMessage('Erfolg', 'Dropship erfolgreich registriert.', 'MxcDropshipIntegrator');
                            }
                        },
                        failure: function(responseData, request) {
                            me.setLoading(false);
                            Shopware.Notification.createGrowlMessage('Fehler', 'Daten konnten nicht gespeichert werden.', 'MxcDropshipIntegrator');
                        }
                    });
                }
            }
        });

        me.removeButton = Ext.create('Ext.button.Button', {
            text: 'Löschen',
            cls: 'secondary',
            style : {
                'float' : 'right'
            },
            listeners: {
                click: function(editor, e) {

                    if (me.article.data.mainDetailId == null) {
                        Shopware.Notification.createGrowlMessage('Fehler', 'Sie haben einen neuen Artikel angelegt aber nicht nicht gespeichert. Sie können einen Dropshipping-Artikel erst hinzufügen, sobald Sie den Artikel gespeichert haben.', 'MxcDropshipIntegrator');
                        return;
                    }

                    let productNumber = me.mxc_dsi_ic_productnumber.getValue();
                    if (productNumber === '') {
                        return;
                    }

                    me.setLoading(true);

                    Ext.Ajax.request({
                        method: 'POST',
                        url: '{url controller=MxcDsiArticleInnocigs action=unregister}',
                        params: {
                            articleId: me.article.data.mainDetailId
                        },
                        success: function(responseData, request) {
                            let response = Ext.JSON.decode(responseData.responseText);
                            me.mxc_dsi_ic_active.setValue(0);
                            me.mxc_dsi_ic_preferownstock.setValue(0);
                            me.mxc_dsi_ic_productnumber.setValue('');
                            me.mxc_dsi_ic_productname.setValue('');
                            me.mxc_dsi_ic_purchaseprice.setValue('');
                            me.mxc_dsi_ic_retailprice.setValue('');
                            me.mxc_dsi_ic_instock.setValue('');
                            me.setLoading(false);
                            Shopware.Notification.createGrowlMessage('Erfolgreich', 'Dropship Konfiguration gelöscht.', 'MxcDropshipIntegrator');
                        },
                        failure: function(responseData, request) {
                            me.setLoading(false);
                            Shopware.Notification.createGrowlMessage('Fehler', 'Daten konnten nicht gespeichert werden.', 'MxcDropshipIntegrator');
                        }
                    });
                }
            }
        });

        me.fieldset = Ext.create('Ext.form.FieldSet', {
            layout: 'anchor',
            title: 'maxence Dropship Integrator / InnoCigs',
            items: [
                me.mxc_dsi_ic_active,
                me.mxc_dsi_ic_preferownstock,
                me.mxc_dsi_ic_productnumber,
                me.mxc_dsi_ic_productname,
                me.mxc_dsi_ic_purchaseprice,
                me.mxc_dsi_ic_retailprice,
                me.mxc_dsi_ic_instock,
                me.saveButton,
                me.removeButton
            ]
        });

        me.detailForm.insert(6, me.fieldset);
        return panelTab;
    },

    onStoresLoaded: function() {
        let me = this,
            panelTab = me.callParent(arguments);

        Ext.Ajax.request({
            url: '{url controller=MxcDsiArticleInnocigs action=getSettings}',
            params: {
                articleId: me.article.get('mainDetailId')
            },
            success: function(responseData, request) {
                let response = Ext.JSON.decode(responseData.responseText);
                if (response.success) {
                    me.mxc_dsi_ic_active.setValue(response.data.mxc_dsi_ic_active);
                    me.mxc_dsi_ic_preferownstock.setValue(response.data.mxc_dsi_ic_preferownstock);
                    me.mxc_dsi_ic_productnumber.setValue(response.data.mxc_dsi_ic_productnumber);
                    me.mxc_dsi_ic_productname.setValue(response.data.mxc_dsi_ic_productname);
                    me.mxc_dsi_ic_purchaseprice.setValue(response.data.mxc_dsi_ic_purchaseprice);
                    me.mxc_dsi_ic_retailprice.setValue(response.data.mxc_dsi_ic_retailprice);
                    me.mxc_dsi_ic_instock.setValue(response.data.mxc_dsi_ic_instock);
                }
            }
        });
    }
});
//{/block}