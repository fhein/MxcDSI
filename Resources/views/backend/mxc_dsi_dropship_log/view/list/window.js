Ext.define('Shopware.apps.MxcDsiDropshipLog.view.list.Window', {
  extend: 'Shopware.window.Listing',
  alias: 'widget.mxc-dsi-dropship-log-list-window',
  height: 450,
  width: 1200,
  title : 'Dropship Integrator Log',

  configure: function() {
    return {
      listingGrid: 'Shopware.apps.MxcDsiDropshipLog.view.list.Log',
      listingStore: 'Shopware.apps.MxcDsiDropshipLog.store.Log',
    };
  }
});