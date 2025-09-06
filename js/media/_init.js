// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Media = new Object();

OB.Media.init = function () {
    OB.Callbacks.add("ready", -5, OB.Media.initMenu);
    OB.Callbacks.add("ready", -4, OB.Media.initMenu2);
};

OB.Media.initMenu = function () {
    OB.UI.addMenuItem("Media", "media", 20);
    OB.UI.addSubMenuItem("media", "Upload Media", "upload", OB.Media.uploadPage, 10, "create_own_media");
};

OB.Media.initMenu2 = function () {
    OB.UI.addSubMenuItem("admin", "Media Settings", "media_settings", OB.Media.settings, 40, "manage_media_settings");
};
