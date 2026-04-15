'use strict';

const EditorState = {
    canvas: null, ctx: null, baseImageData: null, originalImageData: null, currentImageData: null,
    undoStack: [], maxUndo: 20, photoId: null, recipe: [], wasmModule: null, wasmReady: false,
    brushActive: false, brushRadius: 30, maskCanvas: null, maskCtx: null, painting: false,
    currentTool: 'none', currentFilter: 'grayscale', filterIntensity: 100
};

document.addEventListener('DOMContentLoaded', async () => {
    const editorContainer = document.querySelector('.editor-container');
    if (!editorContainer) return;
    try {
        EditorState.canvas = document.getElementById('editor-canvas');
        EditorState.ctx = EditorState.canvas.getContext('2d', { willReadFrequently: true });
        EditorState.photoId = EditorState.canvas.dataset.photoId;

        EditorState.canvas.addEventListener('mousedown', onCanvasMouseDown);
        EditorState.canvas.addEventListener('mousemove', onCanvasMouseMove);
        EditorState.canvas.addEventListener('mouseup', onCanvasMouseUp);
        EditorState.canvas.addEventListener('mouseleave', onCanvasMouseUp);

        document.getElementById('local-filter').addEventListener('change', e => { EditorState.currentFilter = e.target.value; });
        document.getElementById('local-radius').addEventListener('input', e => { EditorState.brushRadius = parseInt(e.target.value); document.getElementById('val-radius').textContent = e.target.value; });
        document.getElementById('local-intensity').addEventListener('input', e => { EditorState.filterIntensity = parseInt(e.target.value); document.getElementById('val-intensity').textContent = e.target.value; });

        try { await initWasm(); } catch(e) { console.error(e); }
        await loadOriginalImage();
        await loadExistingRecipe();
        initMask();
    } catch (err) { console.error(err); } finally {
        const loading = document.getElementById('editor-loading');
        if (loading) loading.style.display = 'none';
        updateUndoButton();
    }
});

async function initWasm() {
    return new Promise(resolve => {
        const to = setTimeout(() => resolve(null), 5000);
        FilterModule({ mainScriptUrlOrBlob: '/wasm/filters.js', locateFile: path => '/wasm/' + path })
        .then(m => { clearTimeout(to); EditorState.wasmModule = m; EditorState.wasmReady = true; resolve(m); })
        .catch(err => { clearTimeout(to); resolve(null); });
    });
}

function saveState() {
    if(!EditorState.currentImageData) return;
    EditorState.undoStack.push(EditorState.ctx.getImageData(0, 0, EditorState.canvas.width, EditorState.canvas.height));
    if(EditorState.undoStack.length > EditorState.maxUndo) EditorState.undoStack.shift();
    updateUndoButton();
}

function updateUndoButton() {
    const btn = document.getElementById('btn-undo');
    if (btn) btn.disabled = EditorState.undoStack.length === 0;
}

window.editorUndo = function() {
    if (EditorState.undoStack.length === 0) return;
    const st = EditorState.undoStack.pop();
    EditorState.ctx.putImageData(st, 0, 0); EditorState.currentImageData = st;
    EditorState.recipe.pop(); renderRecipeList(); updateHistogram(); updateUndoButton();
};

window.editorReset = function() {
    if (EditorState.baseImageData) {
        EditorState.originalImageData = new ImageData(
            new Uint8ClampedArray(EditorState.baseImageData.data),
            EditorState.canvas.width, EditorState.canvas.height
        );
        EditorState.ctx.putImageData(EditorState.baseImageData, 0, 0);
        EditorState.currentImageData = EditorState.ctx.getImageData(0, 0, EditorState.canvas.width, EditorState.canvas.height);
        ['brightness', 'contrast', 'sepia', 'grayscale', 'invert'].forEach(f => {
            const el = document.getElementById('filter-'+f); if(el) el.value = 0;
            const val = document.getElementById('val-'+f); if(val) val.textContent = '0';
        });
        EditorState.undoStack = []; EditorState.recipe = []; renderRecipeList(); updateUndoButton();
        if (EditorState.maskCtx) clearMask();
        updateHistogram();
    }
};

function getFilterTypeId(name) { return { 'grayscale':0, 'sepia':1, 'invert':2, 'brightness':3 }[name] || 0; }

function editorApplyFilters() {
    if (!EditorState.originalImageData) return;
    EditorState.ctx.putImageData(EditorState.originalImageData, 0, 0);
    EditorState.currentImageData = EditorState.ctx.getImageData(0, 0, EditorState.canvas.width, EditorState.canvas.height);
    
    const filters = ['brightness', 'contrast', 'sepia', 'grayscale', 'invert'];
    const vals = {};
    filters.forEach(f => {
        vals[f] = parseInt(document.getElementById('filter-'+f).value);
        document.getElementById('val-'+f).textContent = vals[f];
    });

    if (EditorState.wasmReady && EditorState.wasmModule) {
        const data = EditorState.currentImageData.data;
        const ptr = EditorState.wasmModule._malloc(data.length);
        EditorState.wasmModule.HEAPU8.set(data, ptr);
        filters.forEach(f => {
            if(vals[f]!==0 && f!=='brightness' && f!=='contrast') EditorState.wasmModule.ccall('apply_'+f, null, ['number','number','number'], [ptr, data.length, vals[f]]);
            if(vals[f]!==0 && (f==='brightness'||f==='contrast')) EditorState.wasmModule.ccall('apply_'+f, null, ['number','number','number'], [ptr, data.length / 4, vals[f]]); 
        });
        data.set(new Uint8Array(EditorState.wasmModule.HEAPU8.buffer, ptr, data.length));
        EditorState.wasmModule._free(ptr);
    }
    EditorState.ctx.putImageData(EditorState.currentImageData, 0, 0);
    updateHistogram();
}
window.editorApplyFilters = editorApplyFilters;

function updateHistogram() {
    const hc = document.getElementById('histogram-canvas');
    if (!hc || !EditorState.wasmReady) return;
    const ctx = hc.getContext('2d');
    const data = EditorState.currentImageData.data;
    const ptr = EditorState.wasmModule._malloc(data.length);
    const histPtr = EditorState.wasmModule._malloc(256 * 4);
    EditorState.wasmModule.HEAPU8.set(data, ptr);
    EditorState.wasmModule.ccall('compute_histogram', null, ['number', 'number', 'number'], [ptr, data.length/4, histPtr]);
    
    const histView = new Int32Array(EditorState.wasmModule.HEAP32.buffer, histPtr, 256);
    let max = 0; for(let i=0; i<256; i++) if(histView[i]>max) max=histView[i];
    ctx.clearRect(0,0,hc.width,hc.height);
    if(max>0) {
        ctx.fillStyle = '#6366f1';
        for(let i=0; i<256; i++) {
            const h = (histView[i]/max)*hc.height;
            ctx.fillRect(i, hc.height-h, 1, h);
        }
    }
    EditorState.wasmModule._free(ptr); EditorState.wasmModule._free(histPtr);
}

async function loadOriginalImage() {
    return new Promise((resolve) => {
        const img = new Image(); 
        img.onload = () => {
            EditorState.canvas.width = img.width; EditorState.canvas.height = img.height;
            EditorState.ctx.drawImage(img, 0, 0);
            EditorState.baseImageData = EditorState.ctx.getImageData(0,0,img.width,img.height);
            EditorState.originalImageData = EditorState.ctx.getImageData(0,0,img.width,img.height);
            EditorState.currentImageData = EditorState.ctx.getImageData(0,0,img.width,img.height);
            resolve();
        };
        img.onerror = () => {
            console.error("Erreur de chargement de l'image");
            resolve();
        };
        img.src = '/index.php?route=photo_serve&id=' + EditorState.photoId;
    });
}

function initMask() {
    if (!EditorState.maskCanvas && EditorState.canvas.width > 0) {
        EditorState.maskCanvas = document.createElement('canvas'); EditorState.maskCanvas.width = EditorState.canvas.width; EditorState.maskCanvas.height = EditorState.canvas.height;
        EditorState.maskCtx = EditorState.maskCanvas.getContext('2d', { willReadFrequently: true }); clearMask();
    }
}
function clearMask() { if(EditorState.maskCtx) { EditorState.maskCtx.fillStyle='black'; EditorState.maskCtx.fillRect(0,0,EditorState.canvas.width,EditorState.canvas.height); } }
window.editorClearMask = clearMask;

window.editorSetLocalMode = function(mode) {
    EditorState.currentTool = mode;
    document.getElementById('local-options').style.display = mode==='none' ? 'none' : 'block';
    const btn = document.getElementById('btn-clear-mask'); if (btn) btn.style.display = mode==='brush' ? 'inline-block' : 'none';
    EditorState.canvas.style.cursor = mode==='brush' ? 'crosshair' : mode==='radial' ? 'cell' : 'default';
};

function getMousePos(e) {
    const rect = EditorState.canvas.getBoundingClientRect();
    return { x: (e.clientX - rect.left)*(EditorState.canvas.width/rect.width), y: (e.clientY - rect.top)*(EditorState.canvas.height/rect.height) };
}

function onCanvasMouseDown(e) {
    if(EditorState.currentTool==='none') return;
    saveState(); EditorState.painting = true; const p = getMousePos(e);
    if(EditorState.currentTool==='radial') {
        applyRadial(p.x, p.y, EditorState.brushRadius, EditorState.currentFilter, EditorState.filterIntensity);
        EditorState.recipe.push({ filter_name: 'radial_'+EditorState.currentFilter, parameters: { x:p.x, y:p.y, r:EditorState.brushRadius, intensity:EditorState.filterIntensity } });
        renderRecipeList(); EditorState.painting = false;
    } else if(EditorState.currentTool==='brush') { paintMask(p.x, p.y); }
}
function onCanvasMouseMove(e) { if(EditorState.painting && EditorState.currentTool==='brush') { const p = getMousePos(e); paintMask(p.x, p.y); } }
function onCanvasMouseUp() {
    if(!EditorState.painting) return; EditorState.painting = false;
    if(EditorState.currentTool==='brush') {
        applyBrush();
        EditorState.recipe.push({ filter_name: 'brush_'+EditorState.currentFilter, parameters: { intensity:EditorState.filterIntensity } });
        clearMask(); renderRecipeList();
    }
}
function paintMask(x,y) {
    const r=EditorState.brushRadius; const g=EditorState.maskCtx.createRadialGradient(x,y,0,x,y,r);
    g.addColorStop(0,'rgba(255,255,255,1)'); g.addColorStop(1,'rgba(255,255,255,0)');
    EditorState.maskCtx.beginPath(); EditorState.maskCtx.arc(x,y,r,0,Math.PI*2); EditorState.maskCtx.fillStyle=g; EditorState.maskCtx.fill();
    EditorState.ctx.putImageData(EditorState.currentImageData,0,0);
    EditorState.ctx.globalAlpha=0.5; EditorState.ctx.fillStyle='rgba(255,0,0,0.4)';
    EditorState.ctx.beginPath(); EditorState.ctx.arc(x,y,r,0,Math.PI*2); EditorState.ctx.fill(); EditorState.ctx.globalAlpha=1.0;
}
function applyRadial(x,y,r,type,inty) {
    if(!EditorState.wasmReady) return; const d = EditorState.currentImageData.data; const p = EditorState.wasmModule._malloc(d.length); EditorState.wasmModule.HEAPU8.set(d,p);
    EditorState.wasmModule.ccall('apply_radial_filter', null, ['number','number','number','number','number','number','number','number'], [p, EditorState.canvas.width, EditorState.canvas.height, x, y, r, getFilterTypeId(type), inty]);
    d.set(new Uint8Array(EditorState.wasmModule.HEAPU8.buffer, p, d.length)); EditorState.wasmModule._free(p);
    EditorState.originalImageData = EditorState.ctx.getImageData(0,0,EditorState.canvas.width,EditorState.canvas.height);
    EditorState.originalImageData.data.set(d); EditorState.ctx.putImageData(EditorState.currentImageData,0,0); updateHistogram();
}
function applyBrush() {
    if(!EditorState.wasmReady) return; EditorState.ctx.putImageData(EditorState.currentImageData,0,0);
    const d=EditorState.currentImageData.data, md=EditorState.maskCtx.getImageData(0,0,EditorState.canvas.width,EditorState.canvas.height).data;
    const fastMask = new Uint8Array(EditorState.canvas.width * EditorState.canvas.height);
    for(let i=0; i<fastMask.length; i++) fastMask[i] = md[i*4]; 
    const p=EditorState.wasmModule._malloc(d.length), mp=EditorState.wasmModule._malloc(fastMask.length);
    EditorState.wasmModule.HEAPU8.set(d,p); EditorState.wasmModule.HEAPU8.set(fastMask,mp);
    EditorState.wasmModule.ccall('apply_masked_filter', null, ['number','number','number','number','number'], [p, mp, fastMask.length, getFilterTypeId(EditorState.currentFilter), EditorState.filterIntensity]);
    d.set(new Uint8Array(EditorState.wasmModule.HEAPU8.buffer, p, d.length)); EditorState.wasmModule._free(p); EditorState.wasmModule._free(mp);
    EditorState.originalImageData = EditorState.ctx.getImageData(0,0,EditorState.canvas.width,EditorState.canvas.height);
    EditorState.originalImageData.data.set(d); EditorState.ctx.putImageData(EditorState.currentImageData,0,0); updateHistogram();
}

async function loadExistingRecipe() {
    try { const r=await fetch(`/index.php?route=api_get_recipe&photo_id=${EditorState.photoId}`), d=await r.json();
    if(d.steps) { EditorState.recipe = d.steps; renderRecipeList(); } } catch(e){}
}
function renderRecipeList() { const l=document.getElementById('recipe-list'); if(l) { l.innerHTML=''; EditorState.recipe.forEach((s,i)=>{ const li=document.createElement('li'); li.textContent=`${i+1}. ${s.filter_name}`; l.appendChild(li); }); } }

window.editorSaveRecipe = async () => {
    if(confirm("Sauvegarder ?")) {
        try { const r = await fetch('/index.php?route=api_save_recipe', { method: 'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ photo_id: EditorState.photoId, steps: EditorState.recipe }) });
        const d = await r.json(); alert(d.success ? '✅ Sauvegardé!' : '❌ Erreur'); } catch(e) { alert('Erreur'); }
    }
};
window.editorExport = () => { if(confirm("Exporter l'album (Bonus) ?")) location.href='/index.php?route=api_export_recipe&photo_id='+EditorState.photoId; };
