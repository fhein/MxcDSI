Ext.define('Shopware.apps.MxcDsiDropshipLog.store.Log', {
  extend:'Shopware.store.Listing',
  model: 'Shopware.apps.MxcDsiDropshipLog.model.Log',

  configure: function() {
    return {
      controller: 'MxcDsiDropshipLog'
    };
  }
});