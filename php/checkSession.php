<?php
/**
 * Provide a summary of the current session state for the frontend.
 *
 * This endpoint returns a JSON object indicating whether a user is
 * logged in (`loggedIn`), the internal session username (`username`),
 * the display name (`usernameToDisplay`), and the numeric user ID
 * (`idUser`).  It does not perform any authentication on its own.
 */
session_start();

echo json_encode([
    'loggedIn' => isset($_SESSION['login_user']),
    'username' => $_SESSION['login_user'] ?? null,
    'usernameToDisplay' => $_SESSION['username_to_display'] ?? null,
    'idUser' => $_SESSION['id_user'] ?? null
]);