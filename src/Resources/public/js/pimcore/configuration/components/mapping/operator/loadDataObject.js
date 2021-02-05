pimcore.registerNS("pimcore.plugin.pimcoreDataHubBatchImportBundle.configuration.components.mapping.operator.loadDataObject");
pimcore.plugin.pimcoreDataHubBatchImportBundle.configuration.components.mapping.operator.loadDataObject = Class.create(pimcore.plugin.pimcoreDataHubBatchImportBundle.configuration.components.mapping.abstractOperator, {

    type: 'loadDataObject',
    dataApplied: false,


    setLanguageVisibility: function(attributeStore, attributeSelection, languageSelection) {
        const record = attributeStore.findRecord('key', attributeSelection.getValue());
        if(record) {

            languageSelection.setHidden(!record.data.localized);
            if(!record.data.localized) {
                languageSelection.setValue(null);
            }
        }
    },

    getFormItems: function() {

        this.data.settings = this.data.settings || {};

        const languageSelection = Ext.create('Ext.form.ComboBox', {
            store: pimcore.settings.websiteLanguages,
            forceSelection: true,
            fieldLabel: t('language'),
            name: 'settings.attributeLanguage',
            value: this.data.settings.attributeLanguage,
            allowBlank: true,
            width: 400,
            hidden: true,
            listeners: {
                change: this.inputChangePreviewUpdate.bind(this)
            },
        });

        const attributeName = Ext.create('Ext.form.ComboBox', {
            fieldLabel: t('plugin_pimcore_datahub_batch_import_configpanel_attribute_name'),
            name: 'settings.attributeName',
            hidden: this.data.settings.loadStrategy !== 'attribute',
            allowBlank: true,
            value: this.data.settings.attributeName,
            displayField: 'title',
            valueField: 'key',
            width: 400,
            forceSelection: true,
            queryMode: 'local',
            listeners: {
                change: this.inputChangePreviewUpdate.bind(this)
            },
        });


        const attributeStore = Ext.create('Ext.data.JsonStore', {
            fields: ['key', 'name', 'localized'],
            autoLoad: true,
            proxy: {
                type: 'ajax',
                extraParams: {
                    class_id: this.data.settings.attributeDataObjectClassId,
                    system_read: 1
                },
                url: Routing.generate('pimcore_datahubbatchimport_configdataobject_loaddataobjectattributes'),
                reader: {
                    type: 'json',
                    rootProperty: 'attributes'
                }
            },

            listeners: {
                dataChanged: function(store) {
                    if(!this.dataApplied) {
                        attributeName.setValue(this.data.settings.attributeName);
                        this.form.isValid();
                        this.dataApplied = true;
                        this.setLanguageVisibility(attributeStore, attributeName, languageSelection);
                    }
                }.bind(this)
            }
        });

        attributeName.setStore(attributeStore);
        attributeName.on('change', this.setLanguageVisibility.bind(this, attributeStore, attributeName, languageSelection));


        const attributeDataObjectClassId = Ext.create('Ext.form.field.ComboBox', {
            typeAhead: true,
            triggerAction: 'all',
            store: pimcore.globalmanager.get('object_types_store'),
            valueField: 'id',
            displayField: 'text',
            listWidth: 'auto',
            fieldLabel: t('class'),
            width: 400,
            name: 'settings.attributeDataObjectClassId',
            value:  this.data.settings.attributeDataObjectClassId,
            hidden: this.data.settings.loadStrategy !== 'attribute',
            allowBlank: true, // this.data.findStrategy !== 'attribute',
            forceSelection: true,
            listeners: {
                change: function(combo, newValue, oldValue) {
                    attributeStore.proxy.setExtraParam('class_id', newValue);
                    attributeStore.load();
                    attributeName.setValue(null);
                }.bind(this)
            }
        });

        return [
            {
                xtype: 'combo',
                fieldLabel: t('plugin_pimcore_datahub_batch_import_configpanel_transformation_pipeline_dataobject_load_strategy'),
                name: 'settings.loadStrategy',
                value: this.data.settings.loadStrategy || 'id',
                store: [
                    ['id', t('plugin_pimcore_datahub_batch_import_configpanel_find_strategy_id')],
                    ['path', t('plugin_pimcore_datahub_batch_import_configpanel_find_strategy_path')],
                    ['attribute', t('plugin_pimcore_datahub_batch_import_configpanel_find_strategy_attribute')]
                ],
                listeners: {
                    change: function(combo, strategy) {
                        const attributeFields = [attributeDataObjectClassId, attributeName];
                        if(strategy === 'attribute') {
                            attributeFields.forEach(function(item) {
                                item.setHidden(false);
                            });
                        } else {
                            attributeFields.forEach(function(item) {
                                item.setValue('');
                                item.setHidden(true);
                            });
                            this.inputChangePreviewUpdate();
                        }
                    }.bind(this)
                }
            },
            attributeDataObjectClassId,
            attributeName,
            languageSelection
        ];
    }

});