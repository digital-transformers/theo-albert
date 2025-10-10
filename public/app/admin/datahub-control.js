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

(function addToolbarButton() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.globalmanager) {
      return setTimeout(addToolbarButton, 200);
    }

    var layout  = pimcore.globalmanager.get("layout");
    var portal  = pimcore.globalmanager.get("pimcore_panel_tabs") || Ext.getCmp("pimcore_panel_tabs") || pimcore.globalmanager.get("layout_portal");
    var toolbar = pimcore.globalmanager.get("layout_toolbar") || (layout && layout.toolbar);

    // Log to help us see what's available in your build
    console.log('[datahub-control] toolbar?', !!toolbar, 'portal?', !!portal);

    if (!toolbar || !portal) {
      return setTimeout(addToolbarButton, 200);
    }

    if (!toolbar.findById('datahub-import-btn')) {
      toolbar.add({
        id: 'datahub-import-btn',
        text: "Data Import",
        iconCls: "pimcore_icon_import",
        handler: function () {
          var panel = Ext.getCmp('datahub-control-panel') || new app.datahub.ControlPanel().getPanel();
          portal.add(panel);
          portal.setActiveTab(panel);
        }
      });
      toolbar.doLayout();
      console.log('[datahub-control] toolbar button added');
    }
  } catch (e) {
    console.error('[datahub-control] toolbar add error', e);
    setTimeout(addToolbarButton, 400);
  }
})();

