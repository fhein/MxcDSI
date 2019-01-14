//{namespace name=backend/mxc_dsi_test/view/configurator}
//{block name="backend/mxc_dsi_test/view/configurator/window"}
Ext.define('Shopware.apps.MxcDsiGroup.view.configurator.Window', {
    extend: 'Enlight.app.windows',
    alias: 'widget.mxc-innocigs-configurator-window',
    border: false,
    autoShow: true,
    layout: 'fit',
    width: '80%',
    height: '90%',
    maximizable: true,
    minimizable: true,
    stateful: false,
    stateId: 'mxc-innocigs-configurator-window',
    snippets: {
        title: '{s name=window_title}Configurator{/s}'
    },

    initComponent: function() {

    },


    title : '{s name=window_title}InnoCigs Configurator{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MxcDsiGroup.view.list.InnocigsGroup',
            listingStore: 'Shopware.apps.MxcDsiGroup.store.InnocigsGroup',
        };
    }
});
//{/block}