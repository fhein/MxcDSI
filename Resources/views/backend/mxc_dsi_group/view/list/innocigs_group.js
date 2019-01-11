//

Ext.define('Shopware.apps.MxcDsiGroup.view.list.InnocigsGroup', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.mxc-innocigs-group-listing-grid',
    region: 'center',

    snippets: {
        groups: {
            acceptedGroups: '{s name=innocigs/configurator/group/active_groups_header}Accepted groups{/s}',
            ignoredGroups: '{s name=innocigs/configurator/group/inactive_groups_header}Ignored groups{/s}',
            selected: '{s name=innocigs/configurator/group/group_header_selected}selected{/s}',
        }
    },

    listeners: {
        cellclick: function(view, td, cellIndex, record) {
            if (cellIndex === 0 && record.get('active') === true) {
                me.fireEvent('mxcDeselectGroup', record, me);
            }
        }
    },

    configure: function() {
        return {
            detailWindow: 'Shopware.apps.MxcDsiGroup.view.detail.Window',
            columns: {
                name:       { header: 'Name', flex: 3 },
                accepted:   { header: 'accept', width:60, flex: 0}
            },
            toolbar: false,
            deleteColumn: false
        };
    },

    registerEvents: function() {
        let me = this;
        me.callParent(arguments);
        me.addEvents(
            /**
             * @event mxcSaveGroup
             */
            'mxcSaveGroup',
            /**
             * @event mxcSelectGroup
             */
            'mxcSelectGroup',
            /**
             * @event mxcDeselectGroup
             */
            'mxcDeselectGroup',
        );
    },

    createSelectionModel: function () {
        let me = this;

        return Ext.create('Ext.selection.CheckboxModel', {
            checkOnly: true,
            showHeaderCheckbox: false,
            listeners: {
                select: function (sm, record) {
                    let success = me.fireEvent('mxcSelectGroup', record, me);
                    if (success === false) {
                        sm.deselect(record, true);
                    }
                }
            }
        });
    },

    createFeatures: function() {
        let me = this;
        let items = me.callParent(arguments);

        me.groupingFeature =  Ext.create('Ext.grid.feature.Grouping', {
            groupHeaderTpl: Ext.create('Ext.XTemplate',
                '<span>{ name: this.formatHeader }</span>',
                '<span>&nbsp;({ rows.length } ' + me.snippets.groups.selected + ')</span>',
                {
                    // @todo: continue here, next try: remove accepted column
                    formatHeader: function(accepted) {
                        return 'bla';
                        if (accepted === true || accepted === 'true') {
                            return me.snippets.groups.acceptedGroups;
                        } else {
                            return me.snippets.groups.ignoredGroups;
                        }
                    }
                }
            ),
            hideGroupedHeader: true,
            startCollapsed: false
        });

        items.push(me.groupingFeature);
        return items;
    },

    createPlugins: function () {
        let me = this;
        let items = me.callParent(arguments);

        me.cellEditor = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners: {
                beforeedit: function(editor, e) {
                    return (e.column.text === 'accept');
                },
                edit: function(editor, e) {
                    // the 'edit' event gets fired even if the new value equals the old value
                    if (e.originalValue === e.value) {
                        return;
                    }
                    me.fireEvent('mxcSaveGroup', e.record);
                }
            }
        });
        items.push(me.cellEditor);

        return items;
    },

    destroy: function() {
        let me = this;
        // If the window gets closed while the cell editor is active
        // an exception gets thrown. This is a workaround for that problem.
        me.cellEditor.completeEdit();
        me.callParent(arguments);
    }

});
