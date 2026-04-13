# OpenBroadcaster - Server Installation Instructions


## Dependencies

- A web server with a web environment available (e.g., Apache, Nginx)
- A [supported PHP version](https://www.php.net/supported-versions.php) (not end of life)
- MySQL or MariaDB database server
- Composer (PHP dependency manager)
- Node.js and npm (Node Package Manager)


## Required PHP Modules

Make sure the following PHP modules are installed and enabled (listed as Ubuntu/Debian packages):

- php-mysql (for MySQL database connectivity)
- php-mbstring (for multi-byte string handling)
- php-xml (for XML parsing)
- php-gd (for image manipulation)
- php-curl (for making HTTP requests)
- php-imagick (for advanced image processing)


## Required Packages

Install the following Ubuntu/Debian packages, or the equivalent for your operating system.

- festival (for text-to-speech functionality)
- imagemagick (for image manipulation)
- ffmpeg (for audio/video processing)
- libavcodec-extra (extra codecs for ffmpeg)
- libavfilter-extra (extra filters for ffmpeg)
- vorbis-tools (for Ogg Vorbis audio encoding)


## Files and Database Setup

1. Copy the OpenBroadcaster Server files to your web server's document root directory.

2. Navigate to the web document root and run the following command to install PHP and JavaScript dependencies.

```
composer install && npm install
```

3. Create a new MySQL or MariaDB database for OpenBroadcaster and import the `db/clean.sql` file to set up the initial database structure.

4. Copy the `config.sample.php` file to `config.php` and open it in a text editor. Set the required configuration items, such as database connection details and other settings specific to your environment.

5. To improve performance, uncomment the `OB_SENDFILE_HEADER` line appropriate for your web server in `config.php`. Ensure your web server and site configuration supports this (see Nginx example below).

```
// define('OB_SENDFILE_HEADER', 'X-Sendfile'); // set appropriate SENDFILE header based on server (apache)
// define('OB_SENDFILE_HEADER', 'X-Accel-Redirect'); // set appropriate SENDFILE header based on server (nginx)
// define('OB_SENDFILE_HEADER', 'X-LIGHTTPD-send-file'); // set appropriate SENDFILE header based on server (lighttpd)
```

## Users and Permissions

1. Create a dedicated user for OpenBroadcaster. This user should be the same user the web server PHP process runs as, the background task service runs as, and any manual use of the CLI tool runs as.

2. Ensure the paths specified in `config.php` exist and are writable by this user.


## PHP Configuration

A default PHP installation is generally adequate, though you will likely want to increase these values:

| Name | Value | Description |
| - | - | - |
| memory_limit | 512M | Increase memory allowed per request |
| post_max_size | 1024M | Increase max post size to handle file uploads |

Note that increasing `upload_max_filesize` should not be necessary given how OpenBroadcaster handles file uploads.

Additionally, PHP should be configured to run as the dedicated OpenBroadcaster user. The following is an example PHP pool.d file at `/etc/php/8.3/fpm/pool.d/openbroadcaster.conf`:

```
[openbroadcaster]
user = openbroadcaster
group = openbroadcaster
listen = /run/php/php8.3-openbroadcaster-fpm.sock
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

php_admin_value[memory_limit] = 512M
php_admin_value[post_max_size] = 1024M
```

This assumes the dedicated OpenBroadcaster user is `openbroadcaster`, and that Nginx is running as `www-data`.

## Nginx Configuration

1. Make sure 'mjs' is added as an extension for application/javascript in `/etc/nginx/mime.types`: 

```
application/javascript js mjs;
```

2. Use the following as a starting point for your site configuration, updating values as necessary:
   
```
server {
  listen 80;
  server_name openbroadcaster.example.com;

  root /home/openbroadcaster/www;
  index index.php;

  location / {
    try_files $uri $uri/ =404;
  }

  # disallow access to files and directories starting with a period
  location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
  }

  # php file handling
  location ~ \.php$ {
    include snippets/fastcgi-php.conf;
    fastcgi_pass unix:/run/php/php8.3-openbroadcaster-fpm.sock;
    fastcgi_buffer_size 32k;
    fastcgi_buffers 8 32k;
    fastcgi_busy_buffers_size 64k;
  }

  # rewrite for API URLs
  location ^~ /api/ {
    try_files $uri $uri/ /api.php$is_args$args;
  }

  # allow access to files via X-Accel-Redirect
  location /home/openbroadcaster/files/ {
    internal;
    alias /home/openbroadcaster/files/;
  }

  # allow larger data posts and file uploads
  # this should have the same value as PHP's post_max_size
  client_max_body_size 1024M;
}
```

## Verifying Install and Running Updates

1. Run the following command to validate your configuration file. Correct any errors displayed in red.

```
tools/cli/ob check
```

2. Run the following command to install database updates. This may take a few minutes to complete.

```
tools/cli/ob updates run all
```

3. Set the password for the default admin user by running the following command. Enter a secure password when prompted.

```
tools/cli/ob passwd admin
```

4. Set up a service (or similar) to run required background tasks such as generating thumbnails and cache management. This service should ensure that `tools/cli/ob cron monitor` is running continuously.


## Service Configuration

The last step is to ensure `tools/cli/ob cron monitor` is running continuously in the background. To do this, you may set up a service, `/etc/systemd/system/ob.service`, as follows. Be sure to update the `ExecStart` path and `User` as necessary.

```
[Unit]
Description=OB Background Tasks
After=network.target

[Service]
Type=simple
User=openbroadcaster
ExecStart=/path/to/ob/tools/cli/ob cron monitor
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
```

Then enable and start the service (as root or with sudo):

```
systemctl daemon-reload
systemctl enable ob
systemctl start ob
```
