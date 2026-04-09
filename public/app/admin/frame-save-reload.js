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

    document.addEventListener(pimcore.events.postSaveObject, function (event) {
      try {
        const objectEditor = event?.detail?.object;
        const task = event?.detail?.task;
        const data = objectEditor?.data?.general || {};
        const className = String(data.className || '').toLowerCase();

        if (reloadableClasses.indexOf(className) < 0) {
          return;
        }

        if (objectEditor?.willClose || task === 'autoSave' || task === 'session' || task === 'version') {
          return;
        }

        window.setTimeout(function () {
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
