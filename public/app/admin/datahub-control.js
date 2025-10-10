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

// Register a plugin that runs when Pimcore Admin is ready
pimcore.plugin.broker.registerPlugin({
  getClassName: function () { return "app.DataHubControlPlugin"; },

  pimcoreReady: function () {
    // Try both ways to get the toolbar, depending on Pimcore version
    var toolbar =
      pimcore.globalmanager.get("layout_toolbar") ||
      (pimcore.globalmanager.get("layout") && pimcore.globalmanager.get("layout").toolbar);

    var portal = pimcore.globalmanager.get("layout_portal");

    console.log('[datahub-control] pimcoreReady, toolbar:', !!toolbar, 'portal:', !!portal);

    if (!toolbar || !portal) {
      // As a fallback, open the panel automatically so you at least see it
      try {
        var p = new app.datahub.ControlPanel().getPanel();
        if (portal) {
          portal.add(p);
          portal.setActiveTab(p);
        } else {
          // very old/edge: use global tab panel
          pimcore.globalmanager.get("layout_portal").add(p);
        }
      } catch (e) {
        console.warn('[datahub-control] fallback open failed', e);
      }
      return;
    }

    // Avoid duplicates
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
  }
});
