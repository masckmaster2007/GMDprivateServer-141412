# GMDprivateServer
## Geometry Dash Private Server Emulator

![Geometry Dash](https://img.shields.io/badge/Geometry%20Dash-Server%20Emulator-blue)
![License](https://img.shields.io/badge/license-MIT-green)

A server emulator for Geometry Dash.

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
4. Run the update process through the dashboard

## Credits

- generateHash.php functionality: pavlukivan, Italian APK Downloader

## License

This project is licensed under the MIT License - see the [LICENSE](./license.md) file for details.
