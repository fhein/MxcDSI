Ext.define('Shopware.apps.MxcDsiDropshipLog.model.Log', {
  extend: 'Shopware.data.Model',

  configure: function() {
    return {
      controller: 'MxcDsiDropshipLog',
    };
  },

  fields: [
    { name : 'id', type: 'int', useNull: true },
    { name : 'level', type: 'integer' },
    { name : 'module', type: 'string' },
    { name : 'message', type: 'string' },
    { name : 'orderNumber', type: 'string', useNull: true },
    { name : 'productNumber', type: 'string', useNull: true },
    { name : 'quantity', type: 'integer', useNull: true },
    { name : 'created', type: 'datetime'}
  ],
});
