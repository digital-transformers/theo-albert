(function () {
    'use strict';

    const CHUNK_SIZE = 16 * 1024 * 1024;
    const CHUNK_THRESHOLD = 64 * 1024 * 1024;
    const MAX_RETRIES = 3;

    if (!window.pimcore?.helpers?.uploadAssetFromFileObject) {
        return;
    }

    const originalUpload = pimcore.helpers.uploadAssetFromFileObject;
    const originalUploadDialog = pimcore.helpers.uploadDialog;

    function uploadId() {
        if (window.crypto?.randomUUID) {
            return window.crypto.randomUUID();
        }

        return Date.now().toString(16) + '-' + Math.random().toString(16).slice(2);
    }

    function progressEvent(loaded, total) {
        return {
            lengthComputable: true,
            loaded: loaded,
            total: total
        };
    }

    function parseResponse(request) {
        try {
            return JSON.parse(request.responseText);
        } catch (error) {
            return {
                success: false,
                message: request.responseText || request.statusText || 'Upload failed.'
            };
        }
    }

    function chunkedUpload(file, targetUrl, callbackSuccess, callbackProgress, callbackFailure) {
        const target = new URL(targetUrl, window.location.origin);
        const id = uploadId();
        let offset = 0;

        const sendChunk = function (attempt) {
            const end = Math.min(offset + CHUNK_SIZE, file.size);
            const data = new FormData();
            data.append('Filedata', file.slice(offset, end), file.name + '.part');
            data.append('uploadId', id);
            data.append('filename', file.name);
            data.append('offset', String(offset));
            data.append('totalSize', String(file.size));
            data.append('parentId', target.searchParams.get('parentId') || '');
            data.append('parentPath', target.searchParams.get('parentPath') || '');
            data.append('dir', target.searchParams.get('dir') || '');
            data.append('allowOverwrite', target.searchParams.get('allowOverwrite') || '0');
            data.append('operation', target.pathname.endsWith('/admin/asset/import-zip') ? 'importZip' : 'asset');
            data.append('csrfToken', pimcore.settings.csrfToken);

            const request = new XMLHttpRequest();
            request.open('POST', '/admin/chunked-asset-upload/chunk');
            request.setRequestHeader('X-pimcore-csrf-token', pimcore.settings.csrfToken);

            request.upload.addEventListener('progress', function (event) {
                if (event.lengthComputable) {
                    callbackProgress(progressEvent(offset + event.loaded, file.size));
                }
            });

            request.addEventListener('load', function () {
                const response = parseResponse(request);
                if (request.status >= 200 && request.status < 300 && response.success === true) {
                    offset = end;
                    callbackProgress(progressEvent(offset, file.size));

                    if (response.complete === true) {
                        callbackSuccess(response, request.statusText, request);
                    } else {
                        sendChunk(0);
                    }
                    return;
                }

                if (attempt < MAX_RETRIES && request.status >= 500) {
                    window.setTimeout(function () {
                        sendChunk(attempt + 1);
                    }, 500 * (attempt + 1));
                    return;
                }

                callbackFailure(request, request.statusText, response);
            });

            request.addEventListener('error', function (event) {
                if (attempt < MAX_RETRIES) {
                    window.setTimeout(function () {
                        sendChunk(attempt + 1);
                    }, 500 * (attempt + 1));
                    return;
                }

                callbackFailure(request, request.statusText, event);
            });

            request.send(data);
        };

        sendChunk(0);
    }

    pimcore.helpers.uploadAssetFromFileObject = function (
        file,
        url,
        callbackSuccess,
        callbackProgress,
        callbackFailure
    ) {
        callbackSuccess = typeof callbackSuccess === 'function' ? callbackSuccess : function () {};
        callbackProgress = typeof callbackProgress === 'function' ? callbackProgress : function () {};
        callbackFailure = typeof callbackFailure === 'function' ? callbackFailure : function () {};

        if (!file?.size || file.size <= CHUNK_THRESHOLD) {
            return originalUpload(file, url, callbackSuccess, callbackProgress, callbackFailure);
        }

        return chunkedUpload(file, url, callbackSuccess, callbackProgress, callbackFailure);
    };

    pimcore.helpers.uploadDialog = function (url, filename, success, failure, description) {
        const target = new URL(url, window.location.origin);
        if (!target.pathname.endsWith('/admin/asset/import-zip')) {
            return originalUploadDialog(url, filename, success, failure, description);
        }

        filename = typeof filename === 'string' && filename ? filename : 'Filedata';
        success = typeof success === 'function' ? success : function () {};
        failure = typeof failure === 'function' ? failure : function () {};

        const items = [];
        if (description) {
            items.push({ xtype: 'displayfield', value: description });
        }

        const uploadWindow = new Ext.Window({
            autoHeight: true,
            title: t('upload'),
            closeAction: 'close',
            width: 501,
            modal: true
        });

        items.push({
            xtype: 'fileuploadfield',
            emptyText: t('select_files'),
            fieldLabel: t('file'),
            width: 470,
            name: filename,
            buttonText: '',
            buttonConfig: { iconCls: 'pimcore_icon_upload' },
            listeners: {
                change: function (field) {
                    const file = field.fileInputEl.dom.files[0];
                    if (!file) {
                        return;
                    }
                    if (file.size > pimcore.settings.upload_max_filesize) {
                        pimcore.helpers.showNotification(
                            t('error'),
                            t('file_is_bigger_that_upload_limit') + ' ' + file.name,
                            'error'
                        );
                        return;
                    }

                    const progressBar = new Ext.ProgressBar({ width: 465, text: file.name });
                    const progressWindow = new Ext.Window({
                        items: [progressBar],
                        modal: true,
                        closable: false,
                        bodyStyle: 'padding:10px;',
                        width: 500,
                        autoHeight: true
                    });
                    uploadWindow.close();
                    progressWindow.show();

                    chunkedUpload(
                        file,
                        url,
                        function (response, statusText, request) {
                            progressWindow.close();
                            success({ response: request });
                        },
                        function (event) {
                            if (event.lengthComputable) {
                                const ratio = event.loaded / event.total;
                                progressBar.updateProgress(
                                    ratio,
                                    file.name + ' ( ' + Math.floor(ratio * 100) + '% )'
                                );
                            }
                        },
                        function (request) {
                            progressWindow.close();
                            failure({
                                response: {
                                    responseText: request.responseText || JSON.stringify({
                                        success: false,
                                        message: request.statusText || 'Upload failed.'
                                    })
                                }
                            });
                        }
                    );
                }
            }
        });

        uploadWindow.add(new Ext.form.FormPanel({
            fileUpload: true,
            width: 500,
            bodyStyle: 'padding: 10px;',
            items: items
        }));
        uploadWindow.show();

        return uploadWindow;
    };
}());
