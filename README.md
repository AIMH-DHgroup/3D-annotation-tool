# DLNarratives – 3D and AR Application

## Overview

DLNarratives is a web application developed by the Digital Humanities Group of AIMH‑Lab (ISTI‑CNR) for creating and exploring interactive 3D and augmented‑reality narratives.  Users can upload 3D models, annotate them with textual descriptions and links to external digital objects, and share the resulting stories through the web.  The frontend is built on top of [three.js](https://threejs.org/) and integrates the Sketchfab viewer API, while the backend uses PHP and PostgreSQL for authentication and persistence.

This repository contains a cleaned and reorganised version of the original project.  The codebase has been restructured to improve maintainability and security without breaking compatibility with the existing deployment at [https://tool.dlnarratives.eu/AR/](https://tool.dlnarratives.eu/AR/).  All server‑side scripts now reside in the `php/` directory, static assets are served from `assets/`, and user‑generated content is stored in `php/models/` (for model files) and `storage/json/` (for narrative JSON files).

## Project Structure

The top‑level layout of the project is as follows:

| Path | Description |
|---|---|
| `/index.html` | Landing page where users log in, upload models, browse their narratives and perform administrative actions. |
| `/scene.html`, `/customAnn.html` | Viewer pages for displaying models and editing annotations (standard and custom annotation modes). |
| `/assets/` | Static resources served to the client.  This includes CSS, JavaScript libraries, images, textures and the entire `three.js` distribution. |
| `/assets/js/` | Application scripts (`script_main.js`, `load_model.js`) and third‑party libraries such as Bootstrap, jQuery and three.js helpers. |
| `/assets/img/` | Icons and logo images used by the UI. |
| `/php/` | All PHP endpoints that implement the backend logic (authentication, file upload/download, model management, annotation persistence, etc.). |
| `/php/models/` | Per‑user directories for uploaded GLB and ZIP model files.  The application creates sub‑directories on demand. |
| `/php/config/` | Configuration entry point for the PostgreSQL connection.  The actual connection details live outside the web root (see `INSTALL.md`). |
| `/storage/json/` | Per‑user directories containing narrative JSON files created and edited through the application. |

## Server API Endpoints

The backend is implemented as a collection of standalone PHP scripts in the `php/` directory.  Each endpoint expects either JSON payloads or multipart/form‑data uploads and returns JSON responses.  The table below summarises the most important endpoints.

| Endpoint | Method | Purpose |
|---|---|---|
| `php/login.php` | POST | Authenticates a user against the PostgreSQL database.  Requires `username` and `password` in the request body.  On success, returns user narrations and initialises the session. |
| `php/logout.php` | GET/POST | Logs out the current user by destroying the session and clearing the cookie. |
| `php/checkSession.php` | GET/POST | Returns a snapshot of the session state (logged‑in flag, username, display name, user ID). |
| `php/upload3DModel.php` | POST | Uploads a `.glb` or `.zip` model file for the authenticated user.  Stores the file under `php/models/<username>/`. |
| `php/saveZipModels.php` | POST | Persists a ZIP archive created client‑side into the user's models directory. |
| `php/saveGLBModels.php` | POST | Persists an extracted GLB file into the user's models directory. |
| `php/scanGLB.php` | POST | Lists the `.glb` and `.zip` files available in `php/models/<username>/`.  Returns an array of filenames. |
| `php/renameModel.php` | POST | Renames a model (and its associated narrative JSON file) within the user's directories. |
| `php/deleteModel.php` | POST | Deletes a model ZIP and its JSON narrative. |
| `php/removeGLB.php` | POST | Deletes a temporary GLB file extracted from a ZIP upload after it has been processed client‑side. |
| `php/saveJson.php` | POST | Saves or overwrites a narrative JSON file in `storage/json/<username>/`. |
| `php/getDigitalObjectPageForCorsP.php` | GET | Fetches a remote HTML page (HTTP/S only) and returns it as JSON.  This is used to embed external digital objects while mitigating SSRF risks. |
| `php/saveThemePreference.php` | POST | Stores the user's UI theme (`light` or `dark`) in the session. |
| `php/getThemePreference.php` | GET | Retrieves the stored theme from the session. |

### Database Configuration

Database connectivity is handled indirectly through `php/config/PgConn.php`.  This file loads an external `PgConn.php` (or `PgConnDemo.php` for demo sessions) from outside the project directory.  You must provide these files yourself (see the installation guide) and configure them to establish a `pg_connect` connection stored in a `$dbconn` variable.  Keeping credentials outside the web root helps protect sensitive information.

## Security Enhancements and Improvements

Several security improvements have been implemented during the reorganisation of the project:

* **Directory structure:** All dynamic PHP endpoints have been moved into an `php/` directory to clearly separate server‑side code from public assets.  Static resources are served exclusively from `assets/`.
* **Input validation:** Usernames, model names and filenames are sanitised using regular expressions to allow only alphanumeric characters, underscores, hyphens and dots.  This prevents path traversal and injection attacks.
* **Prepared database queries:** User credentials in `php/login.php` are validated using parameterised queries (`pg_query_params`) to mitigate SQL injection.
* **File type checks:** Upload endpoints accept only specific file types (e.g., `.glb`, `.zip`) and verify extensions case‑insensitively.
* **Safe file removal:** `php/removeGLB.php` and deletion scripts resolve real paths and ensure that files reside within the intended directories before deletion, preventing arbitrary file removal.
* **SSRF mitigation:** `php/getDigitalObjectPageForCorsP.php` restricts remote requests to HTTP/S schemes and validates the URL before fetching content.
* **Session security:** All endpoints that modify or retrieve user‑specific data verify that the caller is authenticated by checking session variables.  Logout removes the session cookie.

## Compatibility

The cleaned version maintains the original API signatures and relative paths used by the frontend.  Existing clients pointing at `https://tool.dlnarratives.eu/AR/` should continue to function without modification.  If you deploy this project under a different base URL, ensure that the `php` directory remains at the same level as the HTML files so that relative fetch paths in the JavaScript continue to resolve correctly.
