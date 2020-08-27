Ext.define('Shopware.apps.MxcDsiDropshipLog', {
  extend: 'Enlight.app.SubApplication',

  name:'Shopware.apps.MxcDsiDropshipLog',

  loadPath: '{url action=load}',
  bulkLoad: true,

  controllers: [ 'Log' ],

  views: [
    'list.Log',
    'list.Window',
  ],

  models: [ 'Log' ],
  stores: [ 'Log' ],

  launch: function() {
    return this.getController('Log').mainWindow;
  }
});