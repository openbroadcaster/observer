# Changelog

:small_blue_diamond: Feature\
:small_orange_diamond: Enhancement or Code Improvement\
:small_red_triangle: Bug Fix

## Release v5.3.6

### Improvements

:small_orange_diamond: account for possible reverse proxy usage on player IP reporting\
:small_orange_diamond: automatically select problematic playlist item when there's an error (i.e. when unable to save due to private media)\
:small_orange_diamond: improved playlist details items display with icons\
:small_orange_diamond: automatically save newly recorded media automatically when saving voicetrack properties\
:small_orange_diamond: refresh sidebar search on media recording save\
:small_orange_diamond: media recorder UI improvements\
:small_orange_diamond: add proper autocomplete tag to search fields

### Fixes
:small_red_triangle: automatically close playlist search settings when clicking playlist search fields\
:small_red_triangle: UI bug fixes related to showing/hiding elements based on permissions, etc.\
:small_red_triangle: remove voicetracks from media when media deleted\
:small_red_triangle: show voicetracks in "where used" information for media (media details and media delete)\
:small_red_triangle: fixes to player sync code related to playlog status and priority broadcasts

## Release v5.3.5

### Improvements
:small_orange_diamond: add missing icon on dayparting edit buttons\
:small_orange_diamond: make playlist last track fadeout "auto" by default

### Fixes
:small_red_triangle: fix settings menu not closing automatically when clicking search form\
:small_red_triangle: fix js error on new player window\
:small_red_triangle: fix missing ID3 button on media upload\
:small_red_triangle: fix permissions issue affecting non-admin users\
:small_red_triangle: fix breakpoint button display issue on liveassist playlist\
:small_red_triangle: remove some debug code\
:small_red_triangle: fix cron run issue (when not run from expected directory)\
:small_red_triangle: fix already defined PHP warning related to web server sendfile header setting\

## Release v5.3.4

### Features
:small_blue_diamond: voicetrack support in playlists including media recorder\
:small_blue_diamond: cli cron monitor support (continous background process rather than intermittant cron)\
:small_blue_diamond: cli cron support for individual task\
:small_blue_diamond: cli support to install and uninstall modules\
:small_blue_diamond: new coordinates custom metadata type\
:small_blue_diamond: new license custom metadata type\
:small_blue_diamond: visibility setting on custom metadata (to allow excluding from public API)\
:small_blue_diamond: PDF document media type support\
:small_blue_diamond: rotate thumbnail feature\
:small_blue_diamond: improved preview player experience via HLS streaming (on-demand for audio, video requires existing transcode)\
:small_blue_diamond: CLI passwd non-interactive mode

### Improvements
:small_orange_diamond: create proper media versions and download API endpoints\
:small_orange_diamond: create proper thumbnail API endpoint\
:small_orange_diamond: create proper media preview API endpoint\
:small_orange_diamond: remove deprecated download.php, thumbnail.php, and preview.php\
:small_orange_diamond: sendfile header support for downloads to lower server resource use\
:small_orange_diamond: additional misc improvements throughout\
:small_orange_diamond: ongoing API improvements and implementation\
:small_orange_diamond: misc ui code improvements and begin refactoring to single file components\
:small_orange_diamond: add button icons throughout\
:small_orange_diamond: improved media and playlist search/filter forms\
:small_orange_diamond: improved automatic thumbnail generation\
:small_orange_diamond: better caching in UI code (performance fixes)\
:small_orange_diamond: prevent duplicates in dynamic show generation (where possible)\
:small_orange_diamond: properly report max file size on "file too large" error\
:small_orange_diamond: cron system performance and code improvements\
:small_orange_diamond: module system cli and uninstall/install improvements\
:small_orange_diamond: upgrade from FontAwesome 5 to 6\
:small_orange_diamond: many other small improvements throughout

### Fixes
:small_red_triangle: fix preview element playlist position with left sidebar\
:small_red_triangle: apache http server compatibility fixes\
:small_red_triangle: update file fix for mysql\
:small_red_triangle: many small bug fixes throughout

## Release v5.3.3
 
### Improvements
:small_orange_diamond: change UI for removing multi-select items to "X" rather than strikethrough\
:small_orange_diamond: force modal to always be over sidebar (including expanded sidebar)\
:small_orange_diamond: don't autocomplete password fields for new passwords

### Fixes
:small_red_triangle: fix dynamic selection playlist item sometimes not saving when editing playlist\
:small_red_triangle: prevent erroneous error reporting in UI (on non-error conditions)\
:small_red_triangle: fix thumbnails not properly loading in sidebar (related to bug in SVG image resize code)\
:small_red_triangle: fix estimated duration for dynamic selections when using custom metadata\
:small_red_triangle: fix overflowing text in select field

## 5.3.2

:small_blue_diamond: new language field now using ISO-639-3 languages and showing most used languages at the top\
:small_blue_diamond: add support for module update files\
:small_blue_diamond: improved OB CLI tool output and added functionality (list and run updates, run cron, change user password)\
:small_blue_diamond: config file setting for custom media verify command\
:small_orange_diamond: improved manual installation instructions\
:small_orange_diamond: switch back from libav-tools to ffmpeg by default\
:small_orange_diamond: removed "results per page" setting (no longer used)\
:small_orange_diamond: support for automatic routes generation from controller code (@route)\
:small_orange_diamond: support for using API v2 from client-side javascript code\
:small_orange_diamond: begin switchover to single-file-components for UI code\
:small_orange_diamond: other code cleanup and refactoring\
:small_red_triangle: removed outdated installer\
:small_red_triangle: fixes necessary for newer PHP versions (8.1+)\
:small_red_triangle: numerous other fixes and quality of life improvements

## 5.3.1

:small_blue_diamond: API v2 using more modern RESTful implementation (alpha)\
:small_blue_diamond: begin building automated testing via CodeCeption (alpha)\
:small_blue_diamond: OB CLI tool (alpha) with "check install" function\
:small_orange_diamond: include and configure PHP Code Sniffer (phpcs) / PHP Code Beautifier (phpcbf)\
:small_orange_diamond: code refactoring for near-PSR12 adherence (a few issues remain to be resolved later)\
:small_orange_diamond: code and database refactoring for naming consistency\
:small_orange_diamond: include document generator in core code (tools directory)\
:small_orange_diamond: update document generator to also define controller/method routes for API v2\
:small_orange_diamond: begin migration to composer and npm to better manage dependencies\
:small_orange_diamond: rename "emergency" to "alert" to better reflect feature usage\
:small_orange_diamond: revise generalized storage method for UI settings\
:small_orange_diamond: remove obsolete apitest tool\
:small_orange_diamond: remove some PHP code maintaining database integrity and rely on MySQL foriegn key constaints instead\
:small_orange_diamond: improved look/feel for documentation\
:small_red_triangle: fix bug related to show deletion \
:small_red_triangle: fix bug related to media "where used" information

## 5.3.0

:small_blue_diamond: update welcome screen design\
:small_blue_diamond: when update(s) required, display notice and prevent login\
:small_orange_diamond: update PHPMailer dependency to latest version\
:small_orange_diamond: begin using composer for dependencies\
:small_orange_diamond: style code tweak (remove old css --prefix, no longer required)\
:small_orange_diamond: update "themeupdate" tool to use latest dart-sass version

## 5.2.0

:small_red_triangle: ob.installer.sh small tweaks and fixes

## 5.1.1 and earlier

See [https://openbroadcaster.com/resource/change-log/](https://openbroadcaster.com/resource/change-log/)
