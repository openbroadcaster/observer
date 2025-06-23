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

/* store settings and data which is used by the UI */

OB.Settings = new Object();

OB.Settings.init = function () {
    OB.Callbacks.add("ready", -70, OB.Settings.getSettings);
    OB.Callbacks.add("ready", -60, OB.Settings.allStoreCache);
};

OB.Settings.categories = new Array();
OB.Settings.countries = new Array();
OB.Settings.languages = new Array();
OB.Settings.genres = new Array();

OB.Settings.permissions = null;
OB.Settings.groups = null;

OB.Settings.storeCache = {};

OB.Settings.getSettings = function (callback) {
    var post = [];
    post.push(["metadata", "country_list", {}]);
    post.push(["metadata", "language_list", {}]);
    post.push(["metadata", "genre_list", {}]);
    post.push(["metadata", "category_list", {}]);
    post.push(["metadata", "media_metadata_fields", {}]);
    post.push(["settings", "get_ob_version", {}]);
    post.push(["metadata", "media_get_fields", {}]);
    post.push(["metadata", "playlist_item_types", {}]);
    post.push(["metadata", "recording_default_values", {}]);

    OB.API.multiPost(
        post,
        function (response) {
            OB.Settings.countries = response[0].data;
            OB.Settings.languages = response[1].data;
            OB.Settings.genres = response[2].data;
            OB.Settings.categories = response[3].data;
            OB.Settings.media_metadata = response[4].data;
            OB.version = response[5].data;
            OB.Settings.media_required_fields = response[6].data;
            OB.Settings.playlist_item_types = response[7].data;
            OB.Settings.recording_metadata = response[8].data;

            if (callback) callback();
        },
        "sync",
    );
};

OB.Settings.allStoreCache = function () {
    OB.API.post("account", "store_all", {}, function (response) {
        OB.Settings.storeCache = response.data;
    });
};

OB.Settings.store = function (name, value) {
    if (typeof value === "undefined") {
        return OB.Settings.storeCache[name];
    }

    OB.Settings.storeCache[name] = value;
    OB.API.post("account", "store", { name: name, value: value }, function (response) {
        // Saved setting.
    });
};
