//

Ext.define('Shopware.apps.InnocigsGroup.view.detail.InnocigsGroup', {
    extend: 'Shopware.model.Container',
    alias: 'widget.mxc-innocigs-group-detail-container',
    title: 'InnoCigs Configurator Group',
    height : 300,

    configure: function() {
        return {
            controller: 'InnocigsGroup',
            associations: [ 'options' ]
        };
    }
});