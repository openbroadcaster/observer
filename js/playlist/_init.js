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
