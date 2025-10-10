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

(function waitForAdmin(){
  try {
    // check for Ext + core layout components
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(waitForAdmin, 200);
    }

    var layout   = pimcore.globalmanager.get("layout");
    var portal   = pimcore.globalmanager.get("layout_portal");
    var toolbar  = pimcore.globalmanager.get("layout_toolbar") || (layout && layout.toolbar);

    // wait until both exist
    if (!portal || !toolbar) {
      return setTimeout(waitForAdmin, 200);
    }

    // avoid duplicates
    if (!toolbar.findById('datahub-import-btn')) {
      toolbar.add({
        id: 'datahub-import-btn',
        text: "Data Import",
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
  } catch (e) {
    console.error('[datahub-control] init error', e);
    setTimeout(waitForAdmin, 400);
  }
})();
