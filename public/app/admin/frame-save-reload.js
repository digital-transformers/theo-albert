console.log('[frame-save-reload] loaded');

(function init() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.events) {
      return setTimeout(init, 300);
    }

    if (window.__frameSaveReloadRegistered) {
      return;
    }
    window.__frameSaveReloadRegistered = true;

    const reloadableClasses = ['frame', 'model'];
    const pendingModelFinalProductsReloads = {};
    const normalizeTitle = function (value) {
      return String(value || '').trim().toLowerCase();
    };

    const walkComponents = function (component, visitor) {
      if (!component) {
        return false;
      }

      if (visitor(component) === true) {
        return true;
      }

      const items = Array.isArray(component?.items?.items) ? component.items.items : [];
      for (let index = 0; index < items.length; index += 1) {
        if (walkComponents(items[index], visitor)) {
          return true;
        }
      }

      return false;
    };

    const getParentTabPanel = function (component) {
      let current = component;

      while (current) {
        const parent = typeof current.up === 'function' ? current.up() : current.ownerCt;
        if (!parent) {
          return null;
        }

        if (typeof parent.isXType === 'function' && parent.isXType('tabpanel')) {
          return parent;
        }

        current = parent;
      }

      return null;
    };

    const findFinalProductsTab = function (objectEditor) {
      let found = null;

      walkComponents(objectEditor?.edit?.layout, function (component) {
        if (normalizeTitle(component?.title) === 'final products') {
          found = component;
          return true;
        }

        return false;
      });

      return found;
    };

    const isFinalProductsTabActive = function (objectEditor) {
      const finalProductsTab = findFinalProductsTab(objectEditor);
      const tabPanel = getParentTabPanel(finalProductsTab);
      if (!finalProductsTab || !tabPanel || typeof tabPanel.getActiveTab !== 'function') {
        return false;
      }

      return tabPanel.getActiveTab() === finalProductsTab;
    };

    const activateFinalProductsTab = function (objectEditor) {
      const finalProductsTab = findFinalProductsTab(objectEditor);
      const tabPanel = getParentTabPanel(finalProductsTab);
      if (!finalProductsTab || !tabPanel || typeof tabPanel.setActiveTab !== 'function') {
        return false;
      }

      tabPanel.setActiveTab(finalProductsTab);
      return true;
    };

    const activateFinalProductsTabWithRetry = function (objectEditor, attempt) {
      if (activateFinalProductsTab(objectEditor)) {
        return;
      }

      if ((attempt || 0) >= 10) {
        return;
      }

      window.setTimeout(function () {
        activateFinalProductsTabWithRetry(objectEditor, (attempt || 0) + 1);
      }, 150);
    };

    document.addEventListener(pimcore.events.postOpenObject, function (event) {
      try {
        const objectEditor = event?.detail?.object;
        const data = objectEditor?.data?.general || {};
        const className = String(data.className || '').toLowerCase();
        const objectId = String(data.id || '');

        if (className !== 'model' || !pendingModelFinalProductsReloads[objectId]) {
          return;
        }

        delete pendingModelFinalProductsReloads[objectId];
        activateFinalProductsTabWithRetry(objectEditor, 0);
      } catch (e) {
        console.warn('[frame-save-reload] postOpenObject error', e);
      }
    });

    document.addEventListener(pimcore.events.postSaveObject, function (event) {
      try {
        const objectEditor = event?.detail?.object;
        const task = event?.detail?.task;
        const data = objectEditor?.data?.general || {};
        const className = String(data.className || '').toLowerCase();
        const objectId = String(data.id || '');

        if (reloadableClasses.indexOf(className) < 0) {
          return;
        }

        if (objectEditor?.willClose || task === 'autoSave' || task === 'session' || task === 'version') {
          return;
        }

        if (className === 'model' && !isFinalProductsTabActive(objectEditor)) {
          return;
        }

        window.setTimeout(function () {
          objectEditor.dirty = false;
          objectEditor._dirtyCloseConfirmed = true;

          if (className === 'model') {
            pendingModelFinalProductsReloads[objectId] = true;
            objectEditor.reload({ignoreUiState: true});
            return;
          }

          objectEditor.reload({ignoreUiState: true});
        }, 250);
      } catch (e) {
        console.warn('[frame-save-reload] postSaveObject error', e);
      }
    });

    console.log('[frame-save-reload] plugin registered');
  } catch (e) {
    console.error('[frame-save-reload] init error', e);
    setTimeout(init, 500);
  }
})();
