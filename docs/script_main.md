# script_main.js — Reference

Purpose
-------
`script_main.js` implements the dashboard UI used on `index.html`.
It handles session checks and authentication, uploads of 3D models, and
simple model management actions (view, rename, download, delete). It
is UI-focused and expects several DOM elements and Bootstrap to be present.

Public API (functions used by inline event handlers)
---------------------------------------------------
- `showAlert(id, message, level, time = 1000)` — create a transient alert inside `#alert-placeholder`.
- `showModal(forceShow, title, text, btnCancel, btnOK, callbackCancel, callbackOK, classButton='btn-primary')` — show a Bootstrap modal and wire its buttons.
- `uploadModel(thisModel, username)` — attach a change handler to a file input to upload a .glb or .zip model for `username`.
- `addAnnotationToList(modelName, username)` — create a list item with View/Rename/Download/Delete controls for a remote model.
- `checkModels(username)` — fetch the list of models from `./php/scanGLB.php` and populate the UI.
- `displayMainForm(data)` — show the main dashboard (assumes `data.username`).
- `checkSession_and_displayMainForm(refresh = false)` — check server session and optionally refresh displayed model list.

DOM elements required
---------------------
- `#alert-placeholder` — container for alerts
- `#modal-container` with children:
  - `#modal-title`, `.modal-body`, `#modal-dismiss`, `#modal-confirm`
- `#loader` — page overlay shown while uploading
- `#model-list`, `#model-list-container` — list of models and the container used to enable scrolling
- `#form-model`, `#model-div`, `#main-form-container`, `footer` — elements hidden/shown when opening the iframe viewer
- `#model-input` — the file input used for uploads
- `#login-button`, `#logout-btn`, `#username`, `#password` — login controls

Server endpoints used
---------------------
All endpoints are called using relative paths from the index page:
- `./php/login.php` — POST login credentials
- `./php/checkSession.php` — GET/POST session validation
- `./php/upload3DModel.php` — POST FormData with a file
- `./php/scanGLB.php` — POST username to list models
- `./php/renameModel.php` — POST rename payload
- `./php/deleteModel.php` — POST delete payload

Quick verification / smoke-check
--------------------------------
1. Open `index.html` in a browser with devtools open.
2. Verify there are no console errors and that `#alert-placeholder` exists.
3. Test login with valid credentials and ensure the main form appears.
4. Try uploading a small `.glb` file. Verify the loader appears and a success alert is shown.
5. Click `View` on a model — an iframe should open (full-screen); check no JS errors.
6. Try `Rename` and `Delete` flows and ensure server responses show alerts.

Notes & Next steps
------------------
- Consider extracting API calls into a small helper (e.g., `api.js`) to centralize fetch logic and session handling.
- Add unit tests for helper functions if you later refactor to modules.

Maintainers: Keep this document updated if DOM ids or endpoints change.
