## GMDprivateServer
### A server emulator for Geometry Dash. Supports GD from 1.0 to 2.207

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

- XOR encryption — https://github.com/sathoro/php-xor-cipher — `incl/lib/XOR.php`
- Hash functions — pavlukivan, Italian APK Downloader — `incl/lib/security.php`
- Translit — https://github.com/ashtokalo/php-translit — `config/translit`
- Discord Webhooks — https://github.com/renzbobz/DiscordWebhook-PHP — `config/webhooks/DiscordWebhook.php`
- Common VPNs list — https://github.com/X4BNet/lists_vpn — `config/vpns.txt`
- Contributors — https://github.com/MegaSa1nt/GMDprivateServer/contributors — thank you all!

## License

This project is licensed under the GPL 3.0 License - see the [LICENSE](./license.md) file for details.
