console.log('[commercial-pricing-generator] loaded');

(function init() {
  if (typeof Ext === 'undefined' || !window.pimcore || !pimcore.events) {
    return setTimeout(init, 300);
  }

  if (window.__commercialPricingGeneratorRegistered) {
    return;
  }
  window.__commercialPricingGeneratorRegistered = true;

  document.addEventListener(pimcore.events.postOpenObject, function (event) {
    const objectEditor = event?.detail?.object;
    const data = objectEditor?.data?.general || {};
    const className = (data.className || '').toLowerCase();
    if (className !== 'family' && className !== 'frame') {
      return;
    }

    const toolbar = objectEditor?.toolbar;
    const buttonId = 'commercial-pricing-generator-' + data.id;
    if (!toolbar || Ext.getCmp(buttonId)) {
      return;
    }

    toolbar.add('-');
    toolbar.add({
      id: buttonId,
      text: 'Generate Pricing',
      tooltip: className === 'family'
        ? 'Overwrite pricing on every descendant frame from this Family base price'
        : 'Overwrite pricing on this Frame from its base price',
      iconCls: 'pimcore_icon_money',
      scale: 'medium',
      disabled: objectEditor.isAllowed && !objectEditor.isAllowed('save'),
      handler: function () {
        const scope = className === 'family' ? 'all descendant frames' : 'this frame';
        Ext.Msg.confirm(
          'Generate Pricing',
          'This will overwrite pricing for ' + scope + ' using every eligible commercial pricelist. Continue?',
          function (choice) {
            if (choice !== 'yes') {
              return;
            }

            const saveData = objectEditor.getSaveData ? objectEditor.getSaveData(null, true) : {};
            const button = Ext.getCmp(buttonId);
            button?.setDisabled(true);
            objectEditor.tab?.mask('Generating pricing...');

            Ext.Ajax.request({
              url: '/admin/commercial-pricing-generator/generate/' + data.id,
              method: 'POST',
              params: { data: saveData?.data || '' },
              success: function (response) {
                const result = Ext.decode(response.responseText);
                pimcore.helpers.showNotification('Generate Pricing', result.message || 'Pricing generated.', 'success');
                pimcore.elementservice.refreshNodeAllTrees('object', data.id);
                if (className === 'frame' && objectEditor.reload) {
                  objectEditor.reload();
                }
              },
              failure: function (response) {
                let message = 'Pricing generation failed.';
                try {
                  message = Ext.decode(response.responseText).message || message;
                } catch (e) {}
                pimcore.helpers.showNotification('Generate Pricing', message, 'error');
              },
              callback: function () {
                objectEditor.tab?.unmask();
                button?.setDisabled(false);
              }
            });
          }
        );
      }
    });
    toolbar.updateLayout();
  });
})();
