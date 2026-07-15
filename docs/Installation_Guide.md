# School Management System — Installation & Setup Guide

This guide provides instructions to deploy, configure, and run the School Management System (EduPortal) in a local development environment (XAMPP on Windows) or a production environment.

---

## 1. Prerequisites

Before installing the application, ensure you have the following software installed:

- **XAMPP (version 8.1 or higher)**
  - Apache HTTP Server
  - MySQL Database Server
  - PHP 8.1+ (with PDO, MySQLi, Session, OpenSSL, and FileInfo extensions enabled)
- **Web Browser** (Chrome, Firefox, Edge, or Safari)
- **A text editor/IDE** (VS Code, PHPStorm, etc.)

---

## 2. Step-by-Step Installation

### Step 2.1: Clone or Copy Project Files
1. Open your XAMPP installation directory (typically `C:\xampp\htdocs\`).
2. Create a folder named `Talent` (or copy the project folder directly).
3. Place all the project files and folders inside this directory:
   ```text
   C:\xampp\htdocs\Talent\
   ├── assets/
   ├── config/
   ├── database/
   ├── docs/
   ├── helpers/
   ├── models/
   ├── views/
   └── index.php
   ```

### Step 2.2: Set Up the Database
1. Start XAMPP Control Panel and start the **Apache** and **MySQL** services.
2. Open your browser and go to `http://localhost/phpmyadmin/`.
3. Click on **New** in the left sidebar to create a new database.
4. Enter the database name: `school_management` and choose collation `utf8mb4_general_ci`, then click **Create**.
5. Select the newly created database, click on the **Import** tab.
6. Click **Choose File** and locate the SQL schema file:
   `C:\xampp\htdocs\Talent\database\schema.sql`
7. Click the **Import** or **Go** button at the bottom of the page.
8. Wait for the database import to finish. You should see 16 tables populated.

### Step 2.3: Configure Database Connection
1. Open `C:\xampp\htdocs\Talent\config\database.php` in a text editor.
2. Confirm or edit the database credentials to match your local MySQL configuration:
   ```php
   <?php
   class Database {
       private static $host = "localhost";
       private static $db_name = "school_management";
       private static $username = "root";  // Default XAMPP user
       private static $password = "";      // Default XAMPP password is empty
       // ...
   }
   ```
3. Save the file.

### Step 2.4: Initialize Default Admin Account
The database schema automatically creates a default administrator account:
- **Username**: `admin`
- **Password**: `admin123`

Other default seed accounts (if schema contains seeds):
- **Teacher Employee ID / Username**: Look at the `users` and `teachers` or `students` tables in PHPMyAdmin to find generated usernames. Default password for seeded accounts is typically `password123` or similar.

---

## 3. Directory Permissions
Ensure the upload directories are writable by the web server. When the application runs, it automatically attempts to create the following directories inside `C:\xampp\htdocs\Talent\assets\uploads\`:
- `profiles/` (for student and teacher avatars)
- `system/` (for system attachments)
- `logos/` (for the school logo)

If there are write errors, ensure your user profile or XAMPP's Apache process has full Read/Write permissions to the `Talent/assets/` directory.

---

## 4. Virtual Host Configuration (Optional but Recommended)
To run the system on a custom local domain (e.g., `http://eduportal.local`) instead of `http://localhost/Talent/`, follow these steps:

1. Open `C:\Windows\System32\drivers\etc\hosts` as Administrator and add:
   ```text
   127.0.0.1   eduportal.local
   ```
2. Open `C:\xampp\apache\conf\extra\httpd-vhosts.conf` and append:
   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/xampp/htdocs/Talent"
       ServerName eduportal.local
       <Directory "C:/xampp/htdocs/Talent">
           Options Indexes FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```
3. Restart Apache in the XAMPP Control Panel.
4. Access the portal at `http://eduportal.local/`.

---

## 5. Security Recommendations for Production
If deploying the system to a live production server:
1. **SSL/HTTPS**: Ensure SSL is enabled. The session cookie configuration in `config/config.php` will automatically enable secure session cookies.
2. **Database Credentials**: Change the MySQL password from blank (`""`) to a secure random string.
3. **Turn Off PHP Errors**: In production `php.ini`, set `display_errors = Off` and log errors to a secure file.
4. **Writable folders**: Do not allow execute permissions inside the upload directories. Use `.htaccess` files or server configuration to prevent `.php` file execution in `assets/uploads/`.
