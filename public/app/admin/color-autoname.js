(function () {
  class ColorAutoNamePlugin extends pimcore.plugin.admin {
    getClassName() { return "ColorAutoNamePlugin"; }

    initialize() {
      // Run when an object editor opens
      pimcore.events.on("postOpenObject", this.onPostOpenObject.bind(this));
    }

    onPostOpenObject(objectEditor /* pimcore.object.edit */, type) {
      try {
        const data = objectEditor?.data?.general || {};
        if (data.className !== 'color') {
          return; // only for Color class
        }

        // Access fields by name from the editor
        const fields = objectEditor?.dataFields || {};
        const multi = fields['multiColor'];
        const nameField = fields['name'];

        if (!multi || !nameField || !multi.grid || !multi.grid.getStore) {
          return;
        }

        const store = multi.grid.getStore();

        const recompute = () => {
          try {
            const names = [];
            store.each(rec => {
              // visibleFields: 'code,name' -> both exist in the store if configured
              const n = (rec.get('name') || rec.get('code') || rec.get('path') || '').toString().trim();
              if (n) names.push(n);
            });

            if (names.length > 1) {
              nameField.setValue(names.join(' + '));
            }
            // If 0/1 item, do nothing (keeps manual control)
          } catch (e) {
            // be silent in UI, but log in console for debugging
            console.warn('ColorAutoNamePlugin recompute error', e);
          }
        };

        // Recompute when rows are added/removed/edited
        store.on('datachanged', recompute);
        store.on('update', recompute);

        // Also recompute right after opening, in case there’s existing data
        Ext.defer(recompute, 100);
      } catch (e) {
        console.warn('ColorAutoNamePlugin init error', e);
      }
    }
  }

  new ColorAutoNamePlugin();
})();
s