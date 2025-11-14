<?php
/**
 * Persist an extracted GLB file to the user's models directory.
 *
 * When a user uploads a ZIP file containing a GLB model, the frontend
 * extracts the GLB and calls this endpoint to persist it.  A POST
 * request must include a `username` field and a `file` upload.  The
 * username is sanitised, and the GLB is saved under
 * `php/models/<username>/`.  Only `.glb` files are accepted.  The user
 * must be logged in.  Responses are JSON with `status` and
 * `message` keys describing the result.
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
        $fileName  = basename($file['name']);
        $filePath = $uploadDir . $fileName;

        // Check if the file is a .glb file
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if ($fileExtension === 'glb') {
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                echo json_encode(['status' => 'success', 'message' => 'GLB file saved successfully.']);
            } else {
                $errorDetails = [
                    'error' => error_get_last()
                ];
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Error saving GLB file.',
                    'details' => $errorDetails
                ]);
            }
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid file type. Only .glb files are allowed.'
            ]);
        }
    } else {
        $errorDetails = [
            'tmp_name' => $file['tmp_name'],
            'error' => error_get_last()
        ];
        echo json_encode([
            'status' => 'error',
            'message' => 'Error uploading the GLB file.',
            'details' => $errorDetails
        ]);
    }
} else {
    $errorDetails = [
        'error' => error_get_last()
    ];
    echo json_encode([
        'status' => 'error',
        'message' => 'No files uploaded.',
        'details' => $errorDetails
    ]);
}