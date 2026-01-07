// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Media.deletePage = function (ids) {
    // no media IDs specified, get IDs from sidebar selection
    if (typeof ids == "undefined") {
        ids = [];
        $(".sidebar_search_media_selected").each(function (index, element) {
            ids.push($(element).attr("data-id"));
        });
    }

    // ids is a single number, make array for consistency
    else if (typeof ids == "number" || typeof ids == "string") {
        ids = [parseInt(ids)];
    }

    // if we get this far, we require ids to be an object/array
    else if (typeof ids != "object") {
        return;
    }

    //T Select at least one media item to delete.
    if (ids.length < 1) {
        OB.UI.alert("Select at least one media item to delete.");
        return;
    }

    var post = [];
    ids.forEach(function (id) {
        post.push(["media", "get", { id: id, where_used: true }]);
    });

    // get 'where media is used' information, load page.
    OB.API.multiPost(post, function (response) {
        // determine status of these items using the first media ID
        var first_media = response[0].data;
        if (first_media.is_archived == 1) var status = "archived";
        else if (first_media.is_approved == 1) var status = "approved";
        else var status = "unapproved";

        // update UI based on status
        OB.UI.replaceMain("media/delete.html");
        $("#media_delete_list").attr("data-status", status);
        //T Media will be moved to archive.
        if (status == "approved") $("#media_second_message").text(OB.t("Media will be moved to archive."));
        //T Media will be permanently deleted.
        else $("#media_second_message").text(OB.t("Media will be permanently deleted."));

        var append_html = "";

        var items = {};
        response.forEach(function (item) {
            if (!item.data.title) return true;
            items[item.data.id] = item.data;
        });

        $.each(items, function (index, media) {
            var used = media.where_used;

            if (used.can_delete) {
                $("#media_delete_list").append(
                    '<li data-id="' + media.id + '">' + htmlspecialchars(media.artist + " - " + media.title) + "</li>",
                );
            } else {
                $("#media_cannot_delete ul").append(
                    '<li data-id="' + media.id + '">' + htmlspecialchars(media.artist + " - " + media.title) + "</li>",
                );
                $("#media_cannot_delete").show();
            }

            if (used.can_delete && used.used.length > 0) {
                append_html = "<ul>";

                const used_playlists = [];

                $.each(used.used, function (where_used_index, where_used) {
                    //T playlist
                    if (where_used.where == "playlist" || where_used.where == "playlist_voicetrack") {
                        // if already in used_playlists, skip
                        if (used_playlists.includes(where_used.id)) return true;

                        // track as already outputted
                        used_playlists.push(where_used.id);

                        // output
                        append_html +=
                            "<li>" +
                            htmlspecialchars(OB.t("Item will be removed from")) +
                            " " +
                            htmlspecialchars(OB.t("playlist")) +
                            ': <a href="javascript: OB.Playlist.detailsPage(' +
                            where_used.id +
                            ');">' +
                            htmlspecialchars(where_used.name) +
                            "</a></li>";
                    } else {
                        //T Item will be removed from
                        append_html +=
                            "<li>" +
                            htmlspecialchars(OB.t("Item will be removed from")) +
                            " " +
                            htmlspecialchars(OB.t(where_used.where)) +
                            " <i>" +
                            htmlspecialchars(where_used.name) +
                            "</i></li>";
                    }
                });

                append_html += "</ul>";

                $("#media_delete_list > li[data-id=" + used.id + "]").append(append_html);
            }
        });
    });
};

OB.Media.delete = function () {
    var status = $("#media_delete_list").attr("data-status");

    var delete_ids = new Array();

    $("#media_delete_list > li").each(function (index, element) {
        delete_ids.push($(element).attr("data-id"));
    });

    if (status == "approved") var delete_method = "archive";
    else var delete_method = "delete";

    OB.API.post("media", delete_method, { id: delete_ids }, function (data) {
        if (data.status == true) {
            OB.Sidebar.mediaSearch();

            $(".media_delete_button").remove();
            $("#media_delete_list").remove();

            //T Media has been deleted.
            $("#media_top_message").text(OB.t("Media has been deleted."));
            $("#media_second_message").remove();
        } else OB.UI.alert(data.msg);
    });
};

OB.Media.unarchivePage = function (ids) {
    // no media IDs specified, get IDs from sidebar selection
    if (typeof ids == "undefined") {
        ids = [];
        $(".sidebar_search_media_selected").each(function (index, element) {
            ids.push($(element).attr("data-id"));
        });
    }

    // ids is a single number, make array for consistency
    else if (typeof ids == "number" || typeof ids == "string") {
        ids = [parseInt(ids)];
    }

    // if we get this far, we require ids to be an object/array
    else if (typeof ids != "object") {
        return;
    }

    //T Select at least one media item to restore.
    if (ids.length < 1) {
        OB.UI.alert("Select at least one media item to restore.");
        return;
    }

    var post = [];
    ids.forEach(function (id) {
        post.push(["media", "get", { id: id }]);
    });

    // get 'where media is used' information, load page.
    OB.API.multiPost(post, function (response) {
        OB.UI.replaceMain("media/unarchive.html");

        $(ids).each(function (index, id) {
            var artist = response[index].data.artist;
            var title = response[index].data.title;

            $("#media_restore_list").append(
                '<li data-id="' + id + '">' + htmlspecialchars(artist + " - " + title) + "</li>",
            );
        });
    });
};

OB.Media.unarchive = function () {
    var restore_ids = new Array();

    $("#media_restore_list > li").each(function (index, element) {
        restore_ids.push($(element).attr("data-id"));
    });

    OB.API.post("media", "unarchive", { id: restore_ids }, function (data) {
        if (data.status == true) {
            OB.Sidebar.mediaSearch();

            $(".media_restore_button").remove();
            $("#media_restore_list").remove();

            //T Media has been restored.
            $("#media_top_message").text(OB.t("Media has been restored."));
            $("#media_second_message").remove();
        } else OB.UI.alert(data.msg);
    });
};
