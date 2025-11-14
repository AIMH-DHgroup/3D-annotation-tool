/**
 * script_main.js
 *
 * Main user-interface logic for the DLNarratives dashboard (index.html).
 *
 * Responsibilities
 * - Manage the landing/dashboard UI: show/hide forms, alerts, modals and lists
 * - Handle authentication (calls to `php/login.php`, `php/checkSession.php`)
 * - Upload models (.glb / .zip) via `php/upload3DModel.php`
 * - List, rename, download and delete models using `php/scanGLB.php`,
 *   `php/renameModel.php`, `php/deleteModel.php`
 * - Orchestrate iframe-based viewer launch (scene.html) and related UI
 *
 * Public functions (attached to global window via DOM interactions)
 * - showAlert(id, message, level, time)
 * - showModal(forceShow, title, text, btnCancel, btnOK, callbackCancel, callbackOK, classButton)
 *
 * DOM elements expected by this script
 * - #alert-placeholder         : container for transient alerts
 * - #modal-container          : bootstrap modal element with #modal-title,
 *                               .modal-body, #modal-dismiss and #modal-confirm
 * - #loader                   : loader overlay element (shown/hidden)
 * - #model-list               : container where models are listed
 * - #model-list-container     : outer container used for vertical scroll
 * - #form-model, #model-div, #main-form-container, footer : layout elements toggled
 * - #login-button, #logout-btn, #model-input, #username, #password : inputs and buttons
 *
 * Server endpoints used (relative)
 * - ./php/login.php
 * - ./php/checkSession.php
 * - ./php/upload3DModel.php
 * - ./php/scanGLB.php
 * - ./php/renameModel.php
 * - ./php/deleteModel.php
 *
 * Notes for maintainers
 * - Keep DOM selectors in sync with the HTML templates (index.html)
 * - This file is intentionally small and UI-focused; business logic is
 *   implemented server-side in php/. Consider moving some network code
 *   to a small API wrapper module if this grows.
 */

/**
 * showAlert
 * Create and display a transient Bootstrap-style alert inside
 * `#alert-placeholder`.
 *
 * Parameters:
 * - id (string)     : a short identifier used as a CSS class to allow
 *                     targeted removal/testing of the alert
 * - message (string): HTML/text shown inside the alert
 * - level (string)  : Bootstrap alert level (success, danger, warning, info)
 * - time (number)   : milliseconds until the alert fades out (default 1000)
 *
 * Notes:
 * - This function clears the placeholder before inserting the alert so only
 *   one alert is visible at a time. The alert is removed from the DOM after
 *   it fades out.
 */
function showAlert(id, message, level, time = 1000) {

	let alertDiv = document.createElement("div");
	alertDiv.className = `alert alert-${level} fade show ${id}`;
	alertDiv.setAttribute("role", "alert");
	alertDiv.innerHTML = `<span class='not-selectable'>${message}</span>`;

	let alertPlaceholder = document.getElementById("alert-placeholder");
	alertPlaceholder.innerHTML = "";
	alertPlaceholder.appendChild(alertDiv);

	alertDiv.style.display = "block";
	alertDiv.style.opacity = "1";

	setTimeout(() => {
		alertDiv.style.transition = "opacity 0.3s";
		alertDiv.style.opacity = "0";

		setTimeout(() => {
			if (alertDiv.parentNode) {
				alertDiv.parentNode.removeChild(alertDiv);
			}
		}, 300)
	}, time);
}

/**
 * showModal
 * Show a Bootstrap modal located at `#modal-container` and wire its
 * Cancel/Confirm buttons. This helper centralizes modal behaviour so callers
 * can display messages and hook callbacks without manipulating the DOM.
 *
 * Parameters:
 * - forceShow (boolean): if true, the modal cannot be closed by backdrop or ESC
 * - title (string)     : modal title text
 * - text (string)      : HTML content inserted into the modal body
 * - btnCancel (string) : Cancel button text (or falsy to hide)
 * - btnOK (string)     : OK button text (or falsy to hide)
 * - callbackCancel     : function called when Cancel clicked
 * - callbackOK         : function called when OK clicked
 * - classButton        : CSS class for the confirm button (default 'btn-primary')
 */
function showModal(forceShow, title, text, btnCancel, btnOK, callbackCancel, callbackOK, classButton='btn-primary') {

	// fill HTML modal information
	const modal = document.getElementById('modal-container');
	const modalTitle = modal.querySelector('#modal-title');
	const modalMessage = modal.querySelector('.modal-body');
	const modalCancel = modal.querySelector('#modal-dismiss');
	const modalConfirm = modal.querySelector('#modal-confirm');
	modalTitle.textContent = title;
	modalMessage.innerHTML = text;

	// Destroy any previous instance if needed - for robustness
	if (modal._bootstrapModalInstance) {
		modal._bootstrapModalInstance.hide();
		modal._bootstrapModalInstance.dispose();
	}

	const options = forceShow
		? {backdrop: 'static', keyboard: false}
		: {backdrop: true, keyboard: true};

	const modalBootstrap = new bootstrap.Modal(modal, options);

	if (forceShow) {
		modalBootstrap.show();
		modal.querySelector('.close').style.display = 'none';
		modal.setAttribute('data-bs-backdrop', 'static');   // it cannot be closed by clicking outside
		modal.setAttribute('data-bs-keyboard', 'false');   // it cannot be closed by pressing ESC from keyboard
	} else {
		modal.querySelector('.close').style.display = 'inline-block';
		modal.setAttribute('data-bs-backdrop', 'true');   // default behavior
		modal.setAttribute('data-bs-keyboard', 'true');   // default behavior
	}

	// if button are present add listeners
	if (btnCancel) {

		modalCancel.textContent = btnCancel;

		modalCancel.addEventListener('click', callbackCancel);

		modalCancel.style.display = "inline-block";

	} else modalCancel.style.display = "none";

	if (btnOK) {

		modalConfirm.textContent = btnOK;

		modalConfirm.addEventListener('click', callbackOK);

		modalConfirm.style.display = "inline-block";

		// change class of confirm button
		modalConfirm.classList.remove(...modalConfirm.classList); // remove all classes
		modalConfirm.classList.add('btn');
		modalConfirm.classList.add(classButton);

	} else modalConfirm.style.display = "none";

}

/**
 * hideLoader
 * Hide the global loader overlay (`#loader`). Uses defensive DOM access.
 */
function hideLoader() {
	document.getElementById('loader').style.display = 'none';
}

/**
 * showLoader
 * Show the global loader overlay (`#loader`). Uses defensive DOM access.
 */
function showLoader() {
	document.getElementById('loader').style.display = 'grid';
}

/**
 * addScrolls
 * Adjusts overflow behavior for the model list and per-item containers.
 * - Enables vertical scroll on `#model-list-container` when the model list is
 *   taller than its container.
 * - Enables horizontal scroll for `.model-item` elements when their content
 *   overflows the available width.
 */
function addScrolls() {

	const modelList = document.getElementById('model-list');

	// add vertical scroll
	if (modelList) {
		const modelListContainer = document.getElementById('model-list-container');
		if (modelList.scrollHeight > modelListContainer.clientHeight) modelListContainer.style.overflowY = 'scroll';
		else modelListContainer.style.overflowY = 'initial';

	}

	const items = document.querySelectorAll('.model-item');

	// add horizontal scroll
	items.forEach(item => {
		if (item.scrollWidth > modelList.clientWidth) item.style.overflowX = 'scroll';
		else item.style.overflowX = 'initial';

	});

}

function uploadModel(thisModel, username) {

	thisModel.addEventListener("change", async function(){

		let thisButtonModel = document.getElementById('upload-model');

		if ($(thisModel).prop('files')[0] !== undefined) {

			let fileType = $(thisModel).prop('files')[0].name;
			let validModelTypes = [".glb", ".zip"];

			// if file is not an image
			if (!validModelTypes.includes(fileType.slice(-4))) {

				$(thisButtonModel).empty();
				$(thisButtonModel).text("Upload model");
				$(thisModel).val("");

				hideLoader();

				showAlert('image-type-error', 'Please select a valid model (.glb).', 'warning', 1300);

				// if file is larger than 40 MB, expressed in bytes
			} else if ($(thisModel).prop('files')[0].size > 41943040) {

				$(thisButtonModel).empty();
				$(thisButtonModel).text("Upload model");
				$(thisModel).val("");

				hideLoader();

				showAlert('model-size-error', 'Please select a smaller model (Max 40 MB).', 'warning', 1300);

			} else {

				// file ok
				try {

					showLoader();

					let modelName = $(thisModel).prop('files')[0].name;
					$(thisButtonModel).empty();
					$(thisButtonModel).text("Upload model: " + modelName);

					console.log('Uploading model...');

					const rawFile = $(thisModel).prop('files')[0];
					const fileType = rawFile.name.endsWith('.glb') ? 'model/gltf-binary' : 'application/zip';
					const fixedFile = new File([rawFile], rawFile.name, { type: fileType });

					const formData = new FormData();
					formData.append('file', fixedFile);
					formData.append('username', username);

                    const response = await fetch('./php/upload3DModel.php', {
						method: 'POST',
						body: formData
					});

					const modelResult = await response.json();

					if (modelResult.status === 'error') {

						hideLoader();

						showAlert('model-error', modelResult.message, 'danger', 1600);

						console.error(modelResult.message);

					} else {

						console.log(modelResult.message);

						const time = 1500;

						showAlert('model-success', modelResult.message, 'success', time);

						// reload page after alert timeout
						window.setTimeout( function() {
							window.location.reload();
						}, time);

					}
				} catch (error) {
					console.log(error);
				}

			}

		} else {
			$(thisButtonModel).empty();
			$(thisButtonModel).text("Upload model");
		}

	});
}

/**
 * addAnnotationToList
 * Create a single list item for `modelName` and attach View / Rename /
 * Download / Delete controls. Each control wires server requests and
 * UI transitions (iframe viewer, modal dialogs).
 *
 * Parameters:
 * - modelName (string): filename including extension (e.g. "scene.glb")
 * - username (string) : owner username used for server actions
 */
function addAnnotationToList(modelName, username) {
	const annotationsContainer = document.getElementById('model-list');

	const div = document.createElement('div');
	div.setAttribute('class', 'model-item not-selectable');
	annotationsContainer.appendChild(div);

	const annotationTitle = document.createElement('div');
	annotationTitle.setAttribute('class', 'model-title-div');
	div.appendChild(annotationTitle);

	const annotationName = document.createElement('span');
	annotationName.setAttribute('class', 'model-name');
	annotationName.textContent = modelName.slice(0, -4);	// cut out the extension type
	annotationTitle.appendChild(annotationName);

	const buttonsDiv = document.createElement('div');
	buttonsDiv.setAttribute('class', 'model-buttons-div');
	div.appendChild(buttonsDiv);

	const viewButton = document.createElement('button');
	viewButton.setAttribute('type', 'button');
	viewButton.setAttribute('class', 'btn btn-primary view-model');
	viewButton.addEventListener('click', function () {
		// to give an effect of continuity with that of the iframe
		showLoader();

        fetch('./php/checkSession.php?nocache=' + new Date().getTime())
			.then(res => res.json())
			.then(async function (data) {

				if (data.loggedIn) {

					// prepare iframe when a model is set
					let ifrm = document.createElement('iframe');

					ifrm.setAttribute('src', 'scene.html?model=' + modelName + '&username=' + data.username + '&nocache=' + new Date().getTime());
					ifrm.setAttribute('allow', 'autoplay; xr-spatial-tracking;');
					ifrm.setAttribute('xr-spatial-tracking', '');
					ifrm.setAttribute('execution-while-out-of-viewport', '');
					ifrm.setAttribute('execution-while-not-rendered', '');
					ifrm.setAttribute('web-share', '');
					ifrm.setAttribute('allowfullscreen', '');
					ifrm.setAttribute('mozallowfullscreen', 'true');
					ifrm.setAttribute('webkitallowfullscreen', 'true');
					ifrm.textContent = 'Your browser doesn\'t support iframes.';
					ifrm.style.cssText = 'position:fixed; top:0; left:0; bottom:0; right:0; width:100%; height:100%; border:none;' +
						'margin:0; padding:0; overflow:hidden; z-index:999999;';

					// to give an effect of continuity with that of the iframe
					ifrm.addEventListener('load', function () {
						hideLoader();
					});

					document.body.appendChild(ifrm);
					document.getElementById('alert-placeholder').style.display = 'none';
					document.getElementById('form-model').style.display = 'none';
					document.getElementById('model-div').style.display = 'none';
					document.getElementById('main-form-container').style.display = 'none';
					document.querySelector('footer').style.display = 'none';

				} else {

					showModal(
						true,
						'Session expired',
						'Please login again.',
						undefined,
						'OK',
						function() {
						},
						function() {
							hideLoader();
							window.location.href = './index.html';
						}
					);

				}

			});
	})
	viewButton.textContent = 'View';
	buttonsDiv.appendChild(viewButton);

	const renameButton = document.createElement('button');
	renameButton.setAttribute('type', 'button');
	renameButton.setAttribute('class', 'btn btn-secondary rename-model');
	renameButton.setAttribute('data-toggle', 'modal');
	renameButton.setAttribute('data-target', '.modal');
	renameButton.addEventListener('click', function () {

		showModal(
			false,
			'Rename model',
			'Model name:\n' + '<input id="model-name-input" type="text" value="' + modelName.slice(0, -4) + '" class="form-control">',
			'Cancel',
			'Confirm',
			function () {
			},
			function () {

				showLoader();

                fetch('./php/checkSession.php?nocache=' + new Date().getTime())
					.then(res => res.json())
					.then(async function (data) {

						if (data.loggedIn) {

							const newModelName = document.getElementById('model-name-input').value;

							const username_modelName = {
								username: data.username,
								oldModelName: modelName.slice(0, -4),
								newModelName: newModelName
							}

                            fetch('./php/renameModel.php?nocache=' + new Date().getTime(), {
								method: 'POST',
								headers: {'Content-Type': 'application/json'},
								body: JSON.stringify(username_modelName)
							})
								.then(res => res.json())
								.then(async function (data) {

									if (data.status === 'success') showAlert('rename-success', (data.message || 'Model renamed successfully.'), 'success', 1200);
									else showAlert('rename-error', (data.message || 'Server error renaming the model ' + modelName.slice(0, -4) + '.'), 'danger', 1500);

									// refresh UI
									await checkSession_and_displayMainForm(true);

								})
								.catch(err => {
									console.error('Renaming error:', err);
									showAlert('rename-error', 'Server error renaming model.', 'danger', 1400);
								});

						} else showModal(
							true,
							'Session expired',
							'Please login again.',
							undefined,
							'OK',
							function() {
							},
							function() {
								hideLoader();
								window.location.href = './index.html';
							}
						);

						hideLoader();

					});

			}
		);

	});
	renameButton.textContent = 'Rename';
	buttonsDiv.appendChild(renameButton);

	const downloadButton = document.createElement('button');
	downloadButton.setAttribute('type', 'button');
	downloadButton.setAttribute('class', 'btn btn-default download-model');
	downloadButton.textContent = 'Download';
	downloadButton.addEventListener('click', function () {
    const filePath = './php/models/' + username + '/' + modelName;
		const link = document.createElement('a');
		link.href = filePath;
		link.download = modelName;
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
	});
	buttonsDiv.appendChild(downloadButton);

	const deleteButton = document.createElement('button');
	deleteButton.setAttribute('type', 'button');
	deleteButton.setAttribute('class', 'btn btn-delete delete-model');
	deleteButton.setAttribute('data-toggle', 'modal');
	deleteButton.setAttribute('data-target', '.modal');
	deleteButton.textContent = 'Delete';
	deleteButton.addEventListener('click', function () {

		showModal(
			false,
			'Delete model',
			'Do you really want to delete the model "' + modelName.slice(0, -4) + '"?\nAny associated narration will also be lost.',
			'Close',
			'Delete',
			function () {
			},
			function () {

				showLoader();

                fetch('./php/checkSession.php?nocache=' + new Date().getTime())
					.then(res => res.json())
					.then(async function (data) {

						if (data.loggedIn) {

							const modelNameJson = {
								modelName: modelName.slice(0, -4),
							}

                            fetch('./php/deleteModel.php?nocache=' + new Date().getTime(), {
								method: 'POST',
								headers: {'Content-Type': 'application/json'},
								body: JSON.stringify(modelNameJson)
							})
								.then(res => res.json())
								.then(async function (data) {

									if (data.status === 'success') showAlert('delete-model-success', (data.message || 'Model deleted successfully.'), 'success', 1200);
									else showAlert('delete-model-error', (data.message || 'Server error deleting the model ' + modelName.slice(0, -4) + '.'), 'danger', 1500);

									// refresh UI
									await checkSession_and_displayMainForm(true);

								})
								.catch(err => {
									console.error('Deleting error:', err);
									showAlert('delete-model-error', 'Server error deleting model.', 'danger', 1400);
								});

						} else showModal(
							true,
							'Session expired',
							'Please login again.',
							undefined,
							'OK',
							function() {
							},
							function() {
								hideLoader();
								window.location.href = './index.html';
							}
						);

						hideLoader();

					});

			},
			'btn-delete'
		);

	});
	buttonsDiv.appendChild(deleteButton);
}

/**
 * checkModels
 * Fetch the list of models for `username` from the server and populate the
 * model list UI by calling `addAnnotationToList` for each file.
 *
 * Uses `./php/scanGLB.php` (POST with {username}). On error displays an alert.
 */
async function checkModels (username) {

	const usernameJson = {
		username: username
	};

    fetch('./php/scanGLB.php', {
		method: 'POST',
		headers: {'Content-Type': 'application/json'},
		body: JSON.stringify(usernameJson)
	})
		.then(res => res.json())
		.then(async function (data) {

			if (data.success) {

				const glbFiles = data.array;

				if (glbFiles.length > 0) {
					glbFiles.forEach(function (model) {
						addAnnotationToList(model, username);
					});
				}

				addScrolls();

			} else showAlert('filescan-error', (data.message || 'Server error during file scan.'), 'danger', 1500);
		})
		.catch(err => {
			console.error('GLB file scan error:', err);
			showAlert('glb-filescan-error', 'Server error during file scan.', 'danger', 1500);
		});
}

/**
 * displayMainForm
 * Transition the UI from login view to the dashboard. Shows the main
 * container, sets welcome text and wires the upload input.
 *
 * Parameters:
 * - data (object) : expected to contain `username` returned by the server
 */
async function displayMainForm(data) {

	// remove the login form and add the main form
	document.getElementById('login-container').classList.add('hidden');
	document.getElementById('main-form-container').classList.remove('hidden');

	document.getElementById('welcome-user').textContent = data.username;

	// add 'Upload model' listener
	let modelInput = document.getElementById('model-input');
	modelInput.addEventListener('click', function (){uploadModel(modelInput, data.username);});

	// append models to the list
	await checkModels(data.username);

}

/**
 * checkSession_and_displayMainForm
 * Wrapper that queries `./php/checkSession.php` and, if the session is
 * valid, calls `displayMainForm`. If `refresh` is true it clears the
 * displayed model list before re-fetching.
 */
function checkSession_and_displayMainForm(refresh = false) {

	if (refresh) document.getElementById('model-list').innerHTML = '';

    fetch('./php/checkSession.php?nocache=' + new Date().getTime())
		.then(res => res.json())
		.then(async function (data) {

			if (data.loggedIn) await displayMainForm(data);

		});

}

/**
 * Window onload handler
 * - checks session and displays the main form when possible
 * - wires login and logout button handlers
 */
window.onload = async function () {

	await checkSession_and_displayMainForm();

	// add login listener
	document.getElementById('login-button').addEventListener('click', function(e) {
		e.preventDefault();

		const data = {
			username: document.getElementById('username').value,
			password: document.getElementById('password').value
		};

        fetch('./php/login.php?nocache=' + new Date().getTime(), {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify(data)
		})
			.then(res => res.json())
			.then(async function (data) {
				data.success
					? await displayMainForm(data)
					: showAlert('login-error', (data.message || 'Login failed.'), 'danger', 1500);
			})
			.catch(err => {
				console.error('Login error:', err);
				showAlert('login-server-error', 'Server error during login.', 'danger', 1500);
			});
	});

	// add logout listener
	document.getElementById('logout-btn').addEventListener('click', function () {
        fetch('./php/logout.php?nocache=' + new Date().getTime())
			.then(res => res.json())
			.then(response => {
				if (response.success) {
					// wait to allow the browser to close the session correctly
					setTimeout(() => {
						window.location.href = './index.html';
					}, 500);
				}
			})
			.catch(err => {
				console.error('Logout error:', err);
				showAlert('logout-error', 'Server error during logout.', 'warning', 1500);
			});
	});

}

window.addEventListener('resize', function () {

	addScrolls();

}, true);