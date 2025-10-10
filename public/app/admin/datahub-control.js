console.log('[datahub-control] loaded');

pimcore.registerNS("app.datahub.ControlPanel");

app.datahub.ControlPanel = Class.create({
  initialize: function () {},
  getPanel: function () {
    if (!this.panel) {
      this.panel = new Ext.Panel({
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

(function addToolbarButtonWhenReady(){
  try {
    var toolbar = pimcore.globalmanager.get("layout_toolbar");
    var portal  = pimcore.globalmanager.get("layout_portal");
    if (!toolbar || !portal) {
      // Admin not fully initialized yet — retry shortly
      return setTimeout(addToolbarButtonWhenReady, 300);
    }

    // Avoid duplicates if script is reloaded
    if (!toolbar.findById('datahub-import-btn')) {
      toolbar.add({
        id: 'datahub-import-btn',
        text: t("Data Import"),
        iconCls: "pimcore_icon_import",
        handler: function () {
          var panel = new app.datahub.ControlPanel().getPanel();
          portal.add(panel);
          portal.setActiveTab(panel);
        }
      });
      toolbar.doLayout();
      console.log('[datahub-control] toolbar button added');
    }
  } catch(e) {
    console.error('[datahub-control] init error', e);
    setTimeout(addToolbarButtonWhenReady, 500);
  }
})();
