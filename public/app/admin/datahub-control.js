console.log('[datahub-control] loaded');

if (window.pimcore?.settings?.csrfToken) {
  Ext.Ajax.defaultHeaders = Ext.Ajax.defaultHeaders || {};
  Ext.Ajax.defaultHeaders['X-pimcore-csrf-token'] = pimcore.settings.csrfToken;
}

pimcore.registerNS("app.datahub");
app.datahub = app.datahub || {};

// Panel factory
app.datahub.getPanel = function () {
  var existing = Ext.getCmp('datahub-control-panel');
  if (existing) return existing;

  var panel = Ext.create('Ext.panel.Panel', {
    id: 'datahub-control-panel',
    title: 'DataHub Import Control',
    iconCls: 'pimcore_icon_import',
    closable: true,
    layout: 'fit',
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
            { xtype: 'textfield', id: 'dhc-profile', width: 220, emptyText: 'profile (optional)' },
            { xtype: 'textfield', id: 'dhc-extra',   width: 280, emptyText: 'extra args (e.g. --limit=500)' },
            {
              text: 'Start', iconCls: 'pimcore_icon_apply',
              handler: function () {
                const profile = Ext.getCmp('dhc-profile').getValue() || 'default';
                const extra   = Ext.getCmp('dhc-extra').getValue() || '';
                Ext.Ajax.request({
                  url: '/admin/datahub-supervisor/start',
                  method: 'POST',
                  params: { profile, extra },
                  success: function(){ pimcore.helpers.showNotification('OK','Started','success'); },
                  failure: function(){ pimcore.helpers.showNotification('Error','Start failed','error'); }
                });
              }
            },
            {
              text: 'Stop', iconCls: 'pimcore_icon_stop',
              handler: function () {
                Ext.Ajax.request({
                  url: '/admin/datahub-supervisor/stop',
                  method: 'POST',
                  success: function(){ pimcore.helpers.showNotification('OK','Stopped','success'); },
                  failure: function(){ pimcore.helpers.showNotification('Error','Stop failed','error'); }
                });
              }
            },
            '-', { text: 'Refresh Status', handler: function(){ app.datahub.refreshStatusOnce(); } },
            { xtype: 'checkbox', id: 'dhc-autorefresh', boxLabel: 'Auto-refresh', checked: true },
            '->',
            {
              text: 'Clear Log',
              handler: function () {
                const box = Ext.getCmp('dhc-log'); if (box) box.setValue('');
                const auto = Ext.getCmp('dhc-autorefresh'); const prev = auto && auto.getValue(); if (auto) auto.setValue(false);
                Ext.Ajax.request({
                  url: '/admin/datahub-supervisor/log/clear', method: 'POST',
                  success: function () { setTimeout(function(){ if (auto) auto.setValue(prev); }, 800); pimcore.helpers.showNotification('OK','Log cleared','success'); },
                  failure: function () { pimcore.helpers.showNotification('Error','Failed to clear log','error'); if (auto) auto.setValue(prev); }
                });
              }
            }
          ]
        },
        {
          xtype: 'displayfield', id: 'dhc-status', width: '100%',
          fieldLabel: 'Workers', labelWidth: 70, value: '—',
          renderer: function(v){ return v; }
        },
        { xtype: 'textarea', id: 'dhc-log', width: '100%', height: 420, readOnly: true }
      ]
    }]
  });

  app.datahub.startPolling(); // starts only after user opens the panel
  return panel;
};

// Polling helpers
(function(){
  let timer = null;

  function poll(){
    try{
      const auto = Ext.getCmp('dhc-autorefresh');
      if (!auto || !auto.getValue()) { timer = setTimeout(poll, 2000); return; }

      Ext.Ajax.request({
        url: '/admin/datahub-supervisor/status', method: 'GET',
        success: function (resp) {
          try {
            const d = Ext.decode(resp.responseText);
            const html = (d.workers || []).map(w =>
              `${Ext.util.Format.htmlEncode(w.name)}: ${Ext.util.Format.htmlEncode(w.state)}`
            ).join('<br/>') || 'No data';
            const st = Ext.getCmp('dhc-status'); if (st) st.setValue(html);
          } catch(e){}
        }
      });

      Ext.Ajax.request({
        url: '/admin/datahub-supervisor/log?lines=400', method: 'GET',
        success: function (resp) {
          try {
            const d = Ext.decode(resp.responseText);
            const box = Ext.getCmp('dhc-log');
            if (box) {
              box.setValue(d.log || '');
              const el = box.inputEl && box.inputEl.dom;
              if (el) el.scrollTop = el.scrollHeight;
            }
          } catch(e){}
        },
        callback: function(){ timer = setTimeout(poll, 2000); }
      });
    } catch(e){ timer = setTimeout(poll, 2500); }
  }

  app.datahub.startPolling = function(){
    if (timer) clearTimeout(timer);
    timer = setTimeout(poll, 800);
  };

  app.datahub.refreshStatusOnce = function(){
    Ext.Ajax.request({
      url: '/admin/datahub-supervisor/status', method: 'GET',
      success: function (resp) {
        try {
          const d = Ext.decode(resp.responseText);
          const html = (d.workers || []).map(w =>
            `${Ext.util.Format.htmlEncode(w.name)}: ${Ext.util.Format.htmlEncode(w.state)}`
          ).join('<br/>') || 'No data';
          const st = Ext.getCmp('dhc-status'); if (st) st.setValue(html);
        } catch(e){}
      }
    });
  };
})();

// Button-only: add icon next to search; open panel on click
(function addTopMenuButton() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(addTopMenuButton, 200);
    }
    const navEl = Ext.get('pimcore_menu_search');
    const tabs  = Ext.getCmp('pimcore_panel_tabs');
    const user  = pimcore.globalmanager.get("user");
    if (!navEl || !tabs || !user) return setTimeout(addTopMenuButton, 200);

    if (!user.admin && !(user.isAllowed && user.isAllowed('datahub_control'))) return;

    if (!Ext.get('pimcore_menu_datahubcontrol')) {
      const btn = navEl.insertSibling(
        '<li id="pimcore_menu_datahubcontrol" data-menu-tooltip="Data Import" class="pimcore_menu_item pimcore_menu_needs_children">' +
          '<img src="/bundles/pimcoreadmin/img/flat-white-icons/import.svg" alt="Data Import" />' +
        '</li>',
        'before'
      );
      btn.on("mousedown", function () {
        const panel = Ext.getCmp('datahub-control-panel') || app.datahub.getPanel();
        tabs.add(panel); tabs.setActiveTab(panel);
      });
      pimcore.helpers.initMenuTooltips();
      console.log('[datahub-control] top menu button added');
    }
  } catch (e) {
    console.error('[datahub-control] top menu add error', e);
    setTimeout(addTopMenuButton, 400);
  }
})();
