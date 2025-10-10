// public/app/admin/datahub-control.js  (or /bundles/app/js/... if that's your path)

console.log('[datahub-control] loaded');

pimcore.registerNS("app.datahub.ControlPanel");

app.datahub.ControlPanel = Class.create({
  getClassName: function () { return "app.datahub.ControlPanel"; },

  initialize: function () {},

  getPanel: function () {
    if (!this.panel) {
      this.panel = new Ext.Panel({
        title: "DataHub Import Control",
        iconCls: "pimcore_icon_import",
        layout: "fit",
        items: [{
          xtype: "panel",
          bodyStyle: "padding:12px",
          html: "<b>It works!</b> Hook your Start/Stop/Status here."
        }]
      });
    }
    return this.panel;
  }
});

// Add an entry in the top toolbar that opens the panel
pimcore.plugin.broker.registerPlugin({
  initialize: function () {
    var toolbar = pimcore.globalmanager.get("layout_toolbar");
    if (!toolbar) return;

    toolbar.add({
      text: t("Data Import"),
      iconCls: "pimcore_icon_import",
      handler: function () {
        var panel = new app.datahub.ControlPanel().getPanel();
        var portal = pimcore.globalmanager.get("layout_portal");
        portal.add(panel);
        portal.setActiveTab(panel);
      }
    });
  }
});
