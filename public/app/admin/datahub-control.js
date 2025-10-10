console.log('[datahub-control] loaded');

// Namespace
pimcore.registerNS("app.datahub");
app.datahub = app.datahub || {};

// Panel factory (no Class.create)
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
              text: 'Start',
              iconCls: 'pimcore_icon_apply',
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
              text: 'Stop',
              iconCls: 'pimcore_icon_stop',
              handler: function () {
                Ext.Ajax.request({
                  url: '/admin/datahub-supervisor/stop',
                  method: 'POST',
                  success: function(){ pimcore.helpers.showNotification('OK','Stopped','success'); },
                  failure: function(){ pimcore.helpers.showNotification('Error','Stop failed','error'); }
                });
              }
            },
            '-',
            {
              text: 'Refresh Status',
              handler: function(){ app.datahub.refreshStatusOnce(); }
            },
            { xtype: 'checkbox', id: 'dhc-autorefresh', boxLabel: 'Auto-refresh', checked: true },
            '->',
            { text: 'Clear Log', handler: function(){ const b=Ext.getCmp('dhc-log'); if(b) b.setValue(''); } }
          ]
        },
        {
          xtype: 'displayfield',
          id: 'dhc-status',
          width: '100%',
          fieldLabel: 'Workers',
          labelWidth: 70,
          value: '—',
          renderer: function(v){ return v; }
        },
        { xtype: 'textarea', id: 'dhc-log', width: '100%', height: 420, readOnly: true }
      ]
    }]
  });

  // start polling when first created
  app.datahub.startPolling();
  return panel;
};

// Polling helpers
(function(){
  let timer = null;

  function poll(){
    try{
      const auto = Ext.getCmp('dhc-autorefresh');
      if (!auto || !auto.getValue()) {
        timer = setTimeout(poll, 2000);
        return;
      }

      Ext.Ajax.request({
        url: '/admin/datahub-supervisor/status',
        method: 'GET',
        success: function (resp) {
          try {
            const d = Ext.decode(resp.responseText);
            const html = (d.workers || []).map(w =>
              `${Ext.util.Format.htmlEncode(w.name)}: ${Ext.util.Format.htmlEncode(w.state)}`
            ).join('<br/>') || 'No data';
            const st = Ext.getCmp('dhc-status');
            if (st) st.setValue(html);
          } catch(e){}
        }
      });

      Ext.Ajax.request({
        url: '/admin/datahub-supervisor/log?lines=400',
        method: 'GET',
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
    } catch(e){
      timer = setTimeout(poll, 2500);
    }
  }

  app.datahub.startPolling = function(){
    if (timer) clearTimeout(timer);
    timer = setTimeout(poll, 800);
  };

  app.datahub.refreshStatusOnce = function(){
    Ext.Ajax.request({
      url: '/admin/datahub-supervisor/status',
      method: 'GET',
      success: function (resp) {
        try {
          const d = Ext.decode(resp.responseText);
          const html = (d.workers || []).map(w =>
            `${Ext.util.Format.htmlEncode(w.name)}: ${Ext.util.Format.htmlEncode(w.state)}`
          ).join('<br/>') || 'No data';
          const st = Ext.getCmp('dhc-status');
          if (st) st.setValue(html);
        } catch(e){}
      }
    });
  };
})();

// Auto-open tab
(function waitThenOpenTab() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(waitThenOpenTab, 200);
    }
    var tabs = Ext.getCmp("pimcore_panel_tabs");
    if (!tabs) {
      return setTimeout(waitThenOpenTab, 200);
    }
    if (!Ext.getCmp('datahub-control-panel')) {
      var panel = app.datahub.getPanel();   // <— no constructor; factory function
      tabs.add(panel);
      tabs.setActiveTab(panel);
      console.log('[datahub-control] panel opened');
    }
  } catch (e) {
    console.error('[datahub-control] open error', e);
    setTimeout(waitThenOpenTab, 400);
  }
})();
