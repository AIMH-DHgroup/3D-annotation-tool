<?php
/**
 * Enumerate GLB and ZIP files in a user's models directory.
 *
 * Receives a POST request with a JSON body containing a `username`.
 * Returns a JSON array of filenames (without any path) located in
 * `php/models/<username>/` that have `.glb` or `.zip` extensions.
 * Input is sanitised to prevent directory traversal.  Requires the
 * requester to be logged in.
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['login_user']) || !isset($_SESSION['id_user'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true);
    // Sanitize username from the request body.  Only allow safe characters
    // to prevent directory traversal.
    $rawUsername = $input['username'] ?? '';
    $username    = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid username.']);
        exit;
    }

    // Path to the user's models directory
    $directory = __DIR__ . '/models/' . $username . DIRECTORY_SEPARATOR;

    if (!is_dir($directory)) {
        echo json_encode(['success' => true, 'array' => []]);
        exit;
    }

    $files = scandir($directory);

    $glbFiles = array_filter($files, function($file) use ($directory) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        return in_array($ext, ['glb', 'zip']) && is_file($directory . $file);
    });

    echo json_encode(['success' => true, 'array' => array_values($glbFiles)]);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}