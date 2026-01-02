# Changelog

:small_blue_diamond: Feature\
:small_orange_diamond: Enhancement or Code Improvement\
:small_red_triangle: Bug Fix

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
