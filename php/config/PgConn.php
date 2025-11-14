<?php
/**
 * Bootstrap the PostgreSQL connection for the application.
 *
 * This file conditionally includes either a demo or production
 * connection configuration based on a session flag `Demon_on`.
 * When `Demon_on` is set in the session it loads a demo connection
 * from `../../../../try/PgConnDemo.php`; otherwise it loads the
 * production connection from `../../../../try/PgConn.php`.  These
 * external files should define a `$dbconn` resource for use by the
 * application.  This indirection allows developers to test against
 * a demonstration database without changing application code.  The
 * actual connection details should be stored outside of the web root
 * for security.
 */
session_start();

if (isset($_SESSION['Demon_on'])) {
    require('../../../../try/PgConnDemo.php');
} else {
    require('../../../../try/PgConn.php');
}