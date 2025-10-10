// public/app/admin/color-autoname.js
console.log('[color-autoname] loaded');

(function init() {
  try {
    // wait until Pimcore Admin is fully booted
    var toolbar = pimcore?.globalmanager?.get?.("layout_toolbar");
    if (!toolbar || typeof Ext === 'undefined' || !pimcore?.plugin?.broker) {
      return setTimeout(init, 300);
    }

    // register a lightweight admin plugin (no extends)
    if (!window.__colorAutonameRegistered) {
      window.__colorAutonameRegistered = true;

      pimcore.plugin.broker.registerPlugin({
        getClassName: function () { return "app.ColorAutoNamePlugin"; },
        initialize: function () {
          // run when an object editor opens
          pimcore.events.on("postOpenObject", function (objectEditor /* pimcore.object.edit */, type) {
            try {
              const data = objectEditor?.data?.general || {};
              if ((data.className || '').toLowerCase() !== 'color') return;

              const fields = objectEditor?.dataFields || {};
              const multi = fields['multiColor'];
              const nameField = fields['name'];

              if (!multi || !nameField || !multi.grid || !multi.grid.getStore) return;

              const store = multi.grid.getStore();

              const recompute = () => {
                try {
                  const names = [];
                  store.each(rec => {
                    const n = (rec.get('name') || rec.get('code') || rec.get('path') || '').toString().trim();
                    if (n) names.push(n);
                  });
                  if (names.length > 1) {
                    nameField.setValue(names.join(' + '));
                  }
                } catch (e) {
                  console.warn('[color-autoname] recompute error', e);
                }
              };

              store.on('datachanged', recompute);
              store.on('update', recompute);
              Ext.defer(recompute, 100);
            } catch (e) {
              console.warn('[color-autoname] init error', e);
            }
          });
        }
      });

      console.log('[color-autoname] plugin registered');
    }
  } catch (e) {
    console.error('[color-autoname] init error', e);
    setTimeout(init, 500);
  }
})();
