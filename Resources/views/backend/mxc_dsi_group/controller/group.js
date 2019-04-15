
Ext.define('Shopware.apps.MxcDsiGroup.controller.Group', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'groupListing', selector: 'mxc-dsi-group-list-window mxc-dsi-group-listing-grid' },
    ],

    init: function() {
        let me = this;

        me.control({
            'mxc-dsi-group-listing-grid': {
                mxcSaveGroup:       me.onGroupSave,
                mxcSelectGroup:     me.onGroupSelect,
            }
        });
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onGroupSave: function(record) {
        record.save({
            // @todo: Error handling as in article controller
            success: function(record, operation) {
                if (operation.success) {
                    // Update the modified record by the data, the controller returned
                    // This way we make sure, that the record shows the data which is stored
                    // in the database
                    Ext.each(Object.keys(record.getData()), function (key) {
                        record.set(key, operation.records[0].data[key]);
                    });
                }
            },
            failure: function(record, operation) {
                Shopware.Notification.createStickyGrowlMessage({
                        title: 'Error',
                        text: 'An unknown error occurred, please check your server logs.',
                        log: true
                    },
                    'Group'
                );
            }
        });
    },

    onGroupSelect: function(record, value) {
        let me = this;
        record.set('accepted', value);
        me.onGroupSave(record);
        me.sortGroupGrid();
        return true;
    },

    /**
     * Internal helper function to sort the configurator group grid.
     */
    sortGroupGrid: function() {
        let me = this;
        let groupListing = me.getGroupListing();
        groupListing.getStore().sort([
            { property: 'accepted', 'direction': 'DESC' }

        ]);
    },

});