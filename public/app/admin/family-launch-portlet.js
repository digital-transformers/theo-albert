console.log('[family-launch-portlet] loaded');

(function registerFamilyLaunchPortlet() {
  if (
    typeof Class === 'undefined'
    || typeof Ext === 'undefined'
    || !window.pimcore
    || !pimcore.layout
    || !pimcore.layout.portlets
    || !pimcore.layout.portlets.abstract
  ) {
    setTimeout(registerFamilyLaunchPortlet, 200);
    return;
  }

  pimcore.registerNS('pimcore.layout.portlets.familyLaunchModels');

  pimcore.layout.portlets.familyLaunchModels = Class.create(pimcore.layout.portlets.abstract, {
    periods: [
      { value: 'jan', label: 'January' },
      { value: 'apr', label: 'April' },
      { value: 'sum', label: 'Summer' },
      { value: 'silmo', label: 'Silmo' }
    ],

    getType: function () {
      return 'pimcore.layout.portlets.familyLaunchModels';
    },

    getName: function () {
      return 'Family Launch Models';
    },

    getIcon: function () {
      return 'pimcore_icon_object';
    },

    getLayout: function (portletId) {
      var fields = ['year'];
      Ext.Array.each(this.periods, function (period) {
        fields.push(period.value);
      });

      var store = new Ext.data.Store({
        autoDestroy: true,
        proxy: {
          type: 'ajax',
          url: '/admin/family-launch-portlet/data',
          reader: {
            type: 'json',
            rootProperty: 'rows'
          }
        },
        fields: fields
      });

      var grid = Ext.create('Ext.grid.Panel', {
        store: store,
        columns: this.getColumns(),
        stripeRows: true,
        scrollable: true,
        viewConfig: {
          enableTextSelection: true,
          deferEmptyText: false,
          emptyText: 'No family launch data found.'
        },
        listeners: {
          cellclick: this.onCellClick.bind(this)
        }
      });

      var config = this.getDefaultConfig();
      config.tools = [
        {
          type: 'refresh',
          handler: function () {
            store.reload();
          }
        }
      ].concat(config.tools || []);

      this.layout = Ext.create('Portal.view.Portlet', Object.assign(config, {
        title: this.getName(),
        iconCls: this.getIcon(),
        height: 450,
        layout: 'fit',
        items: [grid]
      }));

      this.layout.portletId = portletId;
      store.load();

      return this.layout;
    },

    getColumns: function () {
      var columns = [{
        text: 'Year',
        dataIndex: 'year',
        width: 58,
        sortable: false
      }];

      Ext.Array.each(this.periods, function (period) {
        columns.push({
          text: period.label,
          dataIndex: period.value,
          minWidth: 125,
          flex: 1,
          sortable: false,
          renderer: this.renderPeriodCell.bind(this)
        });
      }.bind(this));

      return columns;
    },

    renderPeriodCell: function (families) {
      if (!families || !families.length) {
        return '<span style="color:#999;">-</span>';
      }

      var html = ['<div style="white-space:normal;line-height:1.2;">'];

      Ext.Array.each(families, function (family) {
        html.push(
          '<div style="margin-bottom:6px;">',
          '<div style="font-weight:bold;margin-bottom:2px;">',
          this.html(family.label),
          '</div>'
        );

        if (family.models && family.models.length) {
          Ext.Array.each(family.models, function (model) {
            html.push(
              '<div style="margin:1px 0 1px 4px;">',
              '<a href="#" class="family-launch-model-link" data-model-id="',
              parseInt(model.id, 10),
              '">',
              this.html(model.label),
              '</a>',
              '</div>'
            );
          }.bind(this));
        } else {
          html.push('<div style="color:#999;font-style:italic;margin-left:4px;">No models</div>');
        }

        html.push('</div>');
      }.bind(this));

      html.push('</div>');

      return html.join('');
    },

    onCellClick: function (grid, td, cellIndex, record, tr, rowIndex, e) {
      var target = e.getTarget('.family-launch-model-link');
      if (!target) {
        return;
      }

      e.stopEvent();

      var id = parseInt(target.getAttribute('data-model-id'), 10);
      if (!id) {
        return;
      }

      pimcore.helpers.openObject(id, 'object');

      Ext.defer(function () {
        try {
          pimcore.treenodelocator.showInTree(id, 'object');
        } catch (error) {
          console.warn('[family-launch-portlet] show in tree failed', error);
        }
      }, 300);
    },

    html: function (value) {
      return Ext.util.Format.htmlEncode(value === null || value === undefined ? '' : String(value));
    }
  });
})();
