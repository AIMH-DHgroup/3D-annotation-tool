<?php
/**
 * Delete a temporary GLB file extracted from a user’s ZIP model upload.
 *
 * After a model ZIP is uploaded, the frontend extracts the GLB for
 * processing.  Once the model is loaded client‑side, this endpoint is
 * invoked with a POST request containing a `filePath` parameter.  The
 * file path is expected to be relative to the `php/models/` directory
 * (e.g., `models/<user>/<model>.glb`).  The script resolves the
 * absolute path, verifies it resides within the models directory, and
 * deletes the file if it exists.  Requests from unauthenticated
 * sessions are rejected.
 */
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_SESSION['login_user']) || !isset($_SESSION['id_user'])) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized.']);
        exit();
    }

    // A relative file path is expected (e.g., ./php/models/<user>/model.glb).  Use
    // realpath to resolve the absolute path and ensure it is located within
    // the models directory.  This prevents arbitrary file deletion.
    $filePath = $_POST['filePath'] ?? '';
    // Remove leading "./" if present
    $filePath = preg_replace('/^\.\//', '', $filePath);

    $modelsBase = realpath(__DIR__ . '/models');
    $targetPath = realpath(__DIR__ . '/' . $filePath);

    if ($modelsBase === false || $targetPath === false || strpos($targetPath, $modelsBase) !== 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid file path.']);
        exit;
    }

    if (file_exists($targetPath)) {
        if (is_file($targetPath)) {
            $unlinkResult = unlink($targetPath);
            if ($unlinkResult) {
                echo json_encode(['status' => 'success', 'message' => '.glb file removed successfully.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Failed to remove the .glb file.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Provided path is not a file.']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'File does not exist.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}