console.log('[datahub-control] loaded');

pimcore.registerNS("app.datahub.ControlPanel");

app.datahub.ControlPanel = Class.create({
  initialize: function () {},
  getPanel: function () {
    if (!this.panel) {
      this.panel = new Ext.Panel({
        id: 'datahub-control-panel',
        title: "DataHub Import Control",
        iconCls: "pimcore_icon_import",
        closable: true,
        layout: "fit",
        items: [{
          xtype: "panel",
          bodyStyle: "padding:12px",
          html: "<b>It works!</b> Hook Start/Stop/Status here."
        }]
      });
    }
    return this.panel;
  }
});

(function waitThenOpenTab() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(waitThenOpenTab, 200);
    }

    // Find the main tab panel (this ID exists across Pimcore 10/11)
    var tabs = Ext.getCmp("pimcore_panel_tabs");
    if (!tabs) {
      return setTimeout(waitThenOpenTab, 200);
    }

    // Avoid duplicates
    if (!Ext.getCmp('datahub-control-panel')) {
      var panel = new app.datahub.ControlPanel().getPanel();
      tabs.add(panel);
      tabs.setActiveTab(panel);
      console.log('[datahub-control] panel opened');
    }
  } catch (e) {
    console.error('[datahub-control] open error', e);
    setTimeout(waitThenOpenTab, 400);
  }
})();
