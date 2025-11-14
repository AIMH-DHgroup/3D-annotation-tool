<?php
/**
 * Persist the user's UI theme preference to the session.
 *
 * Expects a JSON body with a `theme` property set to either `light` or
 * `dark`.  The user must be authenticated via `id_user` in the session.
 * Invalid themes result in a 400 response.  On success the chosen
 * theme is stored in the PHP session and a JSON object with
 * `status: ok` is returned.
 */
session_start();

if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$theme = $data['theme'] ?? 'dark';
if (!in_array($theme, ['light', 'dark'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Theme not allowed.']);
    exit;
}

$_SESSION['theme'] = $theme;

echo json_encode(['status' => 'ok']);