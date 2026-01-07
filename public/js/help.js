// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Help = new Object();

OB.Help.init = function () {
    OB.Callbacks.add("ready", -5, OB.Help.initMenu);
};

OB.Help.initMenu = function () {
    //T help
    OB.UI.addMenuItem("Help", "help", 100);
    //T Documentation
    OB.UI.addSubMenuItem("help", "Documentation", "documentation", OB.Help.documentation, 10);
    //T Updates
    OB.UI.addSubMenuItem("help", "Updates", "updates", OB.Help.update, 15);
};

OB.Help.documentation = function () {
    window.open("https://support.openbroadcaster.com/observer/");
};

OB.Help.update = function () {
    window.open("http://support.openbroadcaster.com/observer-updates");
};
