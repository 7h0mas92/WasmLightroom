



'use strict';

let wasmModule = null;

self.onmessage = async function(e) {
    const { type, data } = e.data;

    switch (type) {
        case 'init':
            try {
                importScripts('/wasm/filters.js');
                wasmModule = await FilterModule({
                    locateFile: (path) => {
                        if (path.endsWith('.wasm')) return '/wasm/' + path;
                        if (path.endsWith('.worker.js')) return '/wasm/' + path;
                        return path;
                    }
                });
                self.postMessage({ type: 'ready' });
            } catch (err) {
                self.postMessage({ type: 'error', error: err.message });
            }
            break;

        case 'applyFilter':
            if (!wasmModule) {
                self.postMessage({ type: 'error', error: 'Wasm non initialisé' });
                return;
            }
            try {
                const result = processFilter(data);
                self.postMessage(
                    { type: 'result', imageData: result },
                    [result.buffer]
                );
            } catch (err) {
                self.postMessage({ type: 'error', error: err.message });
            }
            break;
    }
};

function processFilter({ pixels, width, height, filter, intensity }) {
    const numPixels = width * height;
    const dataLen = pixels.length;

    const ptr = wasmModule._malloc(dataLen);
    wasmModule.HEAPU8.set(pixels, ptr);

    switch (filter) {
        case 'grayscale':
            wasmModule._apply_grayscale(ptr, numPixels, intensity);
            break;
        case 'sepia':
            wasmModule._apply_sepia(ptr, numPixels, intensity);
            break;
        case 'invert':
            wasmModule._apply_invert(ptr, numPixels, intensity);
            break;
        case 'brightness':
            wasmModule._apply_brightness(ptr, numPixels, intensity - 50);
            break;
        case 'contrast':
            wasmModule._apply_contrast(ptr, numPixels, intensity - 50);
            break;
    }

    const result = new Uint8ClampedArray(dataLen);
    result.set(wasmModule.HEAPU8.subarray(ptr, ptr + dataLen));
    wasmModule._free(ptr);

    return result;
}
