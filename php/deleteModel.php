<?php
/**
 * Delete a model and its associated narration JSON for the current user.
 *
 * This endpoint accepts a POST request with a JSON body containing a
 * `modelName` property.  The filename is sanitised to remove any
 * disallowed characters and extensions, preventing directory traversal
 * attacks.  The user must be authenticated via the session.  Models
 * are stored as `.zip` archives under `php/models/<username>/` and
 * narration files are stored as `.json` under `storage/json/<username>/`.
 * If either file exists it will be removed.  A successful deletion
 * returns a JSON response with `status: success`, otherwise an
 * appropriate error message is returned.
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
    // Sanitise the username stored in the session (it may contain dots used as separators).
    $rawUsername = $_SESSION['login_user'] ?? '';
    $username = preg_replace('/[^A-Za-z0-9_.-]/', '', $rawUsername);
    if ($username === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid username.']);
        exit;
    }

    // Sanitise the model name provided by the client.  Remove any extension to avoid
    // appending `.zip.zip` later and restrict the allowed characters.
    $rawModelName = $input['modelName'] ?? '';
    $modelName = preg_replace('/[^A-Za-z0-9_.-]/', '', basename($rawModelName));
    if ($modelName === '') {
        echo json_encode(['success' => false, 'message' => 'Invalid model name.']);
        exit;
    }

    // Build absolute paths for model and JSON files.  Models are stored as .zip archives
    // inside php/models/<username> and narration JSON files are stored under storage/json/<username>.
    $modelsDir = __DIR__ . '/models/' . $username . DIRECTORY_SEPARATOR;
    $jsonDir   = dirname(__DIR__) . '/storage/json/' . $username . DIRECTORY_SEPARATOR;
    $filePathModel = $modelsDir . $modelName . '.zip';
    $filePathJson  = $jsonDir   . $modelName . '.json';

    if (!is_dir($modelsDir)) {
        echo json_encode(['success' => false, 'message' => 'User directory not found.']);
        exit;
    } else {

        $response = [];
        $model_delete = false;
        $json_delete = false;

        // Try to delete the model zip file
        if (file_exists($filePathModel)) {
            $unlinkResult = unlink($filePathModel);
            if ($unlinkResult) {
                $model_delete = true;
            }
        } else {
            $response[] = ['status' => 'error', 'message' => 'Model file does not exist.'];
        }

        // Try to delete the JSON narration file (if it exists)
        if (file_exists($filePathJson)) {
            $unlinkResult = unlink($filePathJson);
            if ($unlinkResult) {
                $json_delete = true;
            }
        } else {
            $json_delete = true; // if the file does not exist there is nothing to remove
        }

        $model_delete
            ? $json_delete
                ? $response = ['status' => 'success', 'message' => "Model $modelName deleted successfully."]
                : $response = ['status' => 'error', 'message' => "Failed to delete $modelName.json."]
            : $response = ['status' => 'error', 'message' => "Failed to delete $modelName."];

        echo json_encode($response);
        exit;

    }

} else echo json_encode(['success' => false, 'message' => 'Invalid request.']);