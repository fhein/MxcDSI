
Ext.define('Shopware.apps.MxcDsiGroup.controller.Main', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'groupListing', selector: 'mxc-innocigs-group-list-window mxc-innocigs-group-listing-grid' },
    ],

    init: function() {
        let me = this;

        me.control({
            'mxc-innocigs-group-listing-grid': {
                mxcSaveGroup:       me.onGroupSave,
                mxcSelectGroup:     me.onGroupSelect,
                mxcDeselectGroup:   me.onGroupDeselect,
            }
        });
        Shopware.app.Application.on('innocigsgroup-save-successfully', me.onDetailSaved);
        me.mainWindow = me.getView('list.Window').create({ }).show();
    },

    /**
     * Called after the user edited a cell in the main grid
     *
     * @param record
     */
    onGroupSave: function(record) {
        if (record.get('active') === true) {
            record.set('accepted', true);
        }
        record.save({
            params: {
                 resource: 'innocigs_group'
            },
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
                        title: '{s name=error}Error{/s}',
                        text: '{s name=unknownError}An unknown error occurred, please check your server logs{/s}',
                        log: true
                    },
                    'InnocigsGroup'
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