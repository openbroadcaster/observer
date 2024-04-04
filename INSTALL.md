# OpenBroadcaster - Server Installation Instructions

## Dependencies

- A web server with a web environment available (e.g., Apache, Nginx)
- PHP 8.2 or higher
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

2. Navigate to the cloned repository directory within the web document root and run the following command to install PHP and JavaScript dependencies.

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

9. Set up a cron job to run the `cron.php` script regularly. This script is responsible for clearing old cache and unused upload files. The following is an example crontab entry.

```
*/5 * * * * php /path/to/openbroadcaster/cron.php
```
