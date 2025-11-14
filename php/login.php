<?php
/**
 * Authenticate a user against the PostgreSQL database and initiate a session.
 *
 * This endpoint expects a POST request with a JSON payload containing
 * `username` and `password` fields.  User credentials are sanitised and
 * the password is hashed with MD5 (reflecting the legacy storage in the
 * database) before being compared via a parameterised query.  On success
 * the user's identifier is stored in the session and a list of the user's
 * existing narrations is returned.  Invalid credentials or database
 * errors result in a JSON response with `success: false` and an
 * explanatory `error` message.  Note: this endpoint relies on an
 * external `PgConn.php` for database connectivity; ensure appropriate
 * database credentials are configured there.
 */
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $username = $input['username'];
    $password = $input['password'];

    // DB POSTGRES
    // Load database connection configuration from the php/config directory.  This
    // uses an absolute path relative to this script to avoid relying on PHP's
    // include_path and to prevent directory traversal through manipulated input.
    require __DIR__ . '/config/PgConn.php';

    // delete sessiondemo to be able to connect to real database
    if(isset($_SESSION['Demon_on'])){
        session_destroy ();
    }

    $error = '';

	if (empty($username) || empty($password)) {
	    $error = "Username or Password is invalid";
        echo json_encode(["success" => false, "error" => $error]);
        exit;
	} else {

        // Sanitize input data to mitigate SQL injection.  We trim white space and
        // ensure the username only contains allowed characters.  The password is
        // hashed using md5() before sending it to the database for comparison.
        $usernameAdm = trim($username);
        // allow letters, numbers and a few safe punctuation characters
        $usernameAdm = preg_replace('/[^A-Za-z0-9_.-]/', '', $usernameAdm);
        $passwordAdm = trim($password);
        $passwordHash = md5($passwordAdm);

        // Selecting Database using parameterised query.  Use prepared statements
        // to avoid SQL injection.  We only fetch the id column rather than using
        // "select *" for efficiency.
        $query = 'SELECT id FROM users WHERE password = $1 AND username = $2';
        $result = pg_query_params($dbconn, $query, array($passwordHash, $usernameAdm));
        if ($result === false) {
            // Query failed
            $error = pg_last_error($dbconn);
            echo json_encode(["success" => false, "error" => 'Database error: ' . $error]);
            exit;
        }

        $idUser = null;
        while ($row = pg_fetch_row($result)) {
            $idUser = $row[0];
        }
        $numrows = pg_num_rows($result);

        $arr = [];
        if ($numrows === 1) {

            // username for table name
            $_SESSION['login_user'] = str_replace("-","",$usernameAdm) . "." . $idUser;

            // username to display (it is equals to usernames for our users; is different for vre users)
            $_SESSION['username_to_display'] = str_replace("-","",$usernameAdm) . "." . $idUser;

            // id of user
            $_SESSION['id_user'] = $idUser;

            // variable if is vre user
            $_SESSION['VRE_user'] = 0;

            // get all narrations of this user
            $query = "select id, title, subject, copied_from from narrations where \"user\"= '".$_SESSION['id_user']."' order by id desc";
            $result = pg_query($query) or die('Error message: ' . pg_last_error());

            while ($row = pg_fetch_row($result)) {
                array_push($arr, $row);
            }

            pg_free_result($result);

        } else {
            $error = "Username or Password is invalid";
            echo json_encode(["success" => false, "error" => $error]);
            exit;
        }

        pg_close($dbconn); // Closing Connection - $dbconn is from require()
	}

    // delete old session id to avoid conflicts
    session_regenerate_id(true);

    // array json
    $arrayJson = array("success" => true, "jsonData" => $arr, "usernameToDisplay" => $_SESSION['username_to_display'], "username" => $_SESSION['login_user'], "idUser" => $_SESSION['id_user']);
    echo json_encode($arrayJson);

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}