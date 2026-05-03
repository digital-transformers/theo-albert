console.log('[quality-control-remarks-table] loaded');

(function patchQualityControlRemarksTable() {
  if (
    typeof Ext === 'undefined'
    || !window.pimcore
    || !pimcore.object
    || !pimcore.object.tags
    || !pimcore.object.tags.table
  ) {
    setTimeout(patchQualityControlRemarksTable, 200);
    return;
  }

  if (pimcore.object.tags.table.prototype.qualityControlRemarksPatched) {
    return;
  }

  var originalInitGrid = pimcore.object.tags.table.prototype.initGrid;
  var originalGetValue = pimcore.object.tags.table.prototype.getValue;

  pimcore.object.tags.table.prototype.qualityControlRemarksPatched = true;

  pimcore.object.tags.table.prototype.initGrid = function () {
    originalInitGrid.apply(this, arguments);

    if (!isQualityControlRemarksTable(this) || !this.grid) {
      return;
    }

    applyQualityControlEditors(this);
  };

  pimcore.object.tags.table.prototype.getValue = function () {
    var data = originalGetValue.apply(this, arguments);

    if (!isQualityControlRemarksTable(this)) {
      return data;
    }

    Ext.Array.each(data, function (row) {
      if (!Ext.isArray(row)) {
        return;
      }

      if (row[0] instanceof Date) {
        row[0] = Ext.Date.format(row[0], 'Y-m-d');
      }

      row[3] = normalizeStatus(row[3]);
    });

    return data;
  };

  function isQualityControlRemarksTable(table) {
    return table
      && table.fieldConfig
      && table.fieldConfig.name === 'qualityControlRemarks';
  }

  function applyQualityControlEditors(table) {
    var columns = table.grid.getColumnManager().getColumns();
    var dateColumn = columns[getColumnIndex(table, 'createdAt', 0)];
    var statusColumn = columns[getColumnIndex(table, 'status', 3)];

    if (dateColumn) {
      setColumnEditor(dateColumn, Ext.create('Ext.form.field.Date', {
        format: 'Y-m-d',
        submitFormat: 'Y-m-d',
        altFormats: 'Y-m-d H:i|Y-m-d H:i:s|Y-m-d|d/m/Y|d.m.Y|m/d/Y',
        editable: true
      }));
      dateColumn.renderer = renderDate;
    }

    if (statusColumn) {
      setColumnEditor(statusColumn, Ext.create('Ext.form.field.ComboBox', {
        queryMode: 'local',
        editable: false,
        forceSelection: true,
        triggerAction: 'all',
        valueField: 'value',
        displayField: 'label',
        store: Ext.create('Ext.data.Store', {
          fields: ['value', 'label'],
          data: [
            { value: 'open', label: 'Open' },
            { value: 'resolved', label: 'Resolved' }
          ]
        })
      }));
      statusColumn.renderer = renderStatus;
    }
  }

  function getColumnIndex(table, key, fallbackIndex) {
    var config = table.fieldConfig.columnConfig || [];
    for (var i = 0; i < config.length; i++) {
      if (config[i] && config[i].key === key) {
        return i;
      }
    }

    return fallbackIndex;
  }

  function setColumnEditor(column, editor) {
    if (column.setEditor) {
      column.setEditor(editor);
    } else {
      column.editor = editor;
    }
  }

  function renderDate(value) {
    if (!value) {
      return '';
    }

    if (value instanceof Date) {
      return Ext.Date.format(value, 'Y-m-d');
    }

    return Ext.util.Format.htmlEncode(String(value).substring(0, 10));
  }

  function renderStatus(value) {
    var status = normalizeStatus(value);
    if (status === '') {
      return '';
    }

    return status === 'resolved' ? 'Resolved' : 'Open';
  }

  function normalizeStatus(value) {
    var status = Ext.isEmpty(value) ? '' : String(value).toLowerCase().trim();
    if (status === '') {
      return '';
    }

    return status === 'resolved' ? 'resolved' : 'open';
  }
})();
