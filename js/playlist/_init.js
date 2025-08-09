// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Playlist = new Object();

OB.Playlist.init = function () {
    $("body").keydown(OB.Playlist.advancedKeypress);
    $("body").click(OB.Playlist.advancedItemUnselect);

    $("body").keydown(OB.Playlist.addeditKeypress);
    $("body").click(OB.Playlist.addeditItemUnselect);
    OB.Callbacks.add("ready", -5, OB.Playlist.initMenu);
};

OB.Playlist.initMenu = function () {
    OB.UI.addMenuItem("Playlists", "playlists", 30);
    OB.UI.addSubMenuItem("playlists", "New Playlist", "new", OB.Playlist.newPage, 10, "create_own_playlists");
};

OB.Playlist.station_id_avg_duration = null;

OB.Playlist.artistTitleString = function (artist, title) {
    const itemArtistTitle = [];
    if (artist) {
        itemArtistTitle.push(htmlspecialchars(artist));
    }
    if (title) {
        itemArtistTitle.push(htmlspecialchars(title));
    }
    return itemArtistTitle.join(" - ");
};
