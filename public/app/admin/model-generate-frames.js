console.log('[model-generate-frames] loaded');

(function init() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.events) {
      return setTimeout(init, 300);
    }

    if (window.pimcore?.settings?.csrfToken) {
      Ext.Ajax.defaultHeaders = Ext.Ajax.defaultHeaders || {};
      Ext.Ajax.defaultHeaders['X-pimcore-csrf-token'] = pimcore.settings.csrfToken;
    }

    if (window.__modelGenerateFramesRegistered) {
      return;
    }
    window.__modelGenerateFramesRegistered = true;

    const mergeCreatedFramesIntoFinalProducts = function (objectEditor, createdFrames) {
      if (!createdFrames || !createdFrames.length) {
        return;
      }

      const finalProducts = objectEditor?.edit?.dataFields?.finalProducts;
      const store = finalProducts?.store;
      if (!finalProducts || !store) {
        return;
      }

      const wasDirty = finalProducts.dataChanged === true;
      let added = false;

      createdFrames.forEach(function (frame) {
        if (!frame?.id) {
          return;
        }

        const exists = store.queryBy(function (record) {
          return String(record.get('id')) === String(frame.id);
        }).getCount() > 0;

        if (exists) {
          return;
        }

        store.add({
          id: frame.id,
          path: frame.path,
          fullpath: frame.path,
          type: 'object',
          subtype: 'object',
          classname: 'frame',
          published: false,
          code: frame.code || '',
          name: frame.name || ''
        });
        added = true;
      });

      if (added && !wasDirty) {
        finalProducts.dataChanged = false;
      }

      if (added && finalProducts.component?.getView) {
        finalProducts.component.getView().refresh();
      }
    };

    document.addEventListener(pimcore.events.postOpenObject, function (event) {
      try {
        const objectEditor = event?.detail?.object;
        const data = objectEditor?.data?.general || {};
        if ((data.className || '').toLowerCase() !== 'model') {
          return;
        }

        const toolbar = objectEditor?.toolbar;
        if (!toolbar || Ext.getCmp('model-generate-frames-' + data.id)) {
          return;
        }

        toolbar.add('-');
        toolbar.add({
          id: 'model-generate-frames-' + data.id,
          text: 'Generate Frames',
          tooltip: 'Generate one frame child per final product detail row',
          iconCls: 'pimcore_icon_apply',
          scale: 'medium',
          disabled: objectEditor.isAllowed && (!objectEditor.isAllowed('create') || !objectEditor.isAllowed('save')),
          handler: function () {
            const run = function () {
              const saveData = objectEditor.getSaveData ? objectEditor.getSaveData(null, true) : {};
              const button = Ext.getCmp('model-generate-frames-' + data.id);

              if (button) {
                button.setDisabled(true);
              }
              if (objectEditor.tab) {
                objectEditor.tab.mask('Generating frames...');
              }

              Ext.Ajax.request({
                url: '/admin/model-frame-generator/generate/' + data.id,
                method: 'POST',
                params: {
                  data: saveData?.data || ''
                },
                success: function (response) {
                  const result = Ext.decode(response.responseText);
                  const message = result.message || 'Frames generated';

                  if (result.errors && result.errors.length) {
                    pimcore.helpers.showPrettyError('error', 'Generate Frames', message + '<br>' + Ext.util.Format.htmlEncode(result.errors.join("\n")));
                  } else {
                    pimcore.helpers.showNotification('Generate Frames', message, 'success');
                  }

                  mergeCreatedFramesIntoFinalProducts(objectEditor, result.created || []);
                  pimcore.elementservice.refreshNodeAllTrees('object', data.id);
                },
                failure: function (response) {
                  let message = 'Frame generation failed';
                  try {
                    const result = Ext.decode(response.responseText);
                    message = result.message || message;
                  } catch (e) {}

                  pimcore.helpers.showNotification('Generate Frames', message, 'error');
                },
                callback: function () {
                  if (objectEditor.tab) {
                    objectEditor.tab.unmask();
                  }
                  if (button) {
                    button.setDisabled(false);
                  }
                }
              });
            };

            Ext.Msg.confirm(
              'Generate Frames',
              'This will create unpublished frame children for the current model. Continue?',
              function (choice) {
                if (choice === 'yes') {
                  run();
                }
              }
            );
          }
        });

        toolbar.updateLayout();
      } catch (e) {
        console.warn('[model-generate-frames] postOpenObject error', e);
      }
    });

    console.log('[model-generate-frames] plugin registered');
  } catch (e) {
    console.error('[model-generate-frames] init error', e);
    setTimeout(init, 500);
  }
})();
