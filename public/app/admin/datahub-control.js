// public/bundles/app/js/datahub-control.js
pimcore.registerNS("app.datahub.ControlPanel");
app.datahub.ControlPanel = Class.create(pimcore.element.abstractElement, {
  initialize: function() {
    this.getTabPanel();
  },
  getTabPanel: function() {
    if (!this.panel) {
      this.panel = new Ext.Panel({
        title: t("Data Import Queue Control"),
        iconCls: "pimcore_icon_importer",
        layout: "fit",
        items: [this.buildInner()]
      });
      var tab = pimcore.globalmanager.get("layout_portal");
      pimcore.layout.refresh();
    }
    return this.panel;
  },
  buildInner: function () {
    const logBox = new Ext.form.TextArea({ readOnly:true, height:350 });
    const status = new Ext.form.DisplayField({ value: "—" });

    const call = (url, params, cb) => Ext.Ajax.request({
      url, method: 'POST', params, success: resp => cb(Ext.decode(resp.responseText))
    });

    const poll = () => {
      Ext.Ajax.request({
        url: '/admin/datahub-control/status',
        method: 'GET',
        success: resp => {
          const s = Ext.decode(resp.responseText);
          status.setValue(
            (s.running ? "🟢 RUNNING" : "⚪️ IDLE") +
            (s.progress?.processed !== undefined ? ` — ${s.progress.processed}/${s.progress.total} (failed ${s.progress.failed})` : '')
          );
        }
      });
      Ext.Ajax.request({
        url: '/admin/datahub-control/log?lines=400',
        method: 'GET',
        success: resp => {
          const l = Ext.decode(resp.responseText);
          logBox.setValue(l.log);
          logBox.getEl().dom.scrollTop = logBox.getEl().dom.scrollHeight;
        }
      });
    };

    setInterval(poll, 2000); // live updates

    const tb = new Ext.Toolbar({
      items: [
        { text: "Start", iconCls:"pimcore_icon_apply", handler: () => {
          Ext.Msg.prompt("Start Import", "Profile name", (btn, text)=>{
            if(btn==='ok') call('/admin/datahub-control/start', {profile:text}, poll);
          }, this, false, "default");
        }},
        { text: "Dry Run", handler: ()=> {
          Ext.Msg.prompt("Dry Run", "Profile name", (btn, text)=>{
            if(btn==='ok') call('/admin/datahub-control/start', {profile:text, dryRun:1}, poll);
          });
        }},
        { text: "Stop", iconCls:"pimcore_icon_stop", handler: ()=> call('/admin/datahub-control/stop', {}, poll) },
        '-',
        status
      ]
    });

    return new Ext.Panel({
      layout: 'border',
      tbar: tb,
      items: [
        { region:'center', xtype:'panel', layout:'fit', items:[logBox], title:'Live Log' }
      ]
    });
  }
});

pimcore.plugin.broker.registerPlugin({
  initialize: function() {
    pimcore.globalmanager.get("layout_toolbar").add({
      text: t("Data Import"),
      iconCls: "pimcore_icon_import",
      handler: function(){
        const p = new app.datahub.ControlPanel();
        pimcore.globalmanager.get("layout_portal").add(p.getTabPanel());
      }
    });
  }
});
