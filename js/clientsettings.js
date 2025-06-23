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

// client settings
OB.ClientSettings = new Object();

OB.ClientSettings.init = function () {
    OB.Callbacks.add("ready", -4, OB.ClientSettings.initMenu);
};

OB.ClientSettings.initMenu = function () {
    //T Client Settings
    OB.UI.addSubMenuItem(
        "admin",
        "Client Settings",
        "client_settings",
        OB.ClientSettings.settings,
        10,
        "manage_global_client_storage",
    );
};

// get the media format settings.
OB.ClientSettings.settings = function () {
    OB.UI.replaceMain("client_settings/settings.html");

    OB.API.post("clientsettings", "get_login_message", {}, function (response) {
        if (response.status) {
            $("#client_settings_login_message").val(response.data);
        }
    });

    OB.API.post("clientsettings", "get_welcome_page", {}, function (response) {
        if (response.status) {
            $("#client_settings_welcome_page").val(response.data);
        }
    });
};

OB.ClientSettings.save = function () {
    var success = true;

    var post_lm = {
        client_login_message: $("#client_settings_login_message").val(),
    };
    OB.API.post("clientsettings", "set_login_message", post_lm, function (response) {
        if (!response.status) {
            success = false;
            $("#client_settings_message").obWidget("error", response.msg);
        }
    });

    var post_wp = {
        client_welcome_page: $("#client_settings_welcome_page").val(),
    };
    OB.API.post("clientsettings", "set_welcome_page", post_wp, function (response) {
        if (!response.status) {
            success = false;
            $("#client_settings_message").obWidget("error", response.msg);
        }
    });

    if (success) {
        //T Successfully saved client settings.
        $("#client_settings_message").obWidget("success", "Successfully saved client settings.");
    }
};
