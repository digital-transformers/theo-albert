console.log('[automatic-image-linking] loaded');

(function init() {
  try {
    if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.events) {
      return setTimeout(init, 300);
    }

    if (window.pimcore?.settings?.csrfToken) {
      Ext.Ajax.defaultHeaders = Ext.Ajax.defaultHeaders || {};
      Ext.Ajax.defaultHeaders['X-pimcore-csrf-token'] = pimcore.settings.csrfToken;
    }

    if (window.__automaticImageLinkingRegistered) {
      return;
    }
    window.__automaticImageLinkingRegistered = true;

    const currentUserCanLinkImages = function () {
      const user = pimcore.globalmanager ? pimcore.globalmanager.get('user') : null;

      return !!(user && (user.admin || (user.isAllowed && user.isAllowed('automatic_image_linking'))));
    };

    const encode = function (value) {
      return Ext.util.Format.htmlEncode(String(value || ''));
    };

    const renderSummary = function (result) {
      const linked = (result.linked || []).map(function (row) {
        return '<li>' + encode(row.path) + ' -> ' + encode(row.objectPath) + ' / ' + encode(row.field) + (row.changed ? '' : ' (already linked)') + '</li>';
      }).join('');

      const orphan = (result.orphan || []).map(function (row) {
        return '<li>' + encode(row.path) + ' - ' + encode(row.reason) + '</li>';
      }).join('');

      const errors = (result.errors || []).map(function (error) {
        return '<li>' + encode(error) + '</li>';
      }).join('');

      return [
        '<p>' + encode(result.message || '') + '</p>',
        '<h3>Linked images</h3><ul>' + (linked || '<li>None</li>') + '</ul>',
        '<h3>Orphan images</h3><ul>' + (orphan || '<li>None</li>') + '</ul>',
        errors ? '<h3>Errors</h3><ul>' + errors + '</ul>' : ''
      ].join('');
    };

    document.addEventListener(pimcore.events.postOpenAsset, function (event) {
      try {
        const assetEditor = event?.detail?.asset || event?.detail?.object;
        const data = assetEditor?.data || {};
        if ((event?.detail?.type || data.type) !== 'folder') {
          return;
        }

        if (!currentUserCanLinkImages()) {
          return;
        }

        const toolbar = assetEditor.getLayoutToolbar ? assetEditor.getLayoutToolbar() : assetEditor.toolbar;
        if (!toolbar || Ext.getCmp('automatic-image-linking-' + data.id)) {
          return;
        }

        toolbar.add('-');
        toolbar.add({
          id: 'automatic-image-linking-' + data.id,
          text: 'Link Images',
          tooltip: 'Link images in this folder to family, model, or frame objects by filename',
          iconCls: 'pimcore_icon_apply',
          scale: 'medium',
          disabled: assetEditor.isAllowed && !assetEditor.isAllowed('view'),
          handler: function () {
            const button = Ext.getCmp('automatic-image-linking-' + data.id);
            const run = function () {
              if (button) {
                button.setDisabled(true);
              }
              if (assetEditor.tab) {
                assetEditor.tab.mask('Linking images...');
              }

              Ext.Ajax.request({
                url: '/admin/automatic-image-linking/process-folder/' + data.id,
                method: 'POST',
                success: function (response) {
                  const result = Ext.decode(response.responseText);
                  Ext.create('Ext.window.Window', {
                    title: 'Image Linking Summary',
                    width: 760,
                    height: 520,
                    modal: true,
                    layout: 'fit',
                    items: [{
                      xtype: 'panel',
                      bodyPadding: 16,
                      autoScroll: true,
                      html: renderSummary(result)
                    }],
                    buttons: [{
                      text: 'Close',
                      handler: function (btn) {
                        btn.up('window').close();
                      }
                    }]
                  }).show();
                  pimcore.helpers.showNotification('Link Images', result.message || 'Image linking completed', result.errors?.length ? 'warning' : 'success');
                  pimcore.elementservice.refreshNodeAllTrees('asset', data.id);
                },
                failure: function (response) {
                  let message = 'Image linking failed';
                  try {
                    const result = Ext.decode(response.responseText);
                    message = result.message || message;
                  } catch (e) {}

                  pimcore.helpers.showNotification('Link Images', message, 'error');
                },
                callback: function () {
                  if (assetEditor.tab) {
                    assetEditor.tab.unmask();
                  }
                  if (button) {
                    button.setDisabled(false);
                  }
                }
              });
            };

            Ext.Msg.confirm(
              'Link Images',
              'This will scan the folder and link matching images to family, model, or frame objects. Continue?',
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
        console.warn('[automatic-image-linking] postOpenAsset error', e);
      }
    });
  } catch (e) {
    console.error('[automatic-image-linking] init error', e);
    setTimeout(init, 500);
  }
})();
