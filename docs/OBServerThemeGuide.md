Theme Guide

OpenBroadcaster has theme support, to allow a custom colour scheme throught menus including a branded transparent logo in background. To switch themes. Select Theme from User Account. To add custom Theme, copy and modify from Themes directory in server. Save. Refresh browser to load\view new theme.

Directory Structure

/themes : Main theme directory. Contains themes. /themes/THEME_NAME : Contains the theme 'THEME_NAME'.

Directories under THEME_NAME:

THEME_NAME/css_core : Overrides core CSS files. See files in /css. Create a file in THEME_NAME/css_core with the same filename, and it will be used instead.

THEME_NAME/css_theme : Any css files here will be included after the core CSS files.

THEME_NAME/html : HTML overrides. See files in /html. Create a file in THEME_NAME/html with the same filename, and it will be used instead.

THEME_NAME/images : Any images (to be used used with HTML/CSS). Use absolute path name when referring to images (in CSS or HTML).
