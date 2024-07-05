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

// live assist uses mostly functions in addedit_standard.js.  extra stuff for live assist only is added here.

// add a breakpoint (live assist playlists)
OB.Playlist.addeditInsertBreakpoint = function () {
    OB.Playlist.addedit_item_last_id += 1;

    //T Breakpoint
    $("#playlist_items").append(
        '<div class="playlist_addedit_item" id="playlist_addedit_item_' +
            OB.Playlist.addedit_item_last_id +
            '"><span class="playlist_addedit_thumbnail"></span><i class="playlist_addedit_description">' +
            htmlspecialchars(OB.t("Breakpoint")) +
            "</i></div>",
    );

    $("#playlist_addedit_item_" + OB.Playlist.addedit_item_last_id).attr("data-type", "breakpoint");
    $("#playlist_addedit_item_" + OB.Playlist.addedit_item_last_id).attr("data-duration", "0");
    eval(
        "$('#playlist_addedit_item_'+OB.Playlist.addedit_item_last_id).dblclick(function() { OB.Playlist.addeditItemProperties(" +
            OB.Playlist.addedit_item_last_id +
            ",'breakpoint'); });",
    );

    // item select
    $("#playlist_addedit_item_" + OB.Playlist.addedit_item_last_id).click(OB.Playlist.addeditItemSelect);

    // hide our 'drag items here' help.
    $("#playlist_items_drag_help").hide();

    $("#playlist_items").sortable({
        start: OB.Playlist.addeditSortStart,
        stop: OB.Playlist.addeditSortStop,
    });
};

OB.Playlist.addedit_liveassist_item_last_id = 0;
OB.Playlist.addeditInsertLiveassistItem = function (item) {
    OB.Playlist.addedit_liveassist_item_last_id += 1;

    var description = htmlspecialchars(item.name + (item.description ? " - " + item.description : ""));

    $("#playlist_liveassist_items").append(
        '<div class="playlist_addedit_liveassist_item" id="playlist_addedit_liveassist_item_' +
            OB.Playlist.addedit_liveassist_item_last_id +
            '" data-id="' +
            item.id +
            '">' +
            description +
            "</div>",
    );

    // item select
    $("#playlist_addedit_liveassist_item_" + OB.Playlist.addedit_liveassist_item_last_id).click(
        OB.Playlist.addeditItemSelect,
    );

    // hide our 'drag items here' help.
    $("#playlist_liveassist_drag_help").hide();

    // make our list sortable
    $("#playlist_liveassist_items").sortable({
        start: OB.Playlist.addeditSortStart,
        stop: OB.Playlist.addeditSortStop,
    });
};

OB.Playlist.liveassistButtonItems = function () {
    var items = new Array();

    $("#playlist_liveassist_items")
        .children()
        .not("#playlist_liveassist_drag_help")
        .each(function (index, element) {
            items.push($(element).attr("data-id"));
        });

    return items;
};

OB.Playlist.liveassistRemoveAll = function () {
    //T Clear all Live Assist buttons?
    if (
        $("#playlist_edit_standard_container .playlist_addedit_liveassist_item").length &&
        confirm(OB.t("Clear all Live Assist buttons?"))
    ) {
        $("#playlist_edit_standard_container .playlist_addedit_liveassist_item").remove();
        $("#playlist_liveassist_drag_help").show();
    }
};
