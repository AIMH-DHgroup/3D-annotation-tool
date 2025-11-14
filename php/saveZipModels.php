<?php
/**
 * Save a ZIP archive of a 3D model to the user's models directory.
 *
 * The frontend sends ZIP files created from GLB uploads to this
 * endpoint.  A POST request must include a `username` field and a
 * `file` upload.  The username is sanitised and the ZIP is stored
 * under `php/models/<username>/`.  The user must be logged in.  A JSON
 * response indicates success or failure.
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {

    if (!isset($_POST['username'])) {
        echo json_encode(['status' => 'error', 'message' => 'Missing username.']);
        exit;
    }

    if (!isset($_SESSION['login_user']) || !isset($_SESSION['id_user'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit();
    }

    // Sanitize username: allow only safe characters.  If empty after sanitisation,
    // abort the request.
    $rawUsername = $_POST['username'] ?? '';
    $username    = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username.']);
        exit;
    }

    $uploadDir = __DIR__ . '/models/' . $username . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Unable to create upload directory.']);
        exit;
    }

    $file = $_FILES['file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        // Construct destination file path inside the user directory
        $fileName = basename($file['name']);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            echo json_encode(['status' => 'success', 'message' => 'ZIP file saved successfully.']);
        } else {
            $errorDetails = [
                'error' => error_get_last()
            ];
            echo json_encode([
                'status' => 'error',
                'message' => 'Error saving ZIP file.',
                'details' => $errorDetails
            ]);
        }
    } else {
        $errorDetails = [
            'tmp_name' => $file['tmp_name'],
            'error' => error_get_last()
        ];
        echo json_encode([
            'status' => 'error',
            'message' => 'Error uploading the ZIP file.',
            'details' => $errorDetails
        ]);
    }
} else {
    $errorDetails = [
        'error' => error_get_last()
    ];
    echo json_encode([
        'status' => 'error',
        'message' => 'No files saved.',
        'details' => $errorDetails
    ]);
}