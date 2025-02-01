## GMDprivateServer
### Geometry Dash Server Emulator

### Setup

Initial:
1. Upload files to webserver
2. Configure `config/connection.php` to match your MySQL settings
3. Import `database.sql` file to your database
4. Edit links in GD's `.exe` and `.so` files

Updating:
1. Upload files to webserver
2. Set `$installed` to false in config/dashboard.php

### Credits:
Most of the stuff in generateHash.php has been figured out by pavlukivan and Italian APK Downloader, so credits to them