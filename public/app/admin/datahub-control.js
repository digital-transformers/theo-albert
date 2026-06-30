console.log('[datahub-control] loaded');

if (window.pimcore?.settings?.csrfToken) {
  Ext.Ajax.defaultHeaders = Ext.Ajax.defaultHeaders || {};
  Ext.Ajax.defaultHeaders['X-pimcore-csrf-token'] = pimcore.settings.csrfToken;
}

pimcore.registerNS('app.datahub');
app.datahub = app.datahub || {};

app.datahub.createQueuePanel = function () {
  return {
    xtype: 'panel',
    title: 'DataHub Queue',
    iconCls: 'pimcore_icon_import',
    border: false,
    layout: 'vbox',
    bodyStyle: 'padding:12px;',
    items: [
      {
        xtype: 'toolbar',
        width: '100%',
        items: [
          { xtype: 'textfield', id: 'dhc-profile', width: 220, emptyText: 'profile (optional)' },
          { xtype: 'textfield', id: 'dhc-extra', width: 280, emptyText: 'extra args (e.g. --limit=500)' },
          {
            text: 'Start',
            iconCls: 'pimcore_icon_apply',
            handler: function () {
              const profile = Ext.getCmp('dhc-profile').getValue() || 'default';
              const extra = Ext.getCmp('dhc-extra').getValue() || '';
              Ext.Ajax.request({
                url: '/admin/datahub-supervisor/start',
                method: 'POST',
                params: { profile, extra },
                success: function () { pimcore.helpers.showNotification('OK', 'Started', 'success'); },
                failure: function () { pimcore.helpers.showNotification('Error', 'Start failed', 'error'); }
              });
            }
          },
          {
            text: 'Stop',
            iconCls: 'pimcore_icon_stop',
            handler: function () {
              Ext.Ajax.request({
                url: '/admin/datahub-supervisor/stop',
                method: 'POST',
                success: function () { pimcore.helpers.showNotification('OK', 'Stopped', 'success'); },
                failure: function () { pimcore.helpers.showNotification('Error', 'Stop failed', 'error'); }
              });
            }
          },
          '-',
          { text: 'Refresh', iconCls: 'pimcore_icon_reload', handler: app.datahub.refreshStatusOnce },
          { xtype: 'checkbox', id: 'dhc-autorefresh', boxLabel: 'Auto-refresh', checked: true },
          '->',
          {
            text: 'Clear Log',
            iconCls: 'pimcore_icon_delete',
            handler: function () {
              const box = Ext.getCmp('dhc-log');
              if (box) box.setValue('');
              const auto = Ext.getCmp('dhc-autorefresh');
              const previous = auto && auto.getValue();
              if (auto) auto.setValue(false);
              Ext.Ajax.request({
                url: '/admin/datahub-supervisor/log/clear',
                method: 'POST',
                success: function () {
                  setTimeout(function () { if (auto) auto.setValue(previous); }, 800);
                  pimcore.helpers.showNotification('OK', 'Log cleared', 'success');
                },
                failure: function () {
                  if (auto) auto.setValue(previous);
                  pimcore.helpers.showNotification('Error', 'Failed to clear log', 'error');
                }
              });
            }
          }
        ]
      },
      {
        xtype: 'displayfield',
        id: 'dhc-status',
        width: '100%',
        fieldLabel: 'Workers',
        labelWidth: 70,
        value: '-'
      },
      { xtype: 'textarea', id: 'dhc-log', width: '100%', flex: 1, readOnly: true }
    ]
  };
};

app.datahub.prestashopStatus = function (value) {
  const colors = {
    completed: '#2e7d32',
    completed_with_errors: '#a45a00',
    failed: '#b3261e',
    syncing: '#1565c0',
    converting: '#1565c0',
    queued: '#5f6368'
  };
  const label = String(value || 'unknown').replaceAll('_', ' ');
  const color = colors[value] || '#5f6368';
  return '<span style="font-weight:600;color:' + color + ';">' +
    Ext.util.Format.htmlEncode(label) + '</span>';
};

app.datahub.prestashopEntitySummary = function (value) {
  if (!value) return '-';
  value = value || {};
  return [
    Number(value.created || 0) + ' new',
    Number(value.updated || 0) + ' updated',
    Number(value.failed || 0) + ' failed'
  ].join(' / ');
};

app.datahub.prestashopDetailHtml = function (payload) {
  const status = payload.status || {};
  const report = payload.report || {};
  const conversion = report.conversion || {};
  const summary = conversion.summary || status.conversion_summary || {};
  const sync = report.sync || status.sync || {};
  const errors = sync.errors || [];
  const encode = Ext.util.Format.htmlEncode;
  const row = function (label, value) {
    return '<tr><th>' + encode(label) + '</th><td>' + encode(String(value ?? '-')) + '</td></tr>';
  };

  let html = '<div class="prestashop-import-detail">';
  html += '<table>';
  html += row('Job', status.job_id);
  html += row('File', status.filename);
  html += row('Status', String(status.status || '').replaceAll('_', ' '));
  html += row('Created', status.created_at);
  html += row('Completed', status.completed_at);
  html += row('Model limit', status.selection?.model_limit || '-');
  html += row('Model filters', status.selection?.models || '-');
  html += row('Source records',
    (summary.source_family_records || 0) + ' families / ' +
    (summary.source_model_records || 0) + ' models / ' +
    (summary.source_product_records || 0) + ' products'
  );
  html += row('Output records',
    (summary.output_family_records || 0) + ' families / ' +
    (summary.output_model_records || 0) + ' models / ' +
    (summary.output_frame_records || 0) + ' frames'
  );
  html += row('Families', app.datahub.prestashopEntitySummary(sync.families));
  html += row('Models', app.datahub.prestashopEntitySummary(sync.models));
  html += row('Frames', app.datahub.prestashopEntitySummary(sync.frames));
  html += row('Errors', errors.length || sync.error_count || 0);
  if (status.error) html += row('Failure', status.error);
  html += '</table>';

  if (errors.length) {
    html += '<h3>Errors</h3><div class="prestashop-import-errors">';
    errors.slice(0, 100).forEach(function (error) {
      html += '<div><strong>' + encode(error.entity || 'import') + ' ' +
        encode(error.code || '') + '</strong>: ' + encode(error.message || '') + '</div>';
    });
    if (errors.length > 100) {
      html += '<div>Only the first 100 errors are shown.</div>';
    }
    html += '</div>';
  }

  return html + '</div>';
};

app.datahub.createPrestaShopPanel = function () {
  const store = Ext.create('Ext.data.Store', {
    storeId: 'prestashop-import-jobs-store',
    fields: [
      'job_id', 'filename', 'status', 'created_at', 'completed_at',
      'stage', 'current', 'total', 'conversion_summary', 'sync', 'selection', 'error'
    ],
    data: []
  });

  return {
    xtype: 'panel',
    id: 'prestashop-import-panel',
    title: 'PrestaShop',
    iconCls: 'pimcore_icon_cart',
    border: false,
    layout: 'border',
    listeners: {
      activate: function () {
        app.datahub.refreshPrestaShopJobs();
        app.datahub.startPrestaShopPolling();
      },
      deactivate: app.datahub.stopPrestaShopPolling
    },
    items: [
      {
        xtype: 'form',
        id: 'prestashop-import-form',
        region: 'north',
        height: 68,
        border: false,
        bodyPadding: '10 12',
        layout: 'hbox',
        items: [
          {
            xtype: 'hiddenfield',
            name: 'csrfToken',
            value: pimcore.settings.csrfToken
          },
          {
            xtype: 'filefield',
            name: 'file',
            id: 'prestashop-import-file',
            width: 390,
            fieldLabel: 'Export ZIP',
            labelWidth: 76,
            buttonText: 'Choose',
            allowBlank: false,
            accept: '.zip,application/zip'
          },
          {
            xtype: 'numberfield',
            name: 'modelLimit',
            width: 145,
            fieldLabel: 'Limit',
            labelWidth: 38,
            minValue: 1,
            allowDecimals: false,
            allowBlank: true,
            margin: '0 0 0 10'
          },
          {
            xtype: 'textfield',
            name: 'models',
            width: 310,
            fieldLabel: 'Models',
            labelWidth: 48,
            emptyText: 'codes or exact names, comma-separated',
            margin: '0 0 0 10'
          },
          {
            xtype: 'button',
            text: 'Import',
            iconCls: 'pimcore_icon_upload',
            margin: '0 0 0 8',
            handler: function () {
              const form = Ext.getCmp('prestashop-import-form').getForm();
              if (!form.isValid()) return;
              form.submit({
                url: '/admin/datahub-supervisor/prestashop/import',
                params: { csrfToken: pimcore.settings.csrfToken },
                waitMsg: 'Uploading PrestaShop export...',
                success: function () {
                  form.reset();
                  pimcore.helpers.showNotification('PrestaShop', 'Import queued', 'success');
                  app.datahub.refreshPrestaShopJobs();
                },
                failure: function (_form, action) {
                  const message = action.result?.message || 'Import could not be started';
                  pimcore.helpers.showNotification('PrestaShop', message, 'error');
                }
              });
            }
          },
          {
            xtype: 'button',
            text: 'Refresh',
            iconCls: 'pimcore_icon_reload',
            margin: '0 0 0 8',
            handler: app.datahub.refreshPrestaShopJobs
          },
          {
            xtype: 'checkbox',
            id: 'prestashop-import-autorefresh',
            boxLabel: 'Auto-refresh',
            checked: true,
            margin: '5 0 0 14'
          }
        ]
      },
      {
        xtype: 'grid',
        id: 'prestashop-import-grid',
        region: 'center',
        store: store,
        border: false,
        columnLines: true,
        viewConfig: { emptyText: 'No PrestaShop imports yet.', deferEmptyText: false },
        columns: [
          { text: 'Started', dataIndex: 'created_at', width: 170 },
          { text: 'File', dataIndex: 'filename', minWidth: 180, flex: 1 },
          {
            text: 'Status',
            dataIndex: 'status',
            width: 155,
            renderer: app.datahub.prestashopStatus
          },
          {
            text: 'Progress',
            width: 135,
            renderer: function (_value, _meta, record) {
              const current = Number(record.get('current') || 0);
              const total = Number(record.get('total') || 0);
              if (total) return current + ' / ' + total + ' ' + (record.get('stage') || '');

              const status = record.get('status');
              if (status === 'queued') return 'Waiting';
              if (status === 'converting') return 'Reading export';
              if (status === 'syncing') return 'Preparing sync';

              return '-';
            }
          },
          {
            text: 'Families',
            width: 190,
            renderer: function (_value, _meta, record) {
              return app.datahub.prestashopEntitySummary(record.get('sync')?.families);
            }
          },
          {
            text: 'Models',
            width: 190,
            renderer: function (_value, _meta, record) {
              return app.datahub.prestashopEntitySummary(record.get('sync')?.models);
            }
          },
          {
            text: 'Frames',
            width: 190,
            renderer: function (_value, _meta, record) {
              return app.datahub.prestashopEntitySummary(record.get('sync')?.frames);
            }
          },
          {
            text: 'Errors',
            width: 70,
            align: 'right',
            renderer: function (_value, _meta, record) {
              return Number(record.get('sync')?.error_count || (record.get('error') ? 1 : 0));
            }
          }
        ],
        listeners: {
          selectionchange: function (_selectionModel, records) {
            if (records.length) app.datahub.loadPrestaShopJob(records[0].get('job_id'));
          }
        }
      },
      {
        xtype: 'panel',
        id: 'prestashop-import-detail',
        title: 'Import Summary',
        region: 'south',
        split: true,
        height: 260,
        scrollable: true,
        bodyPadding: 12,
        html: '<div class="prestashop-import-empty">Select an import to see its summary.</div>'
      }
    ]
  };
};

app.datahub.getPanel = function () {
  const existing = Ext.getCmp('datahub-control-panel');
  if (existing) return existing;

  const panel = Ext.create('Ext.panel.Panel', {
    id: 'datahub-control-panel',
    title: 'DataHub Import Control',
    iconCls: 'pimcore_icon_import',
    closable: true,
    layout: 'fit',
    items: [{
      xtype: 'tabpanel',
      border: false,
      items: [
        app.datahub.createQueuePanel(),
        app.datahub.createPrestaShopPanel()
      ]
    }]
  });

  app.datahub.startPolling();
  return panel;
};

(function () {
  let queueTimer = null;
  let prestaShopTimer = null;

  function pollQueue() {
    try {
      const auto = Ext.getCmp('dhc-autorefresh');
      if (!auto || !auto.getValue()) {
        queueTimer = setTimeout(pollQueue, 2000);
        return;
      }

      app.datahub.refreshStatusOnce();
      Ext.Ajax.request({
        url: '/admin/datahub-supervisor/log?lines=400',
        method: 'GET',
        success: function (response) {
          try {
            const data = Ext.decode(response.responseText);
            const box = Ext.getCmp('dhc-log');
            if (box) {
              box.setValue(data.log || '');
              const element = box.inputEl && box.inputEl.dom;
              if (element) element.scrollTop = element.scrollHeight;
            }
          } catch (_error) {}
        },
        callback: function () { queueTimer = setTimeout(pollQueue, 2000); }
      });
    } catch (_error) {
      queueTimer = setTimeout(pollQueue, 2500);
    }
  }

  function pollPrestaShop() {
    const panel = Ext.getCmp('prestashop-import-panel');
    const auto = Ext.getCmp('prestashop-import-autorefresh');
    if (!panel || !auto || !auto.getValue()) {
      prestaShopTimer = setTimeout(pollPrestaShop, 3000);
      return;
    }
    app.datahub.refreshPrestaShopJobs(function () {
      prestaShopTimer = setTimeout(pollPrestaShop, 3000);
    });
  }

  app.datahub.startPolling = function () {
    if (queueTimer) clearTimeout(queueTimer);
    queueTimer = setTimeout(pollQueue, 800);
  };

  app.datahub.refreshStatusOnce = function () {
    Ext.Ajax.request({
      url: '/admin/datahub-supervisor/status',
      method: 'GET',
      success: function (response) {
        try {
          const data = Ext.decode(response.responseText);
          const html = (data.workers || []).map(function (worker) {
            return Ext.util.Format.htmlEncode(worker.name) + ': ' +
              Ext.util.Format.htmlEncode(worker.state);
          }).join('<br>') || 'No data';
          const status = Ext.getCmp('dhc-status');
          if (status) status.setValue(html);
        } catch (_error) {}
      }
    });
  };

  app.datahub.refreshPrestaShopJobs = function (callback) {
    Ext.Ajax.request({
      url: '/admin/datahub-supervisor/prestashop/jobs?limit=100',
      method: 'GET',
      success: function (response) {
        try {
          const data = Ext.decode(response.responseText);
          const store = Ext.data.StoreManager.lookup('prestashop-import-jobs-store');
          const grid = Ext.getCmp('prestashop-import-grid');
          const selectedId = grid?.getSelectionModel().getSelection()[0]?.get('job_id');
          if (store) store.loadData(data.jobs || []);
          if (selectedId && grid) {
            const record = store.findRecord('job_id', selectedId, 0, false, true, true);
            if (record) grid.getSelectionModel().select(record);
          }
        } catch (_error) {}
      },
      callback: callback
    });
  };

  app.datahub.loadPrestaShopJob = function (jobId) {
    Ext.Ajax.request({
      url: '/admin/datahub-supervisor/prestashop/jobs/' + encodeURIComponent(jobId),
      method: 'GET',
      success: function (response) {
        try {
          const data = Ext.decode(response.responseText);
          const detail = Ext.getCmp('prestashop-import-detail');
          if (detail) detail.update(app.datahub.prestashopDetailHtml(data));
        } catch (_error) {}
      }
    });
  };

  app.datahub.startPrestaShopPolling = function () {
    if (prestaShopTimer) clearTimeout(prestaShopTimer);
    prestaShopTimer = setTimeout(pollPrestaShop, 1000);
  };

  app.datahub.stopPrestaShopPolling = function () {
    if (prestaShopTimer) clearTimeout(prestaShopTimer);
    prestaShopTimer = null;
  };
})();

(function addTopMenuButton() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(addTopMenuButton, 200);
    }
    const navigation = Ext.get('pimcore_menu_search');
    const tabs = Ext.getCmp('pimcore_panel_tabs');
    const user = pimcore.globalmanager.get('user');
    if (!navigation || !tabs || !user) return setTimeout(addTopMenuButton, 200);

    if (!user.admin && !(user.isAllowed && user.isAllowed('datahub_control'))) return;

    if (!Ext.get('pimcore_menu_datahubcontrol')) {
      const button = navigation.insertSibling(
        '<li id="pimcore_menu_datahubcontrol" data-menu-tooltip="Data Import" class="pimcore_menu_item pimcore_menu_needs_children">' +
          '<img src="/bundles/pimcoreadmin/img/flat-white-icons/import.svg" alt="Data Import">' +
        '</li>',
        'before'
      );
      button.on('mousedown', function () {
        const panel = Ext.getCmp('datahub-control-panel') || app.datahub.getPanel();
        tabs.add(panel);
        tabs.setActiveTab(panel);
      });
      pimcore.helpers.initMenuTooltips();
    }
  } catch (error) {
    console.error('[datahub-control] top menu add error', error);
    setTimeout(addTopMenuButton, 400);
  }
})();
