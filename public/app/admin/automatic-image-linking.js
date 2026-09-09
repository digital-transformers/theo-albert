console.log('[automatic-image-linking] loaded');

(function init() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.events) {
      return setTimeout(init, 300);
    }

    if (window.pimcore?.settings?.csrfToken) {
      Ext.Ajax.defaultHeaders = Ext.Ajax.defaultHeaders || {};
      Ext.Ajax.defaultHeaders['X-pimcore-csrf-token'] = pimcore.settings.csrfToken;
    }

    if (window.__automaticImageLinkingRegistered) {
      return;
    }
    window.__automaticImageLinkingRegistered = true;

    const currentUserCanLinkImages = function () {
      const user = pimcore.globalmanager ? pimcore.globalmanager.get('user') : null;

      return !!(user && (user.admin || (user.isAllowed && user.isAllowed('automatic_image_linking'))));
    };

    const encode = function (value) {
      return Ext.util.Format.htmlEncode(String(value || ''));
    };

    const renderSummary = function (result) {
      const maxVisibleRows = 500;
      const linkedRows = result.linked || [];
      const orphanRows = result.orphan || [];
      const errorRows = result.errors || [];
      const linked = linkedRows.slice(0, maxVisibleRows).map(function (row) {
        return '<li>' + encode(row.path) + ' -> ' + encode(row.objectPath) + ' / ' + encode(row.field) + (row.changed ? '' : ' (already linked)') + '</li>';
      }).join('');

      const orphan = orphanRows.slice(0, maxVisibleRows).map(function (row) {
        return '<li>' + encode(row.path) + ' - ' + encode(row.reason) + '</li>';
      }).join('');

      const errors = errorRows.slice(0, maxVisibleRows).map(function (error) {
        return '<li>' + encode(error) + '</li>';
      }).join('');

      const omitted = function (rows) {
        return rows.length > maxVisibleRows
          ? '<li><em>' + encode(rows.length - maxVisibleRows) + ' more rows are available in the JSON report.</em></li>'
          : '';
      };

      return [
        '<p>' + encode(result.message || '') + '</p>',
        '<h3>Linked images (' + linkedRows.length + ')</h3><ul>' + (linked || '<li>None</li>') + omitted(linkedRows) + '</ul>',
        '<h3>Orphan images (' + orphanRows.length + ')</h3><ul>' + (orphan || '<li>None</li>') + omitted(orphanRows) + '</ul>',
        errors ? '<h3>Errors (' + errorRows.length + ')</h3><ul>' + errors + omitted(errorRows) + '</ul>' : ''
      ].join('');
    };

    document.addEventListener(pimcore.events.postOpenAsset, function (event) {
      try {
        const assetEditor = event?.detail?.asset || event?.detail?.object;
        const data = assetEditor?.data || {};
        if ((event?.detail?.type || data.type) !== 'folder') {
          return;
        }

        if (!currentUserCanLinkImages()) {
          return;
        }

        const toolbar = assetEditor.getLayoutToolbar ? assetEditor.getLayoutToolbar() : assetEditor.toolbar;
        if (!toolbar || Ext.getCmp('automatic-image-linking-' + data.id)) {
          return;
        }

        toolbar.add('-');
        toolbar.add({
          id: 'automatic-image-linking-' + data.id,
          text: 'Link Images',
          tooltip: 'Link images in this folder to family, model, or frame objects by filename',
          iconCls: 'pimcore_icon_apply',
          scale: 'medium',
          disabled: assetEditor.isAllowed && !assetEditor.isAllowed('view'),
          handler: function () {
            const button = Ext.getCmp('automatic-image-linking-' + data.id);
            const run = function () {
              if (button) {
                button.setDisabled(true);
              }
              if (assetEditor.tab) {
                assetEditor.tab.mask('Queuing image linking...');
              }

              const finish = function () {
                if (assetEditor.tab) {
                  assetEditor.tab.unmask();
                }
                if (button) {
                  button.setDisabled(false);
                }
              };

              const showFailure = function (message) {
                finish();
                pimcore.helpers.showNotification('Link Images', message || 'Image linking failed', 'error');
              };

              const showResult = function (result) {
                finish();
                if (!result) {
                  return showFailure('Image linking completed without a readable report.');
                }
                Ext.create('Ext.window.Window', {
                    title: 'Image Linking Summary',
                    width: 760,
                    height: 520,
                    modal: true,
                    layout: 'fit',
                    items: [{
                      xtype: 'panel',
                      bodyPadding: 16,
                      autoScroll: true,
                      html: renderSummary(result)
                    }],
                    buttons: [{
                      text: 'Close',
                      handler: function (btn) {
                        btn.up('window').close();
                      }
                    }]
                }).show();
                pimcore.helpers.showNotification('Link Images', result.message || 'Image linking completed', result.errors?.length ? 'warning' : 'success');
                pimcore.elementservice.refreshNodeAllTrees('asset', data.id);
              };

              const poll = function (statusUrl) {
                Ext.Ajax.request({
                  url: statusUrl,
                  method: 'GET',
                  timeout: 30000,
                  success: function (response) {
                    const status = Ext.decode(response.responseText);
                    if (status.status === 'completed') {
                      if (!status.report_url) {
                        return showFailure('Image linking completed without a report URL.');
                      }
                      return Ext.Ajax.request({
                        url: status.report_url,
                        method: 'GET',
                        timeout: 60000,
                        success: function (reportResponse) {
                          const report = Ext.decode(reportResponse.responseText);
                          showResult(report.result);
                        },
                        failure: function () {
                          showFailure('Image linking completed, but the report could not be loaded. You can reopen it from Image Linking Jobs.');
                        }
                      });
                    }
                    if (status.status === 'failed') {
                      return showFailure(status.error || 'Image linking failed.');
                    }
                    if (assetEditor.tab) {
                      assetEditor.tab.mask(status.status === 'running' ? 'Linking images in background...' : 'Waiting for image linking worker...');
                    }
                    setTimeout(function () { poll(statusUrl); }, 2000);
                  },
                  failure: function (response) {
                    let message = 'Unable to read image linking status.';
                    try {
                      const result = Ext.decode(response.responseText);
                      message = result.message || message;
                    } catch (e) {}
                    showFailure(message);
                  }
                });
              };

              Ext.Ajax.request({
                url: '/admin/automatic-image-linking/process-folder/' + data.id,
                method: 'POST',
                timeout: 30000,
                success: function (response) {
                  const job = Ext.decode(response.responseText);
                  if (!job.status_url) {
                    return showFailure('The server did not return an image linking job URL.');
                  }
                  pimcore.helpers.showNotification('Link Images', job.message || 'Image linking queued.', 'info');
                  poll(job.status_url);
                },
                failure: function (response) {
                  let message = 'Image linking failed';
                  try {
                    const result = Ext.decode(response.responseText);
                    message = result.message || message;
                  } catch (e) {}

                  showFailure(message);
                }
              });
            };

            Ext.Msg.confirm(
              'Link Images',
              'This will scan the folder and link matching images to family, model, or frame objects. Continue?',
              function (choice) {
                if (choice === 'yes') {
                  run();
                }
              }
            );
          }
        });

        toolbar.updateLayout();
      } catch (e) {
        console.warn('[automatic-image-linking] postOpenAsset error', e);
      }
    });
  } catch (e) {
    console.error('[automatic-image-linking] init error', e);
    setTimeout(init, 500);
  }
})();

(function addJobsHistory() {
  if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
    return setTimeout(addJobsHistory, 250);
  }

  pimcore.registerNS('app.imageLinkingJobs');
  app.imageLinkingJobs = app.imageLinkingJobs || {};
  let refreshTimer = null;

  const statusRenderer = function (value) {
    const colors = { queued: '#5f6368', running: '#1565c0', completed: '#2e7d32', failed: '#b3261e' };
    const status = String(value || 'unknown');
    return '<span style="font-weight:600;color:' + (colors[status] || '#5f6368') + ';">' +
      Ext.util.Format.htmlEncode(status) + '</span>';
  };

  const detailHtml = function (status) {
    const encode = Ext.util.Format.htmlEncode;
    const row = function (label, value) {
      return '<tr><th style="text-align:left;padding-right:16px;">' + encode(label) + '</th><td>' + encode(String(value ?? '-')) + '</td></tr>';
    };
    let html = '<table>';
    html += row('Job', status.job_id);
    html += row('Folder', status.folder_path);
    html += row('Status', status.status);
    html += row('Created', status.created_at);
    html += row('Started', status.started_at);
    html += row('Finished', status.finished_at);
    html += row('Processed', status.processed_count || 0);
    html += row('Linked', status.linked_count || 0);
    html += row('Orphan', status.orphan_count || 0);
    html += row('Errors', status.error_count || (status.error ? 1 : 0));
    if (status.error) html += row('Failure', status.error);
    return html + '</table>';
  };

  const reportHtml = function (result) {
    const encode = Ext.util.Format.htmlEncode;
    const maxVisibleRows = 500;
    const section = function (title, rows, renderRow) {
      rows = rows || [];
      let content = rows.slice(0, maxVisibleRows).map(renderRow).join('');
      if (!content) content = '<li>None</li>';
      if (rows.length > maxVisibleRows) {
        content += '<li><em>' + encode(rows.length - maxVisibleRows) + ' more rows are available in the JSON report.</em></li>';
      }
      return '<h3>' + encode(title) + ' (' + rows.length + ')</h3><ul>' + content + '</ul>';
    };

    return '<p>' + encode(result.message || '') + '</p>' +
      section('Linked images', result.linked, function (row) {
        return '<li>' + encode(row.path) + ' &rarr; ' + encode(row.objectPath) + ' / ' + encode(row.field) + (row.changed ? '' : ' (already linked)') + '</li>';
      }) +
      section('Orphan images', result.orphan, function (row) {
        return '<li>' + encode(row.path) + ' - ' + encode(row.reason) + '</li>';
      }) +
      section('Errors', result.errors, function (error) {
        return '<li>' + encode(error) + '</li>';
      });
  };

  app.imageLinkingJobs.refresh = function (callback) {
    Ext.Ajax.request({
      url: '/admin/automatic-image-linking/jobs?limit=100',
      method: 'GET',
      success: function (response) {
        const payload = Ext.decode(response.responseText);
        const store = Ext.data.StoreManager.lookup('image-linking-jobs-store');
        const grid = Ext.getCmp('image-linking-jobs-grid');
        const selectedId = grid?.getSelectionModel().getSelection()[0]?.get('job_id');
        if (store) store.loadData(payload.jobs || []);
        if (selectedId && grid) {
          const record = store.findRecord('job_id', selectedId, 0, false, true, true);
          if (record) grid.getSelectionModel().select(record);
        }
      },
      callback: callback
    });
  };

  app.imageLinkingJobs.load = function (jobId) {
    Ext.Ajax.request({
      url: '/admin/automatic-image-linking/status/' + encodeURIComponent(jobId),
      method: 'GET',
      success: function (response) {
        const status = Ext.decode(response.responseText);
        const detail = Ext.getCmp('image-linking-job-detail');
        if (detail) detail.update(detailHtml(status));
        const button = Ext.getCmp('image-linking-download-report');
        if (button) {
          button.setDisabled(status.status !== 'completed');
          button.jobId = status.status === 'completed' ? jobId : null;
        }
        const viewButton = Ext.getCmp('image-linking-view-report');
        if (viewButton) {
          viewButton.setDisabled(status.status !== 'completed');
          viewButton.jobId = status.status === 'completed' ? jobId : null;
        }
      }
    });
  };

  app.imageLinkingJobs.viewReport = function (jobId) {
    Ext.Ajax.request({
      url: '/admin/automatic-image-linking/report/' + encodeURIComponent(jobId),
      method: 'GET',
      success: function (response) {
        const payload = Ext.decode(response.responseText);
        Ext.create('Ext.window.Window', {
          title: 'Image Linking Report',
          width: 900,
          height: 650,
          modal: true,
          layout: 'fit',
          items: [{ xtype: 'panel', bodyPadding: 16, scrollable: true, html: reportHtml(payload.result || {}) }],
          buttons: [{ text: 'Close', handler: function (button) { button.up('window').close(); } }]
        }).show();
      },
      failure: function () {
        pimcore.helpers.showNotification('Image Linking Jobs', 'Unable to load the report.', 'error');
      }
    });
  };

  app.imageLinkingJobs.getPanel = function () {
    const existing = Ext.getCmp('image-linking-jobs-panel');
    if (existing) return existing;

    const store = Ext.create('Ext.data.Store', {
      storeId: 'image-linking-jobs-store',
      fields: ['job_id', 'folder_path', 'status', 'created_at', 'started_at', 'finished_at', 'processed_count', 'linked_count', 'orphan_count', 'error_count', 'error'],
      data: []
    });
    const panel = Ext.create('Ext.panel.Panel', {
      id: 'image-linking-jobs-panel',
      title: 'Image Linking Jobs',
      iconCls: 'pimcore_icon_image',
      closable: true,
      layout: 'border',
      tbar: [
        { text: 'Refresh', iconCls: 'pimcore_icon_reload', handler: app.imageLinkingJobs.refresh },
        { xtype: 'checkbox', id: 'image-linking-jobs-autorefresh', boxLabel: 'Auto-refresh', checked: true },
        '->',
        {
          id: 'image-linking-view-report',
          text: 'View report',
          iconCls: 'pimcore_icon_search',
          disabled: true,
          handler: function (button) {
            if (button.jobId) app.imageLinkingJobs.viewReport(button.jobId);
          }
        },
        {
          id: 'image-linking-download-report',
          text: 'Download JSON report',
          iconCls: 'pimcore_icon_download',
          disabled: true,
          handler: function (button) {
            if (button.jobId) {
              window.open('/admin/automatic-image-linking/report/' + encodeURIComponent(button.jobId), '_blank');
            }
          }
        }
      ],
      items: [
        {
          xtype: 'grid',
          id: 'image-linking-jobs-grid',
          region: 'center',
          store: store,
          border: false,
          columnLines: true,
          viewConfig: { emptyText: 'No image linking jobs yet.', deferEmptyText: false },
          columns: [
            { text: 'Created', dataIndex: 'created_at', width: 190 },
            { text: 'Folder', dataIndex: 'folder_path', minWidth: 260, flex: 1 },
            { text: 'Status', dataIndex: 'status', width: 110, renderer: statusRenderer },
            { text: 'Processed', dataIndex: 'processed_count', width: 90, align: 'right' },
            { text: 'Linked', dataIndex: 'linked_count', width: 75, align: 'right' },
            { text: 'Orphan', dataIndex: 'orphan_count', width: 75, align: 'right' },
            { text: 'Errors', dataIndex: 'error_count', width: 70, align: 'right' }
          ],
          listeners: {
            selectionchange: function (_selection, records) {
              if (records.length) app.imageLinkingJobs.load(records[0].get('job_id'));
            }
          }
        },
        {
          xtype: 'panel',
          id: 'image-linking-job-detail',
          title: 'Job Summary',
          region: 'south',
          split: true,
          height: 250,
          scrollable: true,
          bodyPadding: 12,
          html: 'Select a job to see its summary.'
        }
      ],
      listeners: {
        afterrender: function () {
          const poll = function () {
            const current = Ext.getCmp('image-linking-jobs-panel');
            if (!current) return;
            const auto = Ext.getCmp('image-linking-jobs-autorefresh');
            if (auto?.getValue()) app.imageLinkingJobs.refresh();
            refreshTimer = setTimeout(poll, 3000);
          };
          app.imageLinkingJobs.refresh();
          refreshTimer = setTimeout(poll, 3000);
        },
        destroy: function () {
          if (refreshTimer) clearTimeout(refreshTimer);
          refreshTimer = null;
        }
      }
    });

    return panel;
  };

  const navigation = Ext.get('pimcore_menu_search');
  const tabs = Ext.getCmp('pimcore_panel_tabs');
  const user = pimcore.globalmanager.get('user');
  if (!navigation || !tabs || !user) return setTimeout(addJobsHistory, 250);
  if (!user.admin && !(user.isAllowed && user.isAllowed('automatic_image_linking'))) return;

  if (!Ext.get('pimcore_menu_image_linking_jobs')) {
    const button = navigation.insertSibling(
      '<li id="pimcore_menu_image_linking_jobs" data-menu-tooltip="Image Linking Jobs" class="pimcore_menu_item pimcore_menu_needs_children">' +
        '<img src="/bundles/pimcoreadmin/img/flat-white-icons/image.svg" alt="Image Linking Jobs">' +
      '</li>',
      'before'
    );
    button.on('mousedown', function () {
      const panel = app.imageLinkingJobs.getPanel();
      tabs.add(panel);
      tabs.setActiveTab(panel);
    });
    pimcore.helpers.initMenuTooltips();
  }
})();
