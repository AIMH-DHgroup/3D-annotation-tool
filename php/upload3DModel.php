<?php
/**
 * Upload a 3D model (GLB or ZIP) for the currently logged in user.
 *
 * This endpoint accepts a POST request with a multipart/form‑data body
 * containing a `username` field and a `file` upload.  The supplied
 * username is sanitised to allow only letters, numbers, dots, underscores
 * and hyphens.  Uploaded files are stored in a per‑user subdirectory
 * within `php/models/`.  Only files with a `.glb` or `.zip` extension
 * (case insensitive) are accepted.  The user must already be logged in
 * (validated via PHP session) or the request will be rejected with
 * a 403 status.
 *
 * Responses are returned as JSON with a `status` property indicating
 * success or error, and a descriptive `message`.  Errors such as
 * missing parameters, invalid usernames, incorrect file types or
 * filesystem failures are reported explicitly without leaking sensitive
 * information about the server.
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['username']) || !isset($_FILES['file'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing username or file.']);
        exit;
    }

    if (!isset($_SESSION['login_user']) || !isset($_SESSION['id_user'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit();
    }

    /*
     * Sanitize the provided username. Only allow alphanumeric characters,
     * underscores, hyphens and dots. This prevents directory traversal and
     * other injection attacks.  If the resulting username is empty after
     * sanitization, return an error.
     */
    $rawUsername = $_POST['username'] ?? '';
    // Remove any characters that are not letters, numbers, dot, underscore or hyphen
    $username = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username provided.']);
        exit;
    }

    // Define the destination directory for uploaded models.  Models are stored in
    // a per‑user subdirectory under the php/models directory.  Using __DIR__ ensures
    // that the path resolves relative to this script regardless of where it is deployed.
    $uploadDir = __DIR__ . '/models/' . $username . DIRECTORY_SEPARATOR;

    // Create the directory if it does not exist with safe permissions (0755).  The
    // recursive flag allows creation of nested directories.
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to create upload directory.']);
        exit;
    }

    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $filePath = $uploadDir . basename($file['name']);

        // Check if the file is a .glb or .zip file (case insensitive)
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['glb', 'zip'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid file type. Only .glb or .zip allowed.']);
            exit;
        }

        // Move the uploaded file to the destination directory.  If the operation fails
        // return a clear error message.  Do not expose the temporary file name in the
        // response.
        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['status' => 'success', 'message' => 'Model saved successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error saving the file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File upload error.', 'code' => $file['error']]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}