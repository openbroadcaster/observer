// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Admin = new Object();

OB.Admin.init = function () {
    OB.Callbacks.add("ready", -5, OB.Admin.initMenu);
};

OB.Admin.initMenu = function () {
    //T Admin
    OB.UI.addMenuItem("Admin", "admin", 50);
};
