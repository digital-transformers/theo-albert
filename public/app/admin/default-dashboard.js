console.log('[default-dashboard] loaded');

(function openDefaultDashboardOnLogin() {
  var defaultDashboard = 'Theo';
  var opened = false;

  function scheduleOpen() {
    window.setTimeout(openDashboard, 1300);
  }

  function openDashboard() {
    if (opened) {
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

    opened = true;

    var portalKey = 'layout_portal_' + defaultDashboard;
    if (pimcore.globalmanager.exists(portalKey)) {
      pimcore.globalmanager.get(portalKey).activate();
      return;
    }

    pimcore.globalmanager.add(portalKey, new pimcore.layout.portal(defaultDashboard));
  }

  if (window.pimcore && pimcore.events && pimcore.events.pimcoreReady) {
    document.addEventListener(pimcore.events.pimcoreReady, scheduleOpen, { once: true });
  }

  scheduleOpen();
})();
