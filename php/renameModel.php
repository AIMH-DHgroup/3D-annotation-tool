<?php
/**
 * Rename a user's model and its associated narration JSON file.
 *
 * Expects a POST request with a JSON body containing `username`,
 * `oldModelName` and `newModelName`.  These values are sanitised to
 * permit only safe characters.  The user must be authenticated via
 * session.  The model `.zip` file in `php/models/<username>/` is
 * renamed, and if a corresponding `.json` file exists in
 * `storage/json/<username>/` it is also renamed (copied to the new
 * name and the old file removed).  A JSON response is returned
 * indicating success or failure.
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
    // Sanitize username and model names.  Allow only safe characters to prevent
    // directory traversal and injection.  If any value is invalid after
    // sanitization, abort.
    $rawUsername    = $input['username'] ?? '';
    $username       = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid username.']);
        exit;
    }

    $rawOldModelName = $input['oldModelName'] ?? '';
    $oldModelName    = preg_replace('/[^A-Za-z0-9_.-]/', '', basename($rawOldModelName));
    if ($oldModelName === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid old model name.']);
        exit;
    }

    $rawNewModelName = $input['newModelName'] ?? '';
    $newModelName    = preg_replace('/[^A-Za-z0-9_.-]/', '', basename($rawNewModelName));
    if ($newModelName === '') {
        echo json_encode(['status' => 'error', 'message' => 'Invalid new model name.']);
        exit;
    }

    // Build directory paths.  Models are stored under php/models/<username> and
    // narration files are stored under storage/json/<username>.
    $modelsDir = __DIR__ . '/models/' . $username . DIRECTORY_SEPARATOR;
    $jsonDir   = dirname(__DIR__) . '/storage/json/' . $username . DIRECTORY_SEPARATOR;

    $oldFilePathModel = $modelsDir . $oldModelName . '.zip';
    $newFilePathModel = $modelsDir . $newModelName . '.zip';
    $oldFilePathJson  = $jsonDir   . $oldModelName . '.json';
    $newFilePathJson  = $jsonDir   . $newModelName . '.json';

    if (!is_dir($modelsDir)) {
        echo json_encode(['success' => false, 'message' => 'User directory not found: ' . $modelsDir]);
        exit;
    } else {

        $model_rename = false;
        $json_rename = false;

        // Rename the model
        if (rename($oldFilePathModel, $newFilePathModel)) $model_rename = true;

        // Rename the json if it exists
        if (file_exists($oldFilePathJson)) {

            if ($oldFilePathJson === $newFilePathJson) $json_rename = true;
            else {
                if (copy($oldFilePathJson, $newFilePathJson)) {
                    unlink($oldFilePathJson);
                    $json_rename = true;
                }
            }

        } else $json_rename = true; // if json_dir not exists than there is nothing to check

        $model_rename
            ? $json_rename
                ? $response = ['status' => 'success', 'message' => "Model $newModelName renamed successfully."]
                : $response = ['status' => 'error', 'message' => "Failed to rename $newModelName.json."]
            : $response = ['status' => 'error', 'message' => "Failed to rename $newModelName."];

        echo json_encode($response);
        exit;

    }

} else echo json_encode(['success' => false, 'message' => 'Invalid request.']);