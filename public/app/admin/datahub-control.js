// public/app/admin/datahub-control.js
console.log('[datahub-control] loaded');

pimcore.registerNS("pimcore.plugin.datahubcontrol");

pimcore.plugin.datahubcontrol = Class.create({

  panelId: 'datahub-control-panel',

  initialize: function () {
    // register with plugin broker
    pimcore.plugin.broker.registerPlugin(this);
  },

  pimcoreReady: function () {
    try {
      const navEl = Ext.get('pimcore_menu_search');
      if (!navEl) return;

      const user = pimcore.globalmanager.get("user");
      // show only to admins or users with the custom permission
      if (!user || (!user.admin && !user.isAllowed?.('datahub_control'))) return;

      if (Ext.get('pimcore_menu_datahubcontrol')) return; // avoid duplicates

      const btn = navEl.insertSibling(
        '<li id="pimcore_menu_datahubcontrol" data-menu-tooltip="Data Import" class="pimcore_menu_item pimcore_menu_needs_children">' +
          '<img src="/bundles/pimcoreadmin/img/flat-white-icons/import.svg" alt="Data Import" />' +
        '</li>',
        'before'
      );
      btn.on("mousedown", this.addMainTab.bind(this));
      pimcore.helpers.initMenuTooltips();
      console.log('[datahub-control] menu button added');
    } catch (e) {
      console.error('[datahub-control] pimcoreReady error', e);
    }
  },

  addMainTab: function () {
    if (!Ext.getCmp(this.panelId)) {
      const tabPanel = Ext.getCmp("pimcore_panel_tabs");

      tabPanel.add({
        id: this.panelId,
        iconCls: "pimcore_icon_import",
        title: "DataHub Import Control",
        closable: true,
        layout: "fit",
        items: [{
          xtype: 'panel',
          border: false,
          layout: 'vbox',
          bodyStyle: 'padding:12px; gap:10px;',
          items: [
            {
              xtype: 'toolbar',
              width: '100%',
              items: [
                {
                  text: 'Start (Supervisor)',
                  iconCls: 'pimcore_icon_apply',
                  handler: function () {
                    Ext.Ajax.request({
                      url: '/admin/datahub-supervisor/start',
                      method: 'POST',
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
                '->',
                {
                  text: 'Refresh Status',
                  handler: function () {
                    Ext.Ajax.request({
                      url: '/admin/datahub-supervisor/status',
                      method: 'GET',
                      success: function (resp) {
                        const d = Ext.decode(resp.responseText);
                        Ext.getCmp('datahub-status-box').setValue(
                          (d.workers || []).map(w => `${w.name}: ${w.state}`).join('<br/>') || 'No data'
                        );
                      },
                      failure: function () {
                        pimcore.helpers.showNotification('Error', 'Status failed', 'error');
                      }
                    });
                  }
                }
              ]
            },
            {
              xtype: 'displayfield',
              id: 'datahub-status-box',
              width: '100%',
              fieldLabel: 'Workers',
              labelWidth: 70,
              value: '—',
              renderer: function (v) { return v; }
            },
            {
              xtype: 'textarea',
              id: 'datahub-log-box',
              width: '100%',
              height: 420,
              readOnly: true
            }
          ]
        }]
      });

      tabPanel.setActiveTab(this.panelId);

      // simple log tail poller
      const pollLog = () => {
        const cmp = Ext.getCmp('datahub-log-box');
        if (!cmp || !Ext.getCmp(this.panelId)) return; // stop when tab closed
        Ext.Ajax.request({
          url: '/admin/datahub-supervisor/log?lines=400',
          method: 'GET',
          success: function (resp) {
            try {
              const d = Ext.decode(resp.responseText);
              cmp.setValue(d.log || '');
              const el = cmp.inputEl && cmp.inputEl.dom;
              if (el) el.scrollTop = el.scrollHeight;
            } catch(e){}
          },
          callback: function(){ setTimeout(pollLog, 2000); }
        });
      };
      // if you have a /log endpoint; otherwise remove this block
      // setTimeout(pollLog, 1000);

    } else {
      Ext.getCmp(this.panelId).activate();
    }
  }
});

// Ensure broker exists before instantiating the plugin (prevents race conditions)
(function ensureBrokerThenInit() {
  if (!window.pimcore || !pimcore.plugin || !pimcore.plugin.broker) {
    return setTimeout(ensureBrokerThenInit, 200);
  }
  new pimcore.plugin.datahubcontrol();
})();
