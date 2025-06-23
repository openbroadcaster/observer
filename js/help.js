/*
    Copyright 2012-2024 OpenBroadcaster, Inc.

    This file is part of OpenBroadcaster Server.

    OpenBroadcaster Server is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    OpenBroadcaster Server is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with OpenBroadcaster Server.  If not, see <http://www.gnu.org/licenses/>.
*/

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
