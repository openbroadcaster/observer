<?php

//
// THE FOLLOWING ARE REQUIRED SETTINGS
//

define('OB_DB_USER', 'dbuser'); // database user
define('OB_DB_PASS', 'dbpass'); // database password
define('OB_DB_HOST', 'localhost'); // database hostname
define('OB_DB_NAME', 'dbname'); // database name

define('OB_HASH_SALT', 'CHANGEMEPLEASE'); // change to random characters for password salt.

// make sure the following directories are writable by the web server.
define('OB_MEDIA', '/where/to/put/media');
define('OB_MEDIA_UPLOADS', '/where/to/put/media/uploads'); // can be subdirectory of OB_MEDIA, but doesn't need to be.
define('OB_MEDIA_ARCHIVE', '/where/to/put/media/archive'); // can be subdirectory of OB_MEDIA, but doesn't need to be.
define('OB_THUMBNAILS', '/where/to/put/thumbnails');
define('OB_CACHE', '/where/to/put/cache/files');

define('OB_SITE', 'http://example.com/'); // where do you access OB?

define('OB_EMAIL_REPLY', 'noreply@example.com'); // emails to users come from this address
define('OB_EMAIL_FROM', 'OpenBroadcaster'); // emails to users come from this name

//
// THE FOLLOWING ARE OPTIONAL SETTINGS
//

// define('OB_SENDFILE_HEADER', 'X-Sendfile'); // set appropriate SENDFILE header based on server (apache)
// define('OB_SENDFILE_HEADER', 'X-Accel-Redirect'); // set appropriate SENDFILE header based on server (nginx)
// define('OB_SENDFILE_HEADER', 'X-LIGHTTPD-send-file'); // set appropriate SENDFILE header based on server (lighttpd)

// define('OB_UPDATES_USER', 'updates'); // username/password for updates area
// define('OB_UPDATES_PW', 'PASSWORD_HASH'); // get password hash with: php -r "echo password_hash('password',PASSWORD_DEFAULT).\"\n\";"

// custom SMTP server (all must be defined)
// define('OB_EMAIL_HOST', 'hostname');
// define('OB_EMAIL_USER', 'username');
// define('OB_EMAIL_PASS', 'password');
// define('OB_EMAIL_TYPE', 'ssl');
// define('OB_EMAIL_PORT', 443);

// show all languages
// define('OB_SHOW_ALL_LANGUAGES', true);

// verify audio/video media validity. skip verify (false), default verify command (true), or custom verify command (string)
// define('OB_MEDIA_VERIFY',true);

// media filesize limit in MB (default 1024)
// define('OB_MEDIA_FILESIZE_LIMIT',1024);

// where to media file versions (default is OB_MEDIA/versions)
// define('OB_MEDIA_VERSIONS','/where/to/put/media/versions');

// set a custom assets directory (required for multisite installations)
// define('OB_ASSETS','/where/to/put/assets');

// disable language/translation demos (translated using Google Translate, will contain many errors)
// define('OB_DISABLE_LANGUAGE_DEMOS',TRUE);

// external database for filetype identification if php-bundled database is not adequate. (see php info).
// define('OB_MAGIC_FILE','');

// set for debugging remote.php (remote.php?devmode=CHANGEME sets $_POST = $_GET and skips player authentication).
// define('OB_REMOTE_DEBUG','CHANGEME');

// uncomment to log slow SQL queries using php error_log().
// define('OB_LOG_SLOW_QUERIES',TRUE);

// set custom audio transcode command (outputting to mp3 or ogg).
// define('OB_TRANSCODE_AUDIO_MP3','transcode {infile} {outfile}');
// define('OB_TRANSCODE_AUDIO_OGG','transcode {infile} {outfile}');

// set custom video transcode command (outputting to mp4 or ogv, with specified output resolution)
// define('OB_TRANSCODE_VIDEO_MP4','transcode {infile} -s {width}x{height} {outfile}');
// define('OB_TRANSCODE_VIDEO_OGV','transcode {infile} -s {width}x{height} {outfile}');

// enable public media browsing and streaming
// define('OB_STREAM_API',true);
// define('OB_STREAM_TRANSCODE_ALL', true); // set to TRUE to transcode all media (not just public)

// import tool settings
// OB_SYNC_USERID = 1; // owner for imported media
// OB_SYNC_SOURCE = '/mnt/media';
// OB_ACOUSTID_KEY = '';

// what checks should be done on every request to ensure proper operation?
// see updates/checker.php method names for available checks
// default false
// define('OB_INIT_VERIFY', ['database_version', 'config_file_valid']);

// Google API key for geocoding fields
// define('OB_GOOGLE_API_KEY', 'YOUR_API_KEY');
