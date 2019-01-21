//{namespace name=backend/mxc_dsi_test/view/list/innocigs_group}
//{block name="backend/mxc_dsi_test/view/mxc_dsi_test/view/list/window"}
Ext.define('Shopware.apps.MxcDsiTest.view.detail.Option', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-dsi-test-option-listing-grid',
    region: 'center',

    snippets: {
        options: {
            acceptedOptions: '{s name=innocigs/configurator/option/accepted_options_header}Accepted options{/s}',
            ignoredOptions: '{s name=innocigs/configurator/option/ignored_options_header}Ignored options{/s}',
            selected: '{s name=innocigs/configurator/option/option_header_selected}selected{/s}',
        }
    },

    initComponent: function() {
        let me = this;
        me.listeners = {
            cellclick: function(view, td, cellIndex, record) {
                if (cellIndex === 0 && record.get('accepted') === true) {
                    let records = me.store.data.items;
                    Shopware.Notification.createGrowlMessage('Store', records.length + ' items.');
                    me.fireEvent('mxcSelectOption', record, false);
                }
            },
            viewready: function(view, opts) {
                let selected = [];
                console.log(me);
                console.log(me.getStore().data.items);

                me.store.each(function(record) {
                    console.log(record);
                    if (record.get('accepted') === true) {
                        selected.push(record);
                    }
                });
                Shopware.Notification.createGrowlMessage('Store', selected.length + ' options.');
                if (selected.length > 0) {
                    me.getSelectionModel().select(selected, true, true);
                }
            }
        };
        me.callParent(arguments);
    },

    configure: function() {
        let me = this;
        return {
            detailWindow: 'Shopware.apps.MxcDsiTest.view.detail.Window',
            columns: {
                name:       { header: 'Name', flex: 3 }
            },
            toolbar: false,
            actionColumn: false,
            pagingbar: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            /**
             * @event mxcSelectGroup
             */
            'mxcSelectOption',
        );
    },

    createSelectionModel: function () {
        let me = this;
        return Ext.create('Ext.selection.CheckboxModel', {
            checkOnly: true,
            showHeaderCheckbox: false,
            listeners: {
                select: function (sm, record) {
                    let success = me.fireEvent('mxcSelectOption', record, true);
                    if (success === false) {
                        sm.deselect(record, true);
                    }
                },
            }
        });
    },

    createFeatures: function() {
        let me = this;
        let items = me.callParent(arguments);

        me.groupingFeature =  Ext.create('Ext.grid.feature.Grouping', {
            groupHeaderTpl: Ext.create('Ext.XTemplate',
                '<span>{ name:this.formatHeader }</span>',
                '<span>&nbsp;({ rows.length } ' + me.snippets.options.selected + ')</span>',
                {
                    formatHeader: function(accepted) {
                        if (accepted === true || accepted === 'true') {
                            return me.snippets.options.acceptedOptions;
                        } else {
                            return me.snippets.options.ignoredOptions;
                        }
                    }
                }
            ),
            // hideGroupedHeader: true,
            // startCollapsed: false
        });

        items.push(me.groupingFeature);
        return items;
    },
});
//{/block}