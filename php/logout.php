<?php
/**
 * Log out the current user by destroying their session and clearing the cookie.
 *
 * This endpoint clears all session data, regenerates the session ID and
 * removes the session cookie.  It returns a JSON response indicating
 * success.  No authentication is required to call this endpoint, but
 * callers should ensure they are logged in to avoid unnecessary calls.
 */
session_start();
$_SESSION = [];
session_unset();
session_destroy();

// Remove session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

echo json_encode(['success' => true]);