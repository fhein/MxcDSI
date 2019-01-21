//

Ext.define('Shopware.apps.MxcDsiGroup.view.detail.Group', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-dsi-group-detail-container',
    title: 'InnoCigs Configurator Group',
    height : 240,

    configure: function() {
        return {
            controller: 'MxcDsiGroup',
            associations: [ 'options' ]
        };
    }
});