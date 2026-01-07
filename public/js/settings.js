// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

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
