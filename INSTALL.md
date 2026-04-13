# OpenBroadcaster - Server Installation Instructions

## Dependencies

- A web server with a web environment available (e.g., Apache, Nginx)
- A [supported PHP version](https://www.php.net/supported-versions.php) (not end of life)
- MySQL or MariaDB database server
- Composer (PHP dependency manager)
- Node.js and npm (Node Package Manager)

## Required PHP Modules

Make sure the following PHP modules are installed and enabled:

- mysql (for MySQL database connectivity)
- mbstring (for multi-byte string handling)
- xml (for XML parsing)
- gd (for image manipulation)
- curl (for making HTTP requests)
- imagick (for advanced image processing)

## Required Packages

Install the following Ubuntu / Debian packages, or the equivalent for your operating system.

- festival (for text-to-speech functionality)
- imagemagick (for image manipulation)
- ffmpeg (for audio/video processing)
- libavcodec-extra (extra codecs for ffmpeg)
- libavfilter-extra (extra filters for ffmpeg)
- vorbis-tools (for Ogg Vorbis audio encoding)

## Installation Steps

1. Copy the OpenBroadcaster Server files to your web server's document root directory.

2. Navigate to the web document root and run the following command to install PHP and JavaScript dependencies.

```
composer install && npm install
```

5. Create a new MySQL or MariaDB database for OpenBroadcaster and import the `db/clean.sql` file to set up the initial database structure.

6. Copy the `config.sample.php` file to `config.php` and open it in a text editor. Set the required configuration items, such as database connection details and other settings specific to your environment.

7. Run the following command to validate your configuration file. Correct any errors displayed in red.

```
tools/cli/ob check
```

6. Run the following command to install database updates. This may take a few minutes to complete.

```
tools/cli/ob updates run all
```

8. Set the password for the default admin user by running the following command. Enter a secure password when prompted.

```
tools/cli/ob passwd admin
```

9. Set up a service (or similar) to run required background tasks such as generating thumbnails and cache management. This service should ensure that `tools/bli/cli cron monitor` is running continously.

For example, set up a service, `/etc/systemd/system/ob.service`, as follows:

```
[Unit]
Description=OB Background Tasks
After=network.target

[Service]
Type=simple
User=obuser
ExecStart=/path/to/ob/tools/cli/ob cron monitor
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then run:

```
systemctl daemon-reload
systemctl enable ob
systemctl start ob
```

