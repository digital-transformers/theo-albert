(function () {
    'use strict';

    var processCodes = null;
    var loading = false;

    function loadProcessCodes() {
        if (loading || processCodes !== null || typeof Ext === 'undefined') {
            return;
        }
        loading = true;
        Ext.Ajax.request({
            url: '/admin/sap-sync/process-codes',
            method: 'GET',
            success: function (response) {
                try {
                    var payload = Ext.decode(response.responseText);
                    processCodes = (payload.processes || []).map(function (process) {
                        return process.code;
                    }).filter(Boolean);
                } catch (error) {
                    processCodes = [];
                    console.warn('[supplier-process-types] Invalid Process response', error);
                }
            },
            failure: function () {
                processCodes = [];
                console.warn('[supplier-process-types] Could not load Process codes');
            },
            callback: function () {
                loading = false;
            }
        });
    }

    function patchRelationEditor() {
        var relationClass = window.pimcore
            && pimcore.object
            && pimcore.object.tags
            && pimcore.object.tags.advancedManyToManyObjectRelation;
        if (!relationClass || relationClass.prototype.__supplierProcessTypesPatched) {
            return false;
        }

        var originalInitialize = relationClass.prototype.initialize;
        relationClass.prototype.initialize = function (data, fieldConfig) {
            if (fieldConfig && fieldConfig.name === 'suppliers' && Array.isArray(fieldConfig.columns)) {
                fieldConfig.columns.forEach(function (column) {
                    if (column.key === 'supplierType' && processCodes && processCodes.length) {
                        // The standard metadata multiselect consumes a semicolon-separated value list.
                        column.value = processCodes.join(';');
                    }
                });
            }

            return originalInitialize.call(this, data, fieldConfig);
        };
        relationClass.prototype.__supplierProcessTypesPatched = true;

        return true;
    }

    function initialize() {
        try {
            if (typeof Ext === 'undefined' || typeof pimcore === 'undefined') {
                window.setTimeout(initialize, 250);
                return;
            }
            loadProcessCodes();
            if (!patchRelationEditor()) {
                window.setTimeout(initialize, 250);
            }
        } catch (error) {
            console.warn('[supplier-process-types] Initialization failed', error);
            window.setTimeout(initialize, 500);
        }
    }

    initialize();
}());
