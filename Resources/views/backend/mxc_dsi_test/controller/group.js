
Ext.define('Shopware.apps.MxcDsiTest.controller.Group', {
    extend: 'Enlight.app.Controller',

    refs: [
        { ref: 'groupListing', selector: 'mxc-dsi-test-group-list-window mxc-dsi-test-group-listing-grid' },
        { ref: 'optionListing', selector: 'mxc-dsi-test-group-detail-window mxc-dsi-test-option-listing-grid'}
    ],

    init: function() {
        let me = this;

        me.control({
            'mxc-dsi-test-group-listing-grid': {
                mxcSaveGroup:       me.onGroupSave,
                mxcSelectGroup:     me.onGroupSelect,
            },
            'mxc-dsi-test-option-listing-grid': {
                mxcSelectOption:    me.onOptionSelect,
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
        if (record.get('active') === true) {
            record.set('accepted', true);
        }
        record.save({
            params: {
                 resource: 'innocigs_group'
            },
            success: function(record, operation) {
                if (operation.success) {
                    // Update the modified record with data returned by the php controller
                    // to make sure, that the grid shows the data actually stored in the db
                    Ext.each(Object.keys(record.getData()), function (key) {
                        record.set(key, operation.records[0].data[key]);
                    });
                }
            },
            failure: function() {
                Shopware.Notification.createStickyGrowlMessage({
                        title: 'Error',
                        text: 'An unknown error occurred, please check your server logs',
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
        me.sortGrid('group');
        return true;
    },

    onOptionSelect: function(record, value) {
        Shopware.Notification.createGrowlMessage('Option', 'ImportOption selected: ' + (value === true ? 'true' : 'false'));
        let me = this;
        record.set('accepted', value);
        me.sortGrid('option');
        return true;
    },

    sortGrid: function(selector) {
        let listing = selector === 'option' ? this.getOptionListing() : this.getGroupListing();
        listing.getStore().sort([{ property: 'accepted', 'direction': 'DESC' }]);
    }

});