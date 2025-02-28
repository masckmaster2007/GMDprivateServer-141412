## GMDprivateServer
### A server emulator for Geometry Dash.

## Prerequisites

- Web server (Apache/Nginx recommended) or Docker (for containerized deployment)
- PHP 7.0 or higher
- MySQL/MariaDB database

## Installation

### Initial Setup

1. Clone this repository or download the latest release
2. Upload all files to your web server
3. Configure database connection in `config/connection.php`:
   ```php
   $servername = "localhost";
   $port = 3306;
   $username = "root";
   $password = "123123";
   $dbname = "gcs";
   ```
4. Import the `database.sql` file to your MySQL database
5. Modify the Geometry Dash client:
   - Edit links in GD's `.exe` file (Windows)
   - Edit links in GD's `.so` file (Android)
   - Point them to your server's URL

### Updating

1. Backup your existing installation
2. Upload new files to your web server
3. Set `$installed` to `false` in `config/dashboard.php`
4. Server will install missing SQL data in next request to GDPS

## Credits

- Hash functions in incl/lib/security.php: pavlukivan, Italian APK Downloader

## License

This project is licensed under the GPL 3.0 License - see the [LICENSE](./license.md) file for details.
