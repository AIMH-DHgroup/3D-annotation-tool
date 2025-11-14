<?php
/**
 * Retrieve the saved UI theme preference for the current user.
 *
 * The theme (either `light` or `dark`) is stored in the PHP session under
 * the `theme` key.  This endpoint returns the stored theme as JSON.
 * If the user is not logged in (`id_user` not set in the session) the
 * request is rejected with a 403 status.  If no theme has been set
 * previously, `dark` is returned by default.
 */
session_start();

if (!isset($_SESSION['id_user'])) {
    http_response_code(403);
    echo json_encode(['error' => 'User not logged in.']);
    exit;
}

$theme = $_SESSION['theme'] ?? 'dark';

echo json_encode(['theme' => $theme]);