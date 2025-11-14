# Installation and Deployment Guide

This guide explains how to install and run the DLNarratives 3D and AR application on a local Ubuntu server.  The instructions assume you will deploy the project under `/var/www/html` using Apache with PHP 7.4 or later.  Adapt the paths accordingly if you use a different web server or document root.

## 1. Prerequisites

1. **Operating system:** Ubuntu 20.04 LTS or later.
2. **Web server:** Apache 2.4 with PHP support (`libapache2-mod-php`).
3. **PHP packages:**
   - `php` and `php-cli` (version ≥ 7.4).
   - `php-pgsql` for PostgreSQL database access.
   - `php-zip` and `php-mbstring` for file handling.
   - `php-curl` if you plan to fetch remote digital objects.
4. **Database:** PostgreSQL server with a `users` table (containing at least `id`, `username` and `password` columns) and a `narrations` table.  You will need valid credentials to connect (host, port, database name, username and password).

Install the required packages via `apt`:

```bash
sudo apt update
sudo apt install apache2 php libapache2-mod-php php-pgsql php-zip php-mbstring php-curl unzip
```

## 2. Acquire the code

1. Download or clone the repository.  If you have a ZIP archive, extract it:

   ```bash
   unzip DLNarratives-3D-AR-main.zip
   ```

2. Copy the cleaned project into the web server’s document root.  The example below places it under `/var/www/html/dlnarratives`:

   ```bash
   sudo mkdir -p /var/www/html/dlnarratives
   sudo cp -r DLNarratives-3D-AR-cleaned/* /var/www/html/dlnarratives/
   ```

3. Ensure that the web server can read the files and write to the user‑generated folders:

   ```bash
   sudo chown -R www-data:www-data /var/www/html/dlnarratives/api/models /var/www/html/dlnarratives/storage/json
   sudo chmod -R 755 /var/www/html/dlnarratives/api/models /var/www/html/dlnarratives/storage/json
   ```

## 3. Configure the database connection

The application expects a PostgreSQL connection to be defined in a PHP file outside of the web root.  The file `api/config/PgConn.php` includes either `PgConn.php` or `PgConnDemo.php` from a `try/` directory four levels above `api/config/`.  To configure your database:

1. Create the directory structure outside the web root, for example:

   ```bash
   sudo mkdir -p /var/www/try
   ```

2. Create a file `/var/www/try/PgConn.php` with contents similar to the following:

   ```php
   <?php
   /**
    * Production database connection for DLNarratives.
    * Replace the connection parameters with your own.
    */
   $dbconn = pg_connect(
       "host=localhost port=5432 dbname=dlnarratives user=youruser password=yourpassword"
   );
   if (!$dbconn) {
       die('Could not connect to the PostgreSQL database.');
   }
   ```

   Adjust the `host`, `port`, `dbname`, `user` and `password` values to match your environment.  For development purposes you may also create `/var/www/try/PgConnDemo.php` with connection parameters pointing to a demo database.  When the session variable `Demon_on` is set, the application will use the demo connection.

3. Ensure the `try/` directory and its contents are not accessible via the web server.  Placing it outside `/var/www/html` prevents direct access.

## 4. Set up the database schema

The application relies on two tables:

* `users` – stores user credentials.  At minimum it should have columns `id SERIAL PRIMARY KEY`, `username VARCHAR` and `password VARCHAR`.  Passwords are stored as MD5 hashes.  Create records for each user who will access the application.
* `narrations` – stores narrative metadata (e.g., `id`, `title`, `subject`, `user` and optional `copied_from`).  The application reads this table after login to list the user’s narratives.

Example SQL to create these tables:

```sql
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

CREATE TABLE narrations (
    id SERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    subject TEXT,
    "user" INTEGER REFERENCES users(id) ON DELETE CASCADE,
    copied_from INTEGER
);
```

Insert at least one user for testing and compute their password hash using MD5 (e.g., `SELECT md5('plainpassword')`).

## 5. Configure Apache

If using Apache, enable the PHP module and restart the server:

```bash
sudo a2enmod php
sudo systemctl restart apache2
```

You can optionally create a virtual host configuration pointing to `/var/www/html/dlnarratives`, or simply access the project via the default site.  Ensure that `AllowOverride` is set appropriately if you plan to use `.htaccess` files.  No special rewrite rules are required for this application because all endpoints are explicit PHP scripts.

## 6. Launching the application

Open your web browser and navigate to the URL corresponding to the project directory.  For the example above, visit:

```
http://localhost/dlnarratives/index.html
```

You should see the DLNarratives login page.  Log in with the credentials you created in the database.  Once authenticated, you can upload GLB or ZIP files, create annotations, save narrations and switch between light and dark themes.

## 7. Notes and best practices

* **File permissions:** The `api/models/<username>/` and `storage/json/<username>/` directories are created on demand and must be writable by the web server.  Do not set global write permissions; instead assign ownership to the web server user (`www-data` on Ubuntu).
* **SSL:** In production you should serve the application over HTTPS.  Configure your web server accordingly and update the `url` in any external integrations (e.g., Sketchfab viewer).
* **Security:** Never expose the `try/PgConn.php` file or any database credentials through the web server.  Keep them in a directory outside of the document root with restricted permissions.
* **Backups:** Regularly back up the `api/models` and `storage/json` directories as they contain user‑uploaded assets and narratives.

If you encounter issues with uploads or authentication, consult your web server’s error logs (e.g., `/var/log/apache2/error.log`) and ensure that PHP error reporting is enabled during development.
