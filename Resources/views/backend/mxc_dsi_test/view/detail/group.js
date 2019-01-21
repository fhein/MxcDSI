//

Ext.define('Shopware.apps.MxcDsiTest.view.detail.Group', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-test-group-detail-container',
    title: 'InnoCigs Configurator Group',
    height : 240,

    configure: function() {
        return {
            controller: 'MxcDsiTest',
            associations: [ 'options' ]
        };
    }
});