console.log('[default-dashboard] loaded');

(function openDefaultDashboardOnLogin() {
  var defaultDashboard = 'Theo';
  var maxAttempts = 12;
  var attempts = 0;

  function scheduleOpen(delay) {
    window.setTimeout(openDashboard, delay || 1800);
  }

  function openDashboard() {
    if (attempts >= maxAttempts) {
      console.warn('[default-dashboard] gave up opening dashboard', defaultDashboard);
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
      console.warn('[default-dashboard] user is not allowed to open dashboards');
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
      console.log('[default-dashboard] opened dashboard', defaultDashboard);
      return;
    }

    window.setTimeout(openDashboard, 500);
  }

  if (window.pimcore && pimcore.events && pimcore.events.pimcoreReady) {
    document.addEventListener(pimcore.events.pimcoreReady, scheduleOpen, { once: true });
  }

  scheduleOpen(2500);
})();
