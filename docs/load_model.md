# load_model.js — Reference

This document summarises `assets/js/load_model.js` (the primary 3D viewer
and annotation script) and provides a quick reference for maintainers.

## Purpose

- Load and display 3D models (.glb or .zip containing a .glb).
- Support AR / WebXR modes via Three.js.
- Provide annotation CRUD with Canvas-based sprites and HTML labels.
- Persist annotations to server-side JSON files.

## Public API (functions attached to `window`)

- `saveJSONAnnotations(refresh=false)` — Save current annotation set to server.
- `deleteAnnotation()` — Remove the currently selected annotation and persist changes.
- `addDigitalObject(url, title, auto=false, validation=true)` — Add a digital object entry to the form.
- `displayDigObjTooltip(el)` — Show tooltip for truncated digital object title.
- `confirmDeleteDigObj(url)` — Remove a digital object preview by URL.
- `changeCoors()` — Toggle "change coordinates" (coordinate-picking) mode.
- `refreshViewer()` — Broadcast a local change so other windows/tabs refresh.
- `refreshTheme(theme)` — Broadcast a theme change (light/dark) for cross-tab sync.
- `applyTheme(theme)` — Apply theme locally.
- `closeTooltips(tooltip)` — Close an open bootstrap tooltip.

## Important DOM elements expected

The script assumes these elements exist on the page:

- `#loader` — page-level loading indicator
- `#alert-placeholder` — notifications container
- `#annotationsPanel`, `#annotation-list`, `#annotation-list-div` — annotation UI
- `#modal-container` with `#modal-title`, `.modal-body`, `#modal-dismiss`, `#modal-confirm` — modal UI
- `#label-renderer` — container used by CSS2D/CSS3D renderers
- `#scene-container` — used to change cursor state when selecting coordinates

If you add or rename elements, update the script accordingly.

## External library dependencies

- three.js and its loaders (GLTFLoader, CSS2DObject, CSS3DObject)
- ZIP utilities (ZipReader, ZipWriter, BlobReader, BlobWriter) used for zip extraction/creation
- Bootstrap (Modal, Tooltip) for UI components
- gsap (for smooth camera animations)
- (Optional) Sketchfab viewer API for synchronising with Sketchfab annotations

## Server endpoints

The module calls multiple PHP endpoints (relative paths):

- `./php/checkSession.php` — session validation
- `./php/saveJson.php` — save JSON annotation files
- `./php/saveZipModels.php` — receive uploaded zip files
- `./php/saveGLBModels.php` — receive uploaded glb files
- `./php/upload3DModel.php` — upload handler used elsewhere
- `./php/removeGLB.php` — remove temporary GLB files

Ensure these endpoints are present and accept the inputs the script sends.

## Developer notes and suggestions

- The file is large and mixes several concerns. Consider splitting into modules:
  - `loader.js` — file/zip loading, createZip/extractZip
  - `scene.js` — Three.js scene initialisation, render loop, XR setup
  - `annotations.js` — annotation creation, DOM wiring and persistence
  - `ui.js` — modal, alerts, tooltips and small helpers

- Add unit tests around small, pure functions where possible (e.g. `normalizePositions`,
  `wrapText`, `truncate`). DOM-heavy code is harder to unit-test but can be
  covered by end-to-end tests.

- Performance: creating many CanvasTextures can consume GPU memory. If you find
  leaks, track texture disposal (`texture.dispose()`) and remove unused canvases.

## Quick validation checklist (manual)

- [ ] Open `scene.html`/`customAnn.html` in a browser and ensure the loader and
      modal elements exist and don't throw console errors on start.
- [ ] Try loading a small `.glb` model; confirm annotations can be added and saved.
- [ ] Upload and load a `.zip` containing a `.glb` to exercise the ZIP flow.
- [ ] Check browser console/network for failed requests to `./php/*` endpoints.

## Example usage snippet

From an HTML page that includes `assets/js/load_model.js` and the required DOM:

```html
<!-- required DOM elements -->
<div id="loader"></div>
<div id="alert-placeholder"></div>
<div id="modal-container">...modal markup...</div>
<div id="label-renderer"></div>

<script src="path/to/three.min.js"></script>
<script src="assets/js/load_model.js"></script>
<script>
  // call public API
  window.refreshViewer();
</script>
```

## Where to look for specific functionality

- ZIP handling: `extractZip`, `createZip`, `saveZip` functions inside the script.
- Annotation rendering: `createSprite`, `createCanvas`, `createAnnotationDOM`.
- UI helpers: `showModal`, `showAlert`, `showLoader`, `hideLoader`.

---

If you'd like, I can also:

- Split the file into the suggested module files and wire them via ES modules.
- Add small unit tests for pure helpers and a smoke test script.
- Add inline function-level JSDoc for each exported helper.

Which next step do you prefer?