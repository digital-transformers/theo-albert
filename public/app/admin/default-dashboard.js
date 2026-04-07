console.log('[default-dashboard] loaded');

(function openDefaultDashboardOnLogin() {
  var defaultDashboard = 'Theo';
  var maxAttempts = 12;
  var attempts = 0;

  function scheduleOpen() {
    window.setTimeout(openDashboard, 1800);
  }

  function openDashboard() {
    if (attempts >= maxAttempts) {
      return;
    }

    if (
      typeof Ext === 'undefined'
      || !window.pimcore
      || !pimcore.globalmanager
      || !pimcore.layout
      || !pimcore.layout.portal
    ) {
      window.setTimeout(openDashboard, 250);
      return;
    }

    var user = pimcore.globalmanager.get('user');
    var tabs = Ext.getCmp('pimcore_panel_tabs');

    if (!user || !tabs) {
      window.setTimeout(openDashboard, 250);
      return;
    }

    if (!(user.isAllowed && user.isAllowed('dashboards'))) {
      return;
    }

    if (!user.welcomescreen) {
      return;
    }

    attempts++;

    var portalKey = 'layout_portal_' + defaultDashboard;
    var panelId = 'pimcore_portal_' + defaultDashboard;
    if (pimcore.globalmanager.exists(portalKey)) {
      var portal = pimcore.globalmanager.get(portalKey);
      if (portal.panel) {
        portal.activate();
      }
    } else {
      pimcore.globalmanager.add(portalKey, new pimcore.layout.portal(defaultDashboard));
    }

    if (tabs.getActiveTab && tabs.getActiveTab() && tabs.getActiveTab().getId() === panelId) {
      return;
    }

    window.setTimeout(openDashboard, 500);
  }

  if (window.pimcore && pimcore.events && pimcore.events.pimcoreReady) {
    document.addEventListener(pimcore.events.pimcoreReady, scheduleOpen, { once: true });
  } else {
    scheduleOpen();
  }
})();
