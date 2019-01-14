//

Ext.define('Shopware.apps.MxcDsiTest.view.detail.InnocigsGroup', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-innocigs-group-detail-container',
    title: 'InnoCigs Configurator Group',
    height : 240,

    configure: function() {
        return {
            controller: 'MxcDsiTest',
            associations: [ 'options' ]
        };
    }
});