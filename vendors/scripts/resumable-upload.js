(function (window) {
    function delay(ms) {
        return new Promise(function (resolve) {
            setTimeout(resolve, ms);
        });
    }

    function createUploadId(prefix) {
        return [
            prefix || 'upload',
            Date.now(),
            Math.random().toString(36).slice(2, 10)
        ].join('_');
    }

    function parseResponse(xhr) {
        if (xhr.response && typeof xhr.response === 'object') {
            return xhr.response;
        }
        try {
            return JSON.parse(xhr.responseText || '{}');
        } catch (error) {
            return {};
        }
    }

    function uploadChunk(options, uploadId, totalChunks, chunkIndex, start, end) {
        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            var formData = new FormData();
            var blob = options.file.slice(start, end);

            xhr.open('POST', options.endpoint || 'upload_chunk.php', true);
            xhr.responseType = 'json';
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.upload.onprogress = function (event) {
                if (!event.lengthComputable || typeof options.onProgress !== 'function') {
                    return;
                }
                var loaded = start + event.loaded;
                var total = options.file.size || end;
                var percent = total > 0 ? Math.min(100, Math.round((loaded / total) * 100)) : 0;
                options.onProgress({
                    loaded: loaded,
                    total: total,
                    percent: percent,
                    chunkIndex: chunkIndex,
                    totalChunks: totalChunks
                });
            };

            xhr.onload = function () {
                var response = parseResponse(xhr);
                if (xhr.status >= 200 && xhr.status < 300 && response.status === 'success') {
                    if (typeof options.onProgress === 'function') {
                        var total = options.file.size || end;
                        var percent = total > 0 ? Math.min(100, Math.round((end / total) * 100)) : 100;
                        options.onProgress({
                            loaded: end,
                            total: total,
                            percent: percent,
                            chunkIndex: chunkIndex,
                            totalChunks: totalChunks
                        });
                    }
                    resolve(response);
                    return;
                }
                reject(new Error(response.message || ('Chunk upload failed with status ' + xhr.status + '.')));
            };

            xhr.onerror = function () {
                reject(new Error('The connection was interrupted while uploading a chunk.'));
            };

            xhr.onabort = function () {
                reject(new Error('The upload was cancelled before the chunk finished.'));
            };

            formData.append('purpose', options.purpose);
            formData.append('upload_id', uploadId);
            formData.append('original_name', options.file.name);
            formData.append('chunk_index', chunkIndex);
            formData.append('total_chunks', totalChunks);
            formData.append('chunk', blob, options.file.name + '.part');

            xhr.send(formData);
        });
    }

    async function uploadFileInChunks(options) {
        if (!options || !options.file) {
            throw new Error('Missing file for chunked upload.');
        }
        if (!options.purpose) {
            throw new Error('Missing upload purpose.');
        }

        var chunkSize = options.chunkSize || (4 * 1024 * 1024);
        var maxRetries = typeof options.maxRetries === 'number' ? options.maxRetries : 3;
        var retryBaseDelay = typeof options.retryBaseDelay === 'number' ? options.retryBaseDelay : 1200;
        var uploadId = options.uploadId || createUploadId(options.purpose);
        var totalChunks = Math.max(1, Math.ceil(options.file.size / chunkSize));

        if (typeof options.onStart === 'function') {
            options.onStart({
                uploadId: uploadId,
                totalChunks: totalChunks,
                file: options.file
            });
        }

        for (var chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
            var start = chunkIndex * chunkSize;
            var end = Math.min(options.file.size, start + chunkSize);
            var attempt = 0;

            while (true) {
                try {
                    await uploadChunk(options, uploadId, totalChunks, chunkIndex, start, end);
                    break;
                } catch (error) {
                    attempt += 1;
                    if (attempt > maxRetries) {
                        throw error;
                    }
                    if (typeof options.onRetry === 'function') {
                        options.onRetry({
                            uploadId: uploadId,
                            file: options.file,
                            chunkIndex: chunkIndex,
                            totalChunks: totalChunks,
                            attempt: attempt,
                            maxRetries: maxRetries,
                            error: error
                        });
                    }
                    await delay(retryBaseDelay * attempt);
                }
            }
        }

        if (typeof options.onComplete === 'function') {
            options.onComplete({
                uploadId: uploadId,
                totalChunks: totalChunks,
                file: options.file
            });
        }

        return {
            uploadId: uploadId,
            totalChunks: totalChunks,
            fileName: options.file.name,
            fileSize: options.file.size
        };
    }

    function postFormWithRetry(options) {
        return new Promise(function (resolve, reject) {
            var maxRetries = typeof options.maxRetries === 'number' ? options.maxRetries : 2;
            var retryBaseDelay = typeof options.retryBaseDelay === 'number' ? options.retryBaseDelay : 800;
            var attempt = 0;

            function sendRequest() {
                var xhr = new XMLHttpRequest();
                xhr.open(options.method || 'POST', options.url, true);
                xhr.responseType = 'text';
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function () {
                    if (xhr.status >= 200 && xhr.status < 400) {
                        resolve({
                            status: xhr.status,
                            responseText: xhr.responseText || '',
                            responseURL: xhr.responseURL || options.url
                        });
                        return;
                    }

                    attempt += 1;
                    if (attempt > maxRetries) {
                        reject(new Error('Final request failed with status ' + xhr.status + '.'));
                        return;
                    }

                    if (typeof options.onRetry === 'function') {
                        options.onRetry({
                            attempt: attempt,
                            maxRetries: maxRetries,
                            error: new Error('Final request failed with status ' + xhr.status + '.')
                        });
                    }

                    delay(retryBaseDelay * attempt).then(sendRequest);
                };

                xhr.onerror = function () {
                    attempt += 1;
                    if (attempt > maxRetries) {
                        reject(new Error('The final request could not reach the server.'));
                        return;
                    }

                    if (typeof options.onRetry === 'function') {
                        options.onRetry({
                            attempt: attempt,
                            maxRetries: maxRetries,
                            error: new Error('The final request could not reach the server.')
                        });
                    }

                    delay(retryBaseDelay * attempt).then(sendRequest);
                };

                xhr.send(options.formData);
            }

            sendRequest();
        });
    }

    window.ResumablePanelUpload = {
        createUploadId: createUploadId,
        uploadFileInChunks: uploadFileInChunks,
        postFormWithRetry: postFormWithRetry
    };
})(window);
