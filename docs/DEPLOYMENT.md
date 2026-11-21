# Putting the site online

## What GitHub can and cannot do

- **GitHub stores and publishes the code** (the repository), runs the automated tests (Actions), and tracks issues and pull requests.
- **GitHub Pages cannot run this site.** Pages only serves static files. This project needs PHP and a MySQL/MariaDB database, so it must run on a host that provides both.

## Choose a host

| Option | Good for | Notes |
|---|---|---|
| **Shared PHP hosting** (cPanel-style, with MySQL) | Simplest and cheapest | Upload the files, create a database, import the SQL. No command line needed (see below). |
| **A small VPS** (Apache or Nginx + PHP-FPM + MySQL) | Full control | You manage updates and security yourself. |
| **A container platform** | Automated deploys from GitHub | Needs a Dockerfile and a managed MySQL database (not included yet; contributions welcome). |

Requirements: **PHP 8.1 or newer** with the `pdo_mysql`, `mbstring` and `fileinfo` extensions, and **MySQL 5.7+/8 or MariaDB 10.3+**. The CI workflow tests PHP 8.1, 8.2 and 8.3 against MySQL 8.

## Steps on typical shared hosting

1. **Create a database and a database user** in the hosting control panel. Give that user access to this database only (never use the account's main/root user).
2. **Upload the project files** (everything except `.git`). The folder that contains `index.php` is the web root.
3. **Create `config/config.local.php`** on the server with the database details (this file is git-ignored and must never be committed):

   ```php
   <?php
   return [
       'db_host' => 'localhost',
       'db_name' => 'your_database_name',
       'db_user' => 'your_database_user',
       'db_pass' => 'a-long-random-password',
   ];
   ```

4. **Create the tables.** If you have SSH access, run `php tools/install.php`. Otherwise open phpMyAdmin, select your database, and import `database/schema.sql` and then (optionally) `database/seed.sql`. If your host does not allow creating databases from SQL, delete the first two lines (`CREATE DATABASE …` and `USE …`) at the top of each file before importing.
5. **Create the administrator.** With SSH: `php tools/create-admin.php admin`. Without SSH, generate a password hash on your own computer:

   ```bash
   php -r "echo password_hash('choose-a-long-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   then run this in phpMyAdmin (paste your hash):

   ```sql
   INSERT INTO admins (username, password_hash) VALUES ('admin', 'PASTE_THE_HASH_HERE');
   ```

6. **Make the uploads folder writable** by the web server: `assets/images/uploads/` (permissions 755 or 775, depending on the host).
7. Sign in at `/admin/login.php`.

## Before you make it public

- **HTTPS.** Enable it (most hosts offer free certificates). Never sign in to the admin area over plain HTTP on the internet.
- **Turn off `display_errors`** in `php.ini` (or your host's PHP settings panel) so errors do not leak details.
- **Protect `/admin`** with an extra layer if you can: restrict by IP address or add HTTP basic authentication. The login form only adds a short delay after a failed attempt; it has no lockout.
- **Apache `.htaccess` files.** The root `.htaccess` blocks web access to `config/`, `database/`, `includes/`, `tools/`, `docs/` and hidden files, and `assets/images/uploads/.htaccess` stops uploaded files from running as code. They only work on Apache with `AllowOverride` enabled. On **Nginx** you must add equivalent rules yourself: deny `config/`, `database/`, `includes/`, `tools/`, `docs/` and dotfiles, and do not pass anything under `assets/images/uploads/` to PHP.
- **Do not publish `config/config.local.php`** or a database dump that contains admin password hashes.
- **Keep the "not expert-reviewed" labels.** Only mark records "Expert-reviewed" after a qualified person has checked them.
- **Back up the database regularly.**

## Updating

Upload the new files over the old ones (keep `config/config.local.php` and `assets/images/uploads/`). The schema may change between versions; check the README and release notes before importing `schema.sql` again, because importing it **drops and recreates all tables**.
