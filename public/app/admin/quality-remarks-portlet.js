console.log('[quality-remarks-portlet] loaded');

(function registerQualityRemarksPortlet() {
  if (
    typeof Class === 'undefined'
    || typeof Ext === 'undefined'
    || !window.pimcore
    || !pimcore.globalmanager
    || !pimcore.layout
    || !pimcore.layout.portlets
    || !pimcore.layout.portlets.abstract
  ) {
    setTimeout(registerQualityRemarksPortlet, 200);
    return;
  }

  var user = pimcore.globalmanager.get('user');
  if (!user) {
    setTimeout(registerQualityRemarksPortlet, 200);
    return;
  }

  if (!user.admin && !(user.isAllowed && user.isAllowed('quality_control'))) {
    return;
  }

  pimcore.registerNS('pimcore.layout.portlets.qualityRemarks');

  pimcore.layout.portlets.qualityRemarks = Class.create(pimcore.layout.portlets.abstract, {
    getType: function () {
      return 'pimcore.layout.portlets.qualityRemarks';
    },

    getName: function () {
      return 'Quality Remarks';
    },

    getIcon: function () {
      return 'pimcore_icon_notes';
    },

    getLayout: function (portletId) {
      var portlet = this;
      var store = new Ext.data.Store({
        autoDestroy: true,
        proxy: {
          type: 'ajax',
          url: '/admin/quality-remarks-portlet/data',
          reader: {
            type: 'json',
            rootProperty: 'rows'
          }
        },
        fields: [
          { name: 'objectId', type: 'int' },
          'objectType',
          'objectTypeLabel',
          'code',
          'name',
          'label',
          'path',
          'createdAt',
          'createdBy',
          'type',
          'status',
          'remark'
        ]
      });

      var filters = this.createFilters(store);
      var grid = Ext.create('Ext.grid.Panel', {
        store: store,
        columns: this.getColumns(),
        stripeRows: true,
        scrollable: true,
        tbar: filters.toolbar,
        viewConfig: {
          enableTextSelection: true,
          deferEmptyText: false,
          emptyText: 'No quality remarks found.'
        },
        listeners: {
          cellclick: this.onCellClick.bind(this)
        }
      });

      store.on('beforeload', function (currentStore, operation) {
        var params = filters.getValues();
        Ext.Object.each(params, function (key, value) {
          currentStore.getProxy().setExtraParam(key, value);
        });
      });

      store.on('load', function (currentStore, records, successful, operation) {
        var response = operation && operation.getResponse ? operation.getResponse() : null;
        if (!successful || !response || !response.responseText) {
          return;
        }

        try {
          var payload = Ext.decode(response.responseText);
          if (payload.warnings && payload.warnings.length) {
            console.warn('[quality-remarks-portlet] backend warnings', payload.warnings);
          }

          if (payload.truncated) {
            portlet.layout.setTitle('Quality Remarks: showing ' + records.length + ' of ' + payload.total);
          } else {
            portlet.layout.setTitle('Quality Remarks: ' + records.length);
          }
        } catch (error) {
          console.warn('[quality-remarks-portlet] unable to read response metadata', error);
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
        height: 520,
        layout: 'fit',
        items: [grid]
      }));

      this.layout.portletId = portletId;
      store.load();

      return this.layout;
    },

    createFilters: function (store) {
      var reloadTask = new Ext.util.DelayedTask(function () {
        store.load();
      });

      var reloadSoon = function () {
        reloadTask.delay(350);
      };

      var objectType = Ext.create('Ext.form.field.ComboBox', {
        width: 130,
        queryMode: 'local',
        editable: false,
        forceSelection: true,
        value: 'all',
        valueField: 'value',
        displayField: 'label',
        store: Ext.create('Ext.data.Store', {
          fields: ['value', 'label'],
          data: [
            { value: 'all', label: 'All objects' },
            { value: 'family', label: 'Family' },
            { value: 'model', label: 'Model' },
            { value: 'frame', label: 'Frame' }
          ]
        }),
        listeners: {
          change: reloadSoon,
          select: reloadSoon
        }
      });

      var code = this.createTextFilter('Code / object', 160, reloadSoon);
      var type = this.createTextFilter('Type', 95, reloadSoon);
      var status = this.createTextFilter('Status', 95, reloadSoon);
      var createdBy = this.createTextFilter('By', 105, reloadSoon);
      var remark = this.createTextFilter('Remark contains', 170, reloadSoon);
      var limit = Ext.create('Ext.form.field.Number', {
        width: 82,
        minValue: 1,
        maxValue: 2000,
        value: 500,
        hideTrigger: true,
        emptyText: 'Limit',
        listeners: {
          change: reloadSoon,
          specialkey: function (field, event) {
            if (event.getKey() === event.ENTER) {
              store.load();
            }
          }
        }
      });

      return {
        toolbar: [
          objectType,
          code,
          type,
          status,
          createdBy,
          remark,
          limit,
          {
            text: 'Clear',
            iconCls: 'pimcore_icon_delete',
            handler: function () {
              objectType.setValue('all');
              code.setValue('');
              type.setValue('');
              status.setValue('');
              createdBy.setValue('');
              remark.setValue('');
              limit.setValue(500);
              store.load();
            }
          }
        ],
        getValues: function () {
          return {
            objectType: objectType.getValue() || 'all',
            code: code.getValue() || '',
            type: type.getValue() || '',
            status: status.getValue() || '',
            createdBy: createdBy.getValue() || '',
            remark: remark.getValue() || '',
            limit: limit.getValue() || 500
          };
        }
      };
    },

    createTextFilter: function (emptyText, width, reloadSoon) {
      return Ext.create('Ext.form.field.Text', {
        width: width,
        emptyText: emptyText,
        enableKeyEvents: true,
        listeners: {
          keyup: reloadSoon,
          specialkey: function (field, event) {
            if (event.getKey() === event.ENTER) {
              reloadSoon();
            }
          }
        }
      });
    },

    getColumns: function () {
      return [
        {
          text: 'Date',
          dataIndex: 'createdAt',
          width: 120,
          sortable: false,
          renderer: this.renderText.bind(this)
        },
        {
          text: 'Object Type',
          dataIndex: 'objectTypeLabel',
          width: 90,
          sortable: false,
          renderer: this.renderText.bind(this)
        },
        {
          text: 'Object',
          dataIndex: 'label',
          minWidth: 180,
          flex: 1,
          sortable: false,
          renderer: this.renderObjectCell.bind(this)
        },
        {
          text: 'Type',
          dataIndex: 'type',
          width: 100,
          sortable: false,
          renderer: this.renderText.bind(this)
        },
        {
          text: 'Status',
          dataIndex: 'status',
          width: 100,
          sortable: false,
          renderer: this.renderText.bind(this)
        },
        {
          text: 'By',
          dataIndex: 'createdBy',
          width: 120,
          sortable: false,
          renderer: this.renderText.bind(this)
        },
        {
          text: 'Remark',
          dataIndex: 'remark',
          minWidth: 260,
          flex: 2,
          sortable: false,
          renderer: this.renderRemark.bind(this)
        }
      ];
    },

    renderObjectCell: function (value, metadata, record) {
      var path = record.get('path') || '';
      metadata.tdAttr = 'data-qtip="' + this.html(path) + '"';

      return [
        '<a href="#" class="quality-remarks-object-link" data-object-id="',
        parseInt(record.get('objectId'), 10),
        '">',
        this.html(value || ('#' + record.get('objectId'))),
        '</a>'
      ].join('');
    },

    renderRemark: function (value) {
      var encoded = this.html(value);
      if (encoded === '') {
        return '<span style="color:#999;">-</span>';
      }

      return '<div style="white-space:normal;line-height:1.35;">' + encoded + '</div>';
    },

    renderText: function (value) {
      var encoded = this.html(value);
      return encoded !== '' ? encoded : '<span style="color:#999;">-</span>';
    },

    onCellClick: function (grid, td, cellIndex, record, tr, rowIndex, event) {
      var target = event.getTarget('.quality-remarks-object-link');
      if (!target) {
        return;
      }

      event.stopEvent();

      var id = parseInt(target.getAttribute('data-object-id'), 10);
      if (!id) {
        return;
      }

      this.openObject(id);
    },

    openObject: function (id) {
      pimcore.helpers.openObject(id, 'object');

      Ext.defer(function () {
        try {
          pimcore.treenodelocator.showInTree(id, 'object');
        } catch (error) {
          console.warn('[quality-remarks-portlet] show in tree failed', error);
        }
      }, 300);
    },

    html: function (value) {
      return Ext.util.Format.htmlEncode(value === null || value === undefined ? '' : String(value));
    }
  });
})();
