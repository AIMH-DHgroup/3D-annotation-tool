<?php
/**
 * Save or overwrite a narrative JSON file for the current user.
 *
 * Accepts a POST request with a JSON body containing `fileName` and
 * `data` fields.  The username is derived from the session and
 * sanitised, and the filename is sanitised to prevent directory
 * traversal.  The narrative data is written to
 * `storage/json/<username>/<fileName>.json`, overwriting any existing
 * file of the same name.  Authentication is required via the session.
 * Responses are returned as JSON with `success` and `message` keys.
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['login_user']) || !isset($_SESSION['id_user'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit();
    }

    // Decode json
    $input = json_decode(file_get_contents('php://input'), true);

    // Get name and data
    $fileName = $input['fileName'] ?? 'default.json';
    $jsonData = $input['data'] ?? '';

    // Get username from session and sanitise it.  The login_user session variable
    // may contain dots used as separators; only allow safe characters.
    $rawUsername = $_SESSION['login_user'] ?? '';
    $username = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(["success" => false, "message" => "Invalid username."]);
        exit;
    }

    // Sanitize the file name to avoid directory traversal.  Only keep safe
    // characters and strip any path components.  Append .json extension if
    // not already present.
    $safeFileName = preg_replace('/[^A-Za-z0-9_.-]/', '', basename($fileName));
    if ($safeFileName === '') {
        echo json_encode(["success" => false, "message" => "Invalid file name."]);
        exit;
    }

    // Destination directory for JSON files: storage/json/<username>/
    $uploadDir = dirname(__DIR__) . '/storage/json/' . $username . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        echo json_encode(["success" => false, "message" => "Unable to create user JSON directory."]);
        exit;
    }

    $filePath = $uploadDir . $safeFileName;

    // Overwrite the file
    if (file_put_contents($filePath, $jsonData) !== false) {
        echo json_encode(["success" => true, "message" => "$safeFileName file overwritten successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Error saving the file: $safeFileName."]);
    }
} else echo json_encode(["success" => false, "message" => "Not supported request method."]);
