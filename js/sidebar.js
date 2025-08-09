// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Sidebar = new Object();

OB.Sidebar.init = function () {
    OB.Callbacks.add("ready", -5, OB.Sidebar.sidebarInit);
};

OB.Sidebar.mediaSearchQuery = "";
OB.Sidebar.playlistSearchQuery = "";

OB.Sidebar.sidebarInit = function () {
    if (parseInt(OB.Account.userdata.sidebar_display_left)) {
        $("body").addClass("sidebar-left");
    } else {
        $("body").addClass("sidebar-right");
    }

    OB.Sidebar.mediaDetails();

    OB.Sidebar.playlistEditDeleteVisibility();
    OB.Sidebar.mediaSearchFilter("approved");
    OB.Sidebar.playlistSearch();

    OB.Sidebar.mediaSearchForm = document.querySelector("ob-element-search-media");
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-view-changed", function (e) {
        if (e.detail.view == "list") {
            OB.Sidebar.mediaDetails();
        } else if (e.detail.view == "grid") {
            OB.Sidebar.mediaThumbnails();
        }
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-status-changed", function (e) {
        OB.Sidebar.mediaSearchFilter(e.detail.status);
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-my-changed", function (e) {
        OB.Sidebar.mediaSearchFilter("my");
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-filters-clicked", function () {
        OB.Sidebar.advancedSearchWindow();
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-mode-changed", function (e) {
        OB.Sidebar.mediaSearchFilter(e.detail.mode);
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-history-clicked", function (e) {
        console.log(e);
        OB.Sidebar.mySearchesWindow();
    });
    OB.Sidebar.mediaSearchForm.addEventListener("ob-search-media-query-changed", function (e) {
        OB.Sidebar.mediaSearchQuery = e.detail.query;
        OB.Sidebar.mediaSearch();
    });

    OB.Sidebar.playlistSearchForm = document.querySelector("ob-element-search-playlist");
    OB.Sidebar.playlistSearchForm.addEventListener("ob-search-playlist-query-changed", function (e) {
        OB.Sidebar.playlistSearchQuery = e.detail.query;
        OB.Sidebar.playlistSearch();
    });
    OB.Sidebar.playlistSearchForm.addEventListener("ob-search-playlist-my-changed", function (e) {
        OB.Sidebar.playlistSearchFilter("my");
    });
};

OB.Sidebar.playerToggle = function () {
    $("#sidebar_player").toggleClass("closed");
    OB.UI.sidebarSearchResultsHeight();
};

OB.Sidebar.showMediaSearch = function () {
    $("#sidebar_search_playlist_container").hide();
    $("#sidebar_search_media_container").showFlex();

    $(".sidebar_search_tab").removeClass("selected");
    $("#sidebar_search_tab_media").addClass("selected");

    OB.Layout.tableFixedHeaders($("#sidebar_search_media_headings"), $("#sidebar_search_media_results"));
};

OB.Sidebar.showPlaylistSearch = function () {
    // if we are showing detailed media view, then close back to basic view before switching to playlist view.
    if ($("#sidebar_search").hasClass("sidebar_search_detailed")) OB.Sidebar.mediaDetailedToggle();

    $("#sidebar_search_media_container").hide();
    $("#sidebar_search_playlist_container").showFlex();

    $(".sidebar_search_tab").removeClass("selected");
    $("#sidebar_search_tab_playlist").addClass("selected");

    OB.Layout.tableFixedHeaders($("#sidebar_search_playlist_headings"), $("#sidebar_search_playlist_results"));
};

OB.Sidebar.mediaDetailedToggle = function () {
    if ($("#sidebar_search").hasClass("sidebar_search_detailed")) {
        $("body").removeClass("sidebar-expanded");
        $("#sidebar_search").removeClass("sidebar_search_detailed");
        $("#sidebar_search_media_container").addClass("sidebar_search_media_container_basic");
        $("#sidebar_search_media_container").removeClass("sidebar_search_media_container_detailed");

        // hide detailed columns
        $(".sidebar_search_media_detailed_column").hide();

        // update detailed toggle link
        $("#media_detailed_toggle_text").text(OB.t("more"));
    } else {
        $("body").addClass("sidebar-expanded");
        $("#sidebar_search").addClass("sidebar_search_detailed");
        $("#sidebar_search_media_container").removeClass("sidebar_search_media_container_basic");
        $("#sidebar_search_media_container").addClass("sidebar_search_media_container_detailed");

        // show detailed columns
        $(".sidebar_search_media_detailed_column").each(function (index, column) {
            var name = $(column).attr("data-column");
            if (name == "genre") name = "category";
            if (
                OB.Settings.media_required_fields[name] != "disabled" &&
                OB.Settings.media_required_fields[name + "_id"] != "disabled"
            )
                $(column).show();
        });

        // update detailed toggle link
        $("#media_detailed_toggle_text").text(OB.t("less"));
    }

    $("#sidebar_search_media_headings").width($("#sidebar_search_media_results").width());

    OB.Layout.tableFixedHeaders($("#sidebar_search_media_headings"), $("#sidebar_search_media_results"));
};

OB.Sidebar.media_search_filters = new Object();
OB.Sidebar.media_search_filters.mode = "approved";
OB.Sidebar.media_search_filters.my = false;
OB.Sidebar.media_search_filters.bookmarked = false;

OB.Sidebar.mediaSearchFilter = function (what) {
    // group one (can select only one of these)
    if (what == "approved" || what == "unapproved" || what == "archived") {
        OB.Sidebar.media_search_filters.mode = what;
    }

    // group two (toggle on and off).
    else {
        if (what == "my") OB.Sidebar.media_search_filters.my = !OB.Sidebar.media_search_filters.my;
        else if (what == "bookmarked")
            OB.Sidebar.media_search_filters.bookmarked = !OB.Sidebar.media_search_filters.bookmarked;
    }

    // set the look of the buttons
    //T ap
    if (OB.Sidebar.media_search_filters.mode == "approved")
        $("#sidebar_search_media_approved").html(OB.t("ap").toUpperCase());
    else $("#sidebar_search_media_approved").html(OB.t("ap").toLowerCase());

    //T un
    if (OB.Sidebar.media_search_filters.mode == "unapproved")
        $("#sidebar_search_media_unapproved").html(OB.t("un").toUpperCase());
    else $("#sidebar_search_media_unapproved").html(OB.t("un").toLowerCase());

    //T ar
    if (OB.Sidebar.media_search_filters.mode == "archived")
        $("#sidebar_search_media_archived").html(OB.t("ar").toUpperCase());
    else $("#sidebar_search_media_archived").html(OB.t("ar").toLowerCase());

    //T my
    if (OB.Sidebar.media_search_filters.my == true) $("#sidebar_search_media_my").html(OB.t("my").toUpperCase());
    else $("#sidebar_search_media_my").html(OB.t("my").toLowerCase());

    //T bk
    if (OB.Sidebar.media_search_filters.bookmarked == true) $("#sidebar_search_media_bookmarked").html("BK");
    else $("#sidebar_search_media_bookmarked").html("bk");

    // reload search
    OB.Sidebar.mediaSearch();
};

OB.Sidebar.mediaSelectAll = function () {
    $(".sidebar_search_media_result").addClass("sidebar_search_media_selected");
};

OB.Sidebar.mediaSelectNone = function () {
    $(".sidebar_search_media_result").removeClass("sidebar_search_media_selected");
};

OB.Sidebar.media_last_selected = null;

OB.Sidebar.mediaSelect = function (object, dragging, keypress) {
    if (keypress == null && !dragging) $(".sidebar_search_media_selected").removeClass("sidebar_search_media_selected");
    else if (keypress == null && dragging && !$(object).hasClass("sidebar_search_media_selected"))
        $(".sidebar_search_media_selected").removeClass("sidebar_search_media_selected");

    var media_id = $(object).attr("data-id");

    if (
        !dragging &&
        keypress != "shift" &&
        $("#sidebar_search_media_result_" + media_id).hasClass("sidebar_search_media_selected")
    )
        $("#sidebar_search_media_result_" + media_id).removeClass("sidebar_search_media_selected");
    else {
        if (keypress == "shift") {
            var last_selected = $("#sidebar_search_media_result_" + OB.Sidebar.media_last_selected);

            // figure out if we have to move up or down.
            if (
                $(last_selected).parent().children().index($(last_selected)) <
                $("#sidebar_search_media_result_" + media_id)
                    .parent()
                    .children()
                    .index($("#sidebar_search_media_result_" + media_id))
            )
                var shift_down = true;
            else var shift_down = false;

            while (last_selected.attr("data-id") != media_id) {
                if (shift_down) last_selected = $(last_selected).next();
                else last_selected = $(last_selected).prev();

                last_selected.addClass("sidebar_search_media_selected");
            }
        } else $("#sidebar_search_media_result_" + media_id).addClass("sidebar_search_media_selected");

        OB.Sidebar.media_last_selected = media_id;
    }

    OB.Sidebar.mediaEditDeleteVisibility();
};

// adjust the visibility of the media edit / delete buttons.
OB.Sidebar.mediaEditDeleteVisibility = function () {
    // default to true.
    var visible = true;

    // if there is nothing selected, then we can't edit or delete.
    if ($("#sidebar_search_media_results tbody .sidebar_search_media_selected").length == 0) visible = false;

    // see if we can edit/delete each item.
    $("#sidebar_search_media_results tbody .sidebar_search_media_selected").each(function (index, element) {
        if ($(element).attr("data-can_edit") != "true") {
            visible = false;
            return false;
        }
    });

    if (visible) {
        $("#sidebar_media_edit_button").attr("hidden", false);
        $("#sidebar_media_delete_button").attr("hidden", false);
        if (OB.Sidebar.media_search_filters.mode == "archived")
            $("#sidebar_media_unarchive_button").attr("hidden", false);
    } else {
        $("#sidebar_media_edit_button").attr("hidden", true);
        $("#sidebar_media_delete_button").attr("hidden", true);
        $("#sidebar_media_unarchive_button").attr("hidden", true);
    }
};

OB.Sidebar.mediaSearchNumResults = function () {
    var num_results = $("#sidebar_search_media_results").attr("data-num_results");

    // special case for no results.
    if (num_results == 0) {
        $("#sidebar_search_media_results tbody").html('<td class="sidebar_search_noresults"></td>');
        //T No Media Found
        $("#sidebar_search_media_results tbody .sidebar_search_noresults").text(OB.t("No Media Found"));
    }

    //T 1 result
    if (num_results == 1) var num_results_text = OB.t("1 result");
    //T %1 results
    else var num_results_text = OB.t("%1 results", format_number(num_results));

    $("#sidebar_search_media_footer .results .num_results").html(num_results_text);
    $("#sidebar_search_media_footer .results .dynamic_selection").html("&nbsp;");
};

OB.Sidebar.media_search_offset = 0;

OB.Sidebar.mediaSearchMore = function () {
    var results_per_page = 100;
    OB.Sidebar.media_search_offset += results_per_page;

    OB.Sidebar.mediaSearch(true);
};

OB.Sidebar.media_search_sort_by = "updated";
OB.Sidebar.media_search_sort_dir = "desc";

OB.Sidebar.mediaSearchSort = function (sortby) {
    // change direction if already sorting by this column
    if (sortby == OB.Sidebar.media_search_sort_by) {
        if (OB.Sidebar.media_search_sort_dir == "asc") OB.Sidebar.media_search_sort_dir = "desc";
        else OB.Sidebar.media_search_sort_dir = "asc";
    }

    OB.Sidebar.media_search_sort_by = sortby;

    OB.Sidebar.mediaSearch();
};

// we keep track of the last simple/advanced search so people can toggle between.
OB.Sidebar.media_search_last_simple = false;
OB.Sidebar.media_search_last_advanced = false;

OB.Sidebar.media_search_last_query = false;

OB.Sidebar.mediaSearchMode = function (mode) {
    if (OB.Sidebar.media_search_last_query) {
        if (mode == "simple") {
            $("#sidebar_search_media_input").unbind("click");
            $("#sidebar_search_media_input").focus();
            //T enter search query
            $("#sidebar_search_media_input").attr("placeholder", "(" + OB.t("enter search query") + ")");

            if (OB.Sidebar.media_search_last_query.mode == "advanced" && OB.Sidebar.media_search_last_simple)
                $("#sidebar_search_media_input").val(OB.Sidebar.media_search_last_simple.string);
            OB.Sidebar.advanced_search_filters = null;
            OB.Sidebar.mediaSearch();
        }

        if (mode == "advanced") {
            OB.Sidebar.advancedSearchWindow();
        }
    }
};

OB.Sidebar.mediaDetails = function () {
    $("#sidebar_search_media_container").removeClass("thumbnails").addClass("details");
};

OB.Sidebar.mediaThumbnails = function () {
    $("#sidebar_search_media_container").removeClass("details").addClass("thumbnails");
};

OB.Sidebar.mediaSearch = function (more) {
    // if not the result of pagination (new search), reset offset to 0
    if (!more) {
        OB.Sidebar.media_search_offset = 0;
        $(".context-menu-media-item:first-child").parent().remove();
    }

    // generate our search query
    var search_query = Object();

    if (OB.Sidebar.advanced_search_filters == null) {
        search_query.mode = "simple";
        search_query.string = OB.Sidebar.mediaSearchQuery; // $("#sidebar_search_media_input").val();
    } else {
        search_query.mode = "advanced";
        search_query.filters = OB.Sidebar.advanced_search_filters;
        $("#sidebar_search_media_input").val("");
        //T advanced mode
        $("#sidebar_search_media_input").attr("placeholder", "(" + OB.t("advanced mode") + ")");
        $("#sidebar_search_media_input").click(function (e) {
            OB.Sidebar.mediaSearchMode("advanced");
        });
    }

    $("#sidebar_search_media_loading").show();
    $("#sidebar_search_media_loadmore").hide();

    var results_per_page = 100;
    OB.API.post(
        "media",
        "search",
        {
            save_history: true,
            sort_by: OB.Sidebar.media_search_sort_by,
            sort_dir: OB.Sidebar.media_search_sort_dir,
            q: search_query,
            s: OB.Sidebar.media_search_filters.mode,
            l: results_per_page,
            o: OB.Sidebar.media_search_offset,
            my: OB.Sidebar.media_search_filters.my,
        },
        function (data) {
            var media_class = media; // media singleton is needed, but media local variable below overrides.

            var media = data.data.media;
            var num_results = data.data.num_results;

            // handle results
            if (!more) $("#sidebar_search_media_results tbody").html("");

            if (data.status != false)
                for (var i in media) {
                    var duration = media[i]["duration"];
                    if (duration == null) duration = "";
                    else duration = secsToTime(duration);

                    if (media[i]["is_archived"] == 1) var data_mode = "media_archived";
                    else if (media[i]["is_approved"] == 0) var data_data = "media_unapproved";
                    else data_mode = "media";

                    var media_type_symbol = "";
                    switch (media[i]["type"]) {
                        case "audio":
                            media_type_symbol = '<i class="fas fa-music"></i>';
                            break;
                        case "video":
                            media_type_symbol = '<i class="fas fa-video"></i>';
                            break;
                        case "image":
                            media_type_symbol = '<i class="fas fa-image"></i>';
                            break;
                        case "document":
                            media_type_symbol = '<i style="padding-left: 1px;" class="fas fa-file-alt"></i>';
                            break;
                    }

                    var thumbnail = "";

                    if (media[i]["thumbnail"]) {
                        thumbnail = '<ob-element-thumbnail data-id="' + media[i]["id"] + '"></ob-element-thumbnail>';
                    }

                    $("#sidebar_search_media_results tbody").append(
                        '\
        <tr class="sidebar_search_media_result" id="sidebar_search_media_result_' +
                            media[i]["id"] +
                            '" data-mode="' +
                            data_mode +
                            '">\
          <td class="sidebar_search_media_thumbnail hidden" data-column="thumbnail">' +
                            thumbnail +
                            '</td>\
          <td class="sidebar_search_media_type" data-column="type">' +
                            media_type_symbol +
                            '</td>\
          <td class="sidebar_search_media_artist" data-column="artist">' +
                            htmlspecialchars(media[i]["artist"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column hidden" data-column="album">' +
                            htmlspecialchars(media[i]["album"]) +
                            '</td>\
          <td class="sidebar_search_media_title" data-column="title">' +
                            htmlspecialchars(media[i]["title"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column sidebar_search_media_year hidden" data-column="year">' +
                            htmlspecialchars(media[i]["year"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column hidden" data-column="category">' +
                            htmlspecialchars(media[i]["category_name"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column hidden" data-column="genre">' +
                            htmlspecialchars(media[i]["genre_name"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column hidden" data-column="country">' +
                            htmlspecialchars(media[i]["country_name"]) +
                            '</td>\
          <td class="sidebar_search_media_detailed_column hidden" data-column="language">' +
                            htmlspecialchars(media[i]["language_name"]) +
                            '</td>\
          <td class="sidebar_search_media_time" data-column="time">' +
                            duration +
                            "</td>\
        </tr>",
                    );

                    if (OB.Settings.media_required_fields.artist == "disabled")
                        $(".sidebar_search_media_artist").hide();

                    $("#sidebar_search_media_result_" + media[i]["id"]).click(function (e) {
                        var keypress = null;
                        if (e.shiftKey) keypress = "shift";
                        else if (e.ctrlKey) keypress = "ctrl";

                        OB.Sidebar.mediaSelect(this, false, keypress);
                    });

                    // change null object to blank string when required
                    if (media[i]["year"] === null) media[i]["year"] = "";

                    if (media[i]["country"] === null) media[i]["country"] = "";
                    if (media[i]["country_name"] === null) media[i]["country_name"] = "";

                    if (media[i]["language"] === null) media[i]["language"] = "";
                    if (media[i]["language_name"] === null) media[i]["language_name"] = "";

                    // some additional attributes to set
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-duration", media[i]["duration"]);

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-id", media[i]["id"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-artist", media[i]["artist"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-title", media[i]["title"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-year", media[i]["year"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-album", media[i]["album"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-type", media[i]["type"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-filename", media[i]["filename"]);

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-category_id",
                        media[i]["category_id"],
                    );
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-category_name",
                        media[i]["category_name"],
                    );

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-country_id", media[i]["country"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-country_name",
                        media[i]["country_name"],
                    );

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-language_id", media[i]["language"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-language_name",
                        media[i]["language_name"],
                    );

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-genre_id", media[i]["genre_id"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-genre_name", media[i]["genre_name"]);

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-comments", media[i]["comments"]);

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-is_copyright_owner",
                        media[i]["is_copyright_owner"],
                    );
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-visibility", media[i]["status"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-dynamic_select",
                        media[i]["dynamic_select"],
                    );

                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-owner_id", media[i]["owner_id"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-can_edit", media[i]["can_edit"]);
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-stream",
                        JSON.stringify(media[i]["stream"]),
                    );
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-captions",
                        JSON.stringify(media[i]["captions"]),
                    );
                    $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                        "data-thumbnail",
                        JSON.stringify(media[i]["thumbnail"]),
                    );

                    if (media[i]["is_archived"] == 1)
                        $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-status", "archived");
                    else if (media[i]["is_approved"] == 0)
                        $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-status", "unapproved");
                    else $("#sidebar_search_media_result_" + media[i]["id"]).attr("data-status", "approved");

                    // set custom metadata
                    $.each(OB.Settings.media_metadata, function (index, metadata) {
                        if (metadata.type == "hidden") return;
                        $("#sidebar_search_media_result_" + media[i]["id"]).attr(
                            "data-metadata_" + metadata.name,
                            media[i]["metadata_" + metadata.name],
                        );
                    });

                    // remove our hidden class from detailed columns if we're in an expanded (detailed) view.
                    if ($("#sidebar_search").hasClass("sidebar_search_detailed"))
                        $(".sidebar_search_media_detailed_column").show();

                    // set up context menu
                    var menuOptions = new Object();

                    //T Details
                    menuOptions[OB.t("Details")] = {
                        klass: "context-menu-media-item",
                        click: function (element) {
                            OB.Sidebar.contextMenuDetailsPage($(element).attr("data-id"));
                        },
                    };
                    if (media[i]["can_edit"] == "true")
                        menuOptions[OB.t("Edit")] = {
                            klass: "context-menu-media-item",
                            click: function (element) {
                                OB.Sidebar.contextMenuEditPage();
                            },
                        };
                    //T Download
                    if (OB.Settings.permissions.indexOf("download_media") != -1)
                        menuOptions[OB.t("Download")] = {
                            klass: "context-menu-media-item",
                            click: function (element) {
                                OB.Sidebar.contextMenuDownload($(element).attr("data-id"));
                            },
                        };
                    //T Versions
                    if (
                        media[i]["can_edit"] == "true" &&
                        OB.Settings.permissions.indexOf("manage_media_versions") != -1
                    )
                        menuOptions[OB.t("Versions")] = {
                            klass: "context-menu-media-item",
                            click: function (element) {
                                OB.Sidebar.contextMenuVersionPage(
                                    $(element).attr("data-id"),
                                    $(element).attr("data-title"),
                                );
                            },
                        };

                    if (Object.keys(menuOptions).length > 0) {
                        $("#sidebar_search_media_result_" + media[i]["id"]).contextMenu(
                            "context-menu-media-" + media[i]["id"],
                            menuOptions,
                            {
                                disable_native_context_menu: false,
                                showMenu: function (element) {
                                    $(element).click();
                                },
                                hideMenu: function () {},
                                leftClick: false, // trigger on left click instead of right click
                            },
                        );
                    }

                    // double click loads detail
                    $("#sidebar_search_media_result_" + media[i]["id"]).dblclick(function () {
                        OB.Sidebar.contextMenuDetailsPage($(this).attr("data-id"));
                    });
                }

            if (data.status == false) $("#sidebar_search_media_results").attr("data-num_results", 0);
            else $("#sidebar_search_media_results").attr("data-num_results", num_results);

            OB.Sidebar.mediaSearchNumResults();
            OB.Sidebar.media_search_last_query = search_query;

            if (search_query.mode == "simple") OB.Sidebar.media_search_last_simple = search_query;
            else OB.Sidebar.media_search_last_advanced = search_query;

            if (data.status != false && media.length > 0) {
                // old jquery draggable event
                $(".sidebar_search_media_result")
                    .not(".ui-draggable")
                    .draggable({
                        helper: "clone",
                        start: function (event, ui) {
                            var keypress = null;
                            if (event.shiftKey) keypress = "shift";
                            else if (event.ctrlKey) keypress = "ctrl";

                            // select the media we're dragging also.
                            OB.Sidebar.mediaSelect($(ui.helper), true, keypress);

                            // but we don't actually want the helper to count as part of this.
                            $(ui.helper).removeClass("sidebar_search_media_selected");

                            var num_selected = $(".sidebar_search_media_selected").size();

                            if (num_selected == 1)
                                var helper_html =
                                    htmlspecialchars($(ui.helper).attr("data-artist")) +
                                    "<br>" +
                                    htmlspecialchars($(ui.helper).attr("data-title"));
                            else var helper_html = htmlspecialchars(num_selected + " items");

                            $(ui.helper).html("");
                            OB.UI.dragHelperOn(helper_html, $(".sidebar_search_media_selected"));

                            var data_status = $(ui.helper).attr("data-status");

                            if (data_status == "approved")
                                $(".droppable_target_media").addClass("droppable_target_highlighted");
                            else if (data_status == "unapproved")
                                $(".droppable_target_media_unapproved").addClass("droppable_target_highlighted");
                            else if (data_status == "archived")
                                $(".droppable_target_media_unapproved").addClass("droppable_target_highlighted");

                            // dispatch dragstart to ob-field-media and ob-element-preview elements, since jquery overrides draggable
                            document.querySelectorAll("ob-field-media, ob-element-preview").forEach((element) => {
                                element.dispatchEvent(new Event("dragstart"));
                            });
                        },

                        stop: function (event, ui) {
                            OB.UI.dragHelperOff();

                            var data_status = $(ui.helper).attr("data-status");

                            if (data_status == "approved")
                                $(".droppable_target_media").removeClass("droppable_target_highlighted");
                            else if (data_status == "unapproved")
                                $(".droppable_target_media_unapproved").removeClass("droppable_target_highlighted");
                            else if (data_status == "archived")
                                $(".droppable_target_media_unapproved").removeClass("droppable_target_highlighted");

                            // dispatch dragend to ob-field-media and ob-element-preview elements, since jquery overrides draggable
                            document.querySelectorAll("ob-field-media, ob-element-preview").forEach((element) => {
                                element.dispatchEvent(new Event("dragend"));
                            });
                        },
                    });
                // new draggable attribute
                document.querySelectorAll(".sidebar_search_media_result").forEach((element) => {
                    element.setAttribute("draggable", "true");
                });

                OB.Layout.tableFixedHeaders($("#sidebar_search_media_headings"), $("#sidebar_search_media_results"));
            }

            OB.Sidebar.mediaEditDeleteVisibility();

            // show/hide loadmore as necessary, hide loading
            $("#sidebar_search_media_loadmore").toggle($(".sidebar_search_media_result").length < num_results);
            $("#sidebar_search_media_loading").hide();
        },
    );
};

OB.Sidebar.contextMenuEditPage = function () {
    OB.Media.editPage();
};

OB.Sidebar.contextMenuDownload = function (id) {
    OB.Media.download(id);
};

OB.Sidebar.contextMenuDetailsPage = function (id) {
    OB.MediaDetails.page(id);
};

OB.Sidebar.contextMenuVersionPage = function (id, title) {
    OB.Media.versionPage(id, title);
};

OB.Sidebar.contextMenuPlaylistEditPage = function () {
    OB.Playlist.editPage();
};

OB.Sidebar.contextMenuPlaylistDetailsPage = function (id) {
    OB.Playlist.detailsPage(id);
};

OB.Sidebar.playlist_search_filters = new Object();
OB.Sidebar.playlist_search_filters.my = false;
OB.Sidebar.playlist_search_filters.bookmarked = false;

OB.Sidebar.playlistSearchFilter = function (what) {
    // toggle on off
    if (what == "my") OB.Sidebar.playlist_search_filters.my = !OB.Sidebar.playlist_search_filters.my;

    //T my
    if (OB.Sidebar.playlist_search_filters.my == true) $("#sidebar_search_playlist_my").text(OB.t("my").toUpperCase());
    else $("#sidebar_search_playlist_my").text(OB.t("my").toLowerCase());

    // reload search
    OB.Sidebar.playlistSearch();
};

OB.Sidebar.playlistSelectAll = function () {
    $(".sidebar_search_playlist_result").addClass("sidebar_search_playlist_selected");
};

OB.Sidebar.playlistSelectNone = function () {
    $(".sidebar_search_playlist_result").removeClass("sidebar_search_playlist_selected");
};

OB.Sidebar.playlist_last_selected = null;

OB.Sidebar.playlistSelect = function (object, dragging, keypress) {
    if (keypress == null && !dragging)
        $(".sidebar_search_playlist_selected").removeClass("sidebar_search_playlist_selected");
    else if (keypress == null && dragging && !$(object).hasClass("sidebar_search_playlist_selected"))
        $(".sidebar_search_playlist_selected").removeClass("sidebar_search_playlist_selected");

    var media_id = $(object).attr("data-id");

    if (
        !dragging &&
        keypress != "shift" &&
        $("#sidebar_search_playlist_result_" + media_id).hasClass("sidebar_search_playlist_selected")
    )
        $("#sidebar_search_playlist_result_" + media_id).removeClass("sidebar_search_playlist_selected");
    else {
        if (keypress == "shift") {
            var last_selected = $("#sidebar_search_playlist_result_" + OB.Sidebar.playlist_last_selected);

            // figure out if we have to move up or down.
            if (
                $(last_selected).parent().children().index($(last_selected)) <
                $("#sidebar_search_playlist_result_" + media_id)
                    .parent()
                    .children()
                    .index($("#sidebar_search_playlist_result_" + media_id))
            )
                var shift_down = true;
            else var shift_down = false;

            while (last_selected.attr("data-id") != media_id) {
                if (shift_down) last_selected = $(last_selected).next();
                else last_selected = $(last_selected).prev();

                last_selected.addClass("sidebar_search_playlist_selected");
            }
        } else $("#sidebar_search_playlist_result_" + media_id).addClass("sidebar_search_playlist_selected");

        OB.Sidebar.playlist_last_selected = media_id;
    }

    OB.Sidebar.playlistEditDeleteVisibility();
};

// adjust the visibility of the playlist edit / delete buttons.
OB.Sidebar.playlistEditDeleteVisibility = function () {
    // default to true.
    var visible = true;

    // if there is nothing selected, then we can't edit or delete.
    if ($("#sidebar_search_playlist_results tbody .sidebar_search_playlist_selected").length == 0) visible = false;

    // check to make sure we can edit all selected items
    $("#sidebar_search_playlist_results tbody .sidebar_search_playlist_selected").each(function (index, element) {
        if ($(element).attr("data-can_edit") != "true") {
            visible = false;
        }
    });

    if (visible) {
        $("#sidebar_playlist_edit_button").attr("hidden", false);
        $("#sidebar_playlist_delete_button").attr("hidden", false);
    } else {
        $("#sidebar_playlist_edit_button").attr("hidden", true);
        $("#sidebar_playlist_delete_button").attr("hidden", true);
    }
};

OB.Sidebar.playlist_search_offset = 0;

OB.Sidebar.playlistSearchMore = function () {
    var results_per_page = 100;
    OB.Sidebar.playlist_search_offset += results_per_page;

    OB.Sidebar.playlistSearch(true);
};

OB.Sidebar.playlist_search_sort_by = "updated";
OB.Sidebar.playlist_search_sort_dir = "desc";

OB.Sidebar.playlistSearchSort = function (sortby) {
    // change direction if already sorting by this column
    if (sortby == OB.Sidebar.playlist_search_sort_by) {
        if (OB.Sidebar.playlist_search_sort_dir == "asc") OB.Sidebar.playlist_search_sort_dir = "desc";
        else OB.Sidebar.playlist_search_sort_dir = "asc";
    }

    OB.Sidebar.playlist_search_sort_by = sortby;

    OB.Sidebar.playlistSearch();
};

OB.Sidebar.playlistSearch = function (more) {
    // if not the result of pagination (new search), reset offset to 0
    if (!more) {
        OB.Sidebar.playlist_search_offset = 0;
        $(".context-menu-playlist-item:first-child").parent().remove();
    }

    $("#sidebar_search_playlist_headings").show();

    $("#sidebar_search_playlist_loading").show();
    $("#sidebar_search_playlist_loadmore").hide();

    var results_per_page = 100;

    OB.API.post(
        "playlists",
        "search",
        {
            sort_by: OB.Sidebar.playlist_search_sort_by,
            sort_dir: OB.Sidebar.playlist_search_sort_dir,
            q: OB.Sidebar.playlistSearchQuery, // $("#sidebar_search_playlist_input").val(),
            l: results_per_page,
            o: OB.Sidebar.playlist_search_offset,
            my: OB.Sidebar.playlist_search_filters.my,
        },
        function (data) {
            var playlist = data.data.playlists;
            var num_results = data.data.num_results;

            // handle results
            $("#sidebar_search_playlist_results").attr("data-num_results", num_results);

            if (!more) $("#sidebar_search_playlist_results tbody").html("");

            if (num_results == 0) {
                $("#sidebar_search_playlist_results tbody").html(
                    '<tr><td colspan="3" class="sidebar_search_noresults"></td></tr>',
                );
                //T No Playlists Found
                $("#sidebar_search_playlist_results tbody .sidebar_search_noresults").text(OB.t("No Playlists Found"));
            }

            //T 1 result
            //T %1 results
            if (num_results == 1) num_results_text = OB.t("1 result");
            else num_results_text = OB.t("%1 results", format_number(num_results));
            $("#sidebar_search_playlist_footer .results .num_results").html(num_results_text);

            for (var i in playlist) {
                var duration = playlist[i]["duration"];
                if (duration == null) duration = "";
                else duration = secsToTime(duration);

                var playlist_description = playlist[i]["description"];
                if (playlist_description.length > 150)
                    playlist_description = playlist_description.substr(0, 150) + "...";

                $("#sidebar_search_playlist_results tbody").append(
                    '\
        <tr class="sidebar_search_playlist_result" id="sidebar_search_playlist_result_' +
                        playlist[i]["id"] +
                        '" data-mode="playlist">\
          <td class="sidebar_search_playlist_name" data-column="name">' +
                        htmlspecialchars(playlist[i]["name"]) +
                        '</td>\
          <td class="sidebar_search_playlist_description" data-column="description">' +
                        playlist_description +
                        "</td>\
        </tr>",
                );

                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).click(function (e) {
                    var keypress = null;
                    if (e.shiftKey) keypress = "shift";
                    else if (e.ctrlKey) keypress = "ctrl";

                    OB.Sidebar.playlistSelect(this, false, keypress);
                });

                // some additional attributes to set
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr("data-id", playlist[i]["id"]);
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr("data-name", playlist[i]["name"]);
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr(
                    "data-description",
                    playlist[i]["description"],
                );
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr(
                    "data-visibility",
                    playlist[i]["status"],
                );
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr(
                    "data-owner_id",
                    playlist[i]["owner_id"],
                );
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).attr(
                    "data-can_edit",
                    playlist[i]["can_edit"],
                );

                // set up context menu
                var menuOptions = new Object();

                //T Details
                menuOptions[OB.t("Details")] = {
                    klass: "context-menu-playlist-item",
                    click: function (element) {
                        OB.Sidebar.contextMenuPlaylistDetailsPage($(element).attr("data-id"));
                    },
                };
                //T Edit
                if (playlist[i]["can_edit"])
                    menuOptions[OB.t("Edit")] = {
                        klass: "context-menu-playlist-item",
                        click: function (element) {
                            OB.Sidebar.contextMenuPlaylistEditPage();
                        },
                    };

                if (Object.keys(menuOptions).length > 0) {
                    $("#sidebar_search_playlist_result_" + playlist[i]["id"]).contextMenu(
                        "context-menu-playlist-" + playlist[i]["id"],
                        menuOptions,
                        {
                            disable_native_context_menu: false,
                            showMenu: function (element) {
                                $(element).click();
                            },
                            hideMenu: function () {},
                            leftClick: false, // trigger on left click instead of right click
                        },
                    );
                }

                // double click loads details page
                $("#sidebar_search_playlist_result_" + playlist[i]["id"]).dblclick(function () {
                    OB.Sidebar.contextMenuPlaylistDetailsPage($(this).attr("data-id"));
                });
            }

            if (playlist.length > 0) {
                $(".sidebar_search_playlist_result")
                    .not(".ui-draggable")
                    .draggable({
                        helper: "clone",
                        start: function (event, ui) {
                            var keypress = null;
                            if (event.shiftKey) keypress = "shift";
                            else if (event.ctrlKey) keypress = "ctrl";

                            // select the media we're dragging also.
                            OB.Sidebar.playlistSelect($(ui.helper), true, keypress);

                            // but we don't actually want the helper to count as part of this.
                            $(ui.helper).removeClass("sidebar_search_playlist_selected");

                            var num_selected = $(".sidebar_search_playlist_selected").size();

                            if (num_selected == 1) var helper_html = htmlspecialchars($(ui.helper).attr("data-name"));
                            else var helper_html = htmlspecialchars(num_selected + " items");

                            $(ui.helper).html("");
                            OB.UI.dragHelperOn(helper_html, $(".sidebar_search_playlist_selected"));

                            $(".droppable_target_playlist").addClass("droppable_target_highlighted");

                            // dispatch dragstart to ob-field-playlist elements, since jquery overrides draggable
                            document.querySelectorAll("ob-field-playlist, ob-element-preview").forEach((element) => {
                                element.dispatchEvent(new Event("dragstart"));
                            });
                        },

                        stop: function (event, ui) {
                            $(".droppable_target_playlist").removeClass("droppable_target_highlighted");
                            OB.UI.dragHelperOff();

                            // dispatch dragend to ob-field-playlist elements, since jquery overrides draggable
                            document.querySelectorAll("ob-field-playlist, ob-element-preview").forEach((element) => {
                                element.dispatchEvent(new Event("dragend"));
                            });
                        },
                    });
                // new draggable attribute
                document.querySelectorAll(".sidebar_search_playlist_result").forEach((element) => {
                    element.setAttribute("draggable", "true");
                });

                OB.Layout.tableFixedHeaders(
                    $("#sidebar_search_playlist_headings"),
                    $("#sidebar_search_playlist_results"),
                );
            }

            OB.Sidebar.playlistEditDeleteVisibility();

            // show/hide loadmore as necessary, hide loading
            $("#sidebar_search_playlist_loadmore").toggle($(".sidebar_search_playlist_result").length < num_results);
            $("#sidebar_search_playlist_loading").hide();
        },
    );
};

OB.Sidebar.mySearchesContextMenuOn = function (e, type, id) {
    $("#my_searches_item_" + id).addClass("context_menu_on");

    $("#my_searches_" + type + "_context_menu")
        .css("left", e.pageX)
        .css("top", e.pageY);

    // set default or unset default?
    if (type == "saved") {
        if ($("#my_searches_item_" + id + " .media_search_item_default_text").length) {
            $("#my_searches_context_menu_unset_default").show();
            $("#my_searches_context_menu_set_default").hide();
        } else {
            $("#my_searches_context_menu_set_default").show();
            $("#my_searches_context_menu_unset_default").hide();
        }
    }

    $("#my_searches_" + type + "_context_menu").show();
    $(window).bind("mousedown", OB.Sidebar.mySearchesContextMenuOff);
};

OB.Sidebar.mySearchesContextMenuOff = function (e) {
    var $target = $(e.target);
    if ($target.hasClass("context_menu_item")) $target.click();

    $("#my_searches_history_context_menu").hide();
    $("#my_searches_saved_context_menu").hide();

    $(".my_searches_item").removeClass("context_menu_on");

    $(window).unbind("mousedown", OB.Sidebar.mySearchesContextMenuOff);
};

OB.Sidebar.mySearchesSave = function () {
    if (!$(".my_searches_item.context_menu_on").length) return;

    var id = $(".my_searches_item.context_menu_on").attr("data-id");

    OB.API.post("media", "media_my_searches_save", { id: id }, function (response) {
        if (response.status == true) {
            $(".my_searches_item[data-id=" + id + "]").prependTo("#my_searches_saved");
            OB.Sidebar.mySearchesItemContextMenu($(".my_searches_item[data-id=" + id + "]"), "saved");
            OB.Sidebar.mySearchesNosearchtext();
        }
        //T An error occurred while trying to save this search item.
        else OB.UI.alert("An error occurred while trying to save this search item.");
    });
};

OB.Sidebar.mySearchesDelete = function () {
    if (!$(".my_searches_item.context_menu_on").length) return;

    var id = $(".my_searches_item.context_menu_on").attr("data-id");

    OB.API.post("media", "media_my_searches_delete", { id: id }, function (response) {
        if (response.status == true) {
            $(".my_searches_item[data-id=" + id + "]").remove();
            OB.Sidebar.mySearchesNosearchtext();
        }
        //T An error occurred while trying to delete this search item.
        else OB.UI.alert("An error occurred while trying to delete this search item.");
    });
};

OB.Sidebar.mySearchesNosearchtext = function () {
    if ($("#my_searches_history .my_searches_item").length == 0) $("#my_searches_history_nosearches").show();
    else $("#my_searches_history_nosearches").hide();

    if ($("#my_searches_saved .my_searches_item").length == 0) $("#my_searches_saved_nosearches").show();
    else $("#my_searches_saved_nosearches").hide();
};

OB.Sidebar.mySearchesSearch = function (id) {
    OB.Sidebar.advanced_search_filters = $("#my_searches_item_" + id).data("filters");
    OB.Sidebar.mediaSearch();
    OB.UI.closeModalWindow();
};

OB.Sidebar.mySearchesWindow = function () {
    OB.API.post("media", "media_my_searches", {}, function (response) {
        OB.UI.openModalWindow("sidebar/my_searches.html");

        var history = response.data.history;
        var saved = response.data.saved;

        if (history && history.length > 0) {
            $.each(history, function (index, data) {
                OB.Sidebar.mySearchesWindowAddItem(data, "history");
            });
        }

        if (saved && saved.length > 0) {
            $.each(saved, function (index, data) {
                OB.Sidebar.mySearchesWindowAddItem(data, "saved");
            });
        }

        OB.Sidebar.mySearchesNosearchtext();
    });
};

OB.Sidebar.mySearchesWindowAddItem = function (data, type) {
    if (data.query.mode != "advanced") return; // this shouldn't be.

    $("#my_searches_" + type).append(
        '<div class="my_searches_item" data-id="' + data.id + '" id="my_searches_item_' + data.id + '"></div>',
    );

    $("#my_searches_item_" + data.id).click(function () {
        OB.Sidebar.mySearchesSearch(data.id);
    });
    $("#my_searches_item_" + data.id).data("filters", data.query.filters);
    $("#my_searches_item_" + data.id).data("description", data.description);

    OB.Sidebar.mySearchesItemContextMenu($("#my_searches_item_" + data.id), type);

    //T All simple searches will include these filters by default.
    if (data.default == "1")
        $("#my_searches_item_" + data.id).append(
            '<div class="media_search_item_default_text">' +
                OB.t("All simple searches will include these filters by default.") +
                "</div>",
        );

    if (type == "saved" && data.description != "") {
        $("#my_searches_item_" + data.id).append("<div><i>" + nl2br(htmlspecialchars(data.description)) + "</i></div>");
    } else
        $.each(data.query.filters, function (filter_index, filter) {
            $("#my_searches_item_" + data.id).append("<div>" + htmlspecialchars(filter.description) + "</div>");
        });
};

OB.Sidebar.mySearchesItemContextMenu = function ($element, type) {
    $element.unbind("contextmenu").bind("contextmenu", function (e) {
        OB.Sidebar.mySearchesContextMenuOn(e, type, $element.attr("data-id"));
        return false;
    });
};

OB.Sidebar.mySearchesMakeDefault = function () {
    if (!$(".my_searches_item.context_menu_on").length) return;

    var id = $(".my_searches_item.context_menu_on").attr("data-id");

    OB.API.post("media", "media_my_searches_default", { id: id }, function (response) {
        if (response.status == true) {
            $(".media_search_item_default_text").remove();
            //T All simple searches will include these filters by default.
            $(".my_searches_item[data-id=" + id + "]").prepend(
                '<div class="media_search_item_default_text">' +
                    OB.t("All simple searches will include these filters by default.") +
                    "</div>",
            );
        }
        //T An error occurred while trying to make this search the default.
        else OB.UI.alert("An error occurred while trying to make this search the default.");
    });
};

OB.Sidebar.mySearchesUnsetDefault = function () {
    OB.API.post("media", "media_my_searches_unset_default", {}, function (response) {
        if (response.status == true) {
            $(".media_search_item_default_text").remove();
        }
        //T An error occurred while trying to unset the default search.
        else OB.UI.alert("An error occurred while trying to unset the default search.");
    });
};

OB.Sidebar.mySearchesEditWindow = function () {
    if (!$(".my_searches_item.context_menu_on").length) return;

    var id = $(".my_searches_item.context_menu_on").attr("data-id");
    var filters = $("#my_searches_item_" + id).data("filters");
    var description = $("#my_searches_item_" + id).data("description");

    OB.Sidebar.advancedSearchWindowInit();

    // switch to 'edit my search item' mode.
    $("#layout_modal_window .advanced_search_item").hide();
    $("#layout_modal_window .edit_my_search_item").show();

    $("#layout_modal_window #edit_my_search_item_save").attr("data-id", id);
    $("#edit_my_search_item_description").val(description);

    $.each(filters, function (index, filter_data) {
        OB.Sidebar.advancedSearchAdd(filter_data);
    });
};

OB.Sidebar.mySearchesEdit = function () {
    postfields = {};
    postfields.id = $("#layout_modal_window #edit_my_search_item_save").attr("data-id");
    postfields.filters = OB.Sidebar.advancedSearchGetFilters();
    postfields.description = $("#edit_my_search_item_description").val();

    if (postfields.filters.length < 1) {
        //T Adding at least one search filter is required.
        $("#advanced_search_message").obWidget("error", "Adding at least one search filter is required.");
        return;
    }

    OB.API.post("media", "media_my_searches_edit", postfields, function (response) {
        if (!response.status) {
            $("#advanced_search_message").obWidget("error", response.msg);
            return;
        }

        OB.Sidebar.mySearchesWindow();
    });
};

OB.Sidebar.advancedSearchWindowInit = function () {
    // refresh settings and then load window
    OB.Settings.getSettings(function () {
        OB.UI.openModalWindow("sidebar/advanced_search.html");

        $.each(OB.Settings.categories, function (index, category) {
            $("#advanced_search_category_options").append(
                '<option value="' + category.id + '">' + htmlspecialchars(category.name) + "</option>",
            );
        });

        $.each(OB.Settings.countries, function (index, country) {
            $("#advanced_search_country_options").append(
                '<option value="' + country.country_id + '">' + htmlspecialchars(country.name) + "</option>",
            );
        });

        $.each(OB.Settings.genres, function (index, genre) {
            $("#advanced_search_genre_options").append(
                '<option value="' + genre.id + '">' + htmlspecialchars(genre.name) + "</option>",
            );
        });

        $.each(OB.Settings.languages, function (index, language) {
            $("#advanced_search_language_options").append(
                '<option value="' + language.language_id + '">' + htmlspecialchars(language.ref_name) + "</option>",
            );
        });

        $.each(OB.Settings.media_metadata, function (index, metadata) {
            // skip hidden metadata and media/playlist currently unsupported for advanced search
            if (metadata.type == "hidden" || metadata.type == "media" || metadata.type == "playlist") return;

            // access static operators property for this element
            const metadataType = metadata.type == "integer" ? "number" : metadata.type;

            var $metadata = $("<option></option>")
                .text(metadata.description)
                .attr("value", "metadata_" + metadata.name)
                .attr("data-type", metadataType);

            /*
            if (metadata.type == "text" || metadata.type == "textarea")
                $metadata.attr("data-compare", "text").attr("data-value", "text");
            else if (metadata.type == "integer") $metadata.attr("data-compare", "number").attr("data-value", "text");
            else if (metadata.type == "bool") $metadata.attr("data-compare", "select").attr("data-value", "bool");
            else if (metadata.type == "tags") {
                $metadata.attr("data-compare", "tags").attr("data-value", "metadata_" + metadata.name);
                var $select = $("<select></select>")
                    .attr("data-type", "value")
                    .attr("data-name", "metadata_" + metadata.name)
                    .addClass("hidden");
                $.each(metadata.settings.all, function (index, option) {
                    $select.append($("<option></option>").text(option));
                });
                $("#advanced_search_bool_options").after($select);
            } else if (metadata.type == "select") {
                $metadata.attr("data-compare", "select").attr("data-value", "metadata_" + metadata.name);
                var $select = $("<select></select>")
                    .attr("data-type", "value")
                    .attr("data-name", "metadata_" + metadata.name)
                    .addClass("hidden");
                $.each(metadata.settings.options, function (index, option) {
                    $select.append($("<option></option>").text(option));
                });
                $("#advanced_search_bool_options").after($select);
            }
            */

            $("#advanced_search_filter").append($metadata);
        });

        $("#advanced_search_value").focus();
    });
};

OB.Sidebar.advancedSearchWindow = function () {
    OB.Sidebar.advancedSearchWindowInit();

    if (OB.Sidebar.media_search_last_advanced) {
        $.each(OB.Sidebar.media_search_last_advanced.filters, function (index, filter_data) {
            OB.Sidebar.advancedSearchAdd(filter_data);
        });
    } else {
        OB.API.post("media", "media_my_searches", {}, function (response) {
            if (response.data.saved) {
                $.each(response.data.saved, function (index, filter) {
                    if (filter.default == "1") {
                        $.each(filter.query.filters, function (index, filter_data) {
                            OB.Sidebar.advancedSearchAdd(filter_data);
                        });
                    }
                });
            }
        });
    }
};

OB.Sidebar.advancedSearchFilterChange = function () {
    $(".advanced_search [data-type=compare]").hide();
    $(".advanced_search [data-type=value]").hide();

    const metadataContainer = document.getElementById("advanced_search_metadata");
    metadataContainer.style.display = "none";

    var $filter = $("#advanced_search_filter").find(":selected");
    var compare = $filter.attr("data-compare"); // which compare field to use
    var value = $filter.attr("data-value"); // which value field to use
    var type = $filter.attr("data-type");

    if (type) {
        metadataContainer.innerHTML = "";
        const metadataElement = document.createElement("ob-field-" + type);
        const metadataOperators = metadataElement?.constructor?.comparisonOperators;

        const operatorElement = document.createElement("select");
        operatorElement.setAttribute("id", "advanced_search_metadata_operator");
        Object.entries(metadataOperators).forEach((operator) => {
            const option = document.createElement("option");
            option.value = operator[0];
            option.text = operator[1];
            operatorElement.appendChild(option);
        });

        metadataContainer.appendChild(operatorElement);

        // field specifies a different comparison field
        if (metadataElement?.constructor?.comparisonField) {
            const comparisonField = document.createElement("ob-field-" + metadataElement.constructor.comparisonField);
            comparisonField.editable = true;
            comparisonField.setAttribute("id", "advanced_search_metadata_value");
            metadataContainer.appendChild(comparisonField);
        }

        // field uses itself as the comparison field
        else {
            metadataElement.editable = true;
            metadataElement.setAttribute("id", "advanced_search_metadata_value");
            metadataContainer.appendChild(metadataElement);
        }

        metadataContainer.style.display = "inline-flex";
    } else {
        $(".advanced_search [data-type=compare][data-name=" + compare + "]").show();
        $(".advanced_search [data-type=value][data-name=" + value + "]").show();
    }
};

OB.Sidebar.advanced_search_filter_id = 0;
OB.Sidebar.language_list = [];

OB.Sidebar.advancedSearchAdd = function (filter_data) {
    if (filter_data) {
        OB.Sidebar.advanced_search_filter_id++;
        var filter_description = filter_data.description;
        var op = filter_data.op;
        var val = filter_data.val;
        var filter = filter_data.filter;
    } else if (document.getElementById("advanced_search_metadata").style.display != "none") {
        var filter = document.getElementById("advanced_search_filter").value;
        filter = filter.replace(/^metadata_/, "");
        var op = document.getElementById("advanced_search_metadata_operator").value;
        var val = document.getElementById("advanced_search_metadata_value").value;

        // get metadata/settings for this field
        const metadata = OB.Settings.media_metadata.find((metadata) => metadata.name === filter);
        const type = metadata.type;

        // get the operator name from the index
        const operators = document.createElement(`ob-field-${type}`).constructor.comparisonOperators;

        // create metadata element
        const metadataElement = document.createElement(`ob-field-${type}`);
        var filter_description = `${metadata.description} ${operators[op]} "${val}"`;

        // server needs prefix for metadata fields
        filter = "metadata_" + filter;
    } else {
        var $filter = $("#advanced_search_filter option:selected");
        var filter = $("#advanced_search_filter").val();
        var filter_name = $filter.text();

        var compare_field = $filter.attr("data-compare");
        var value_field = $filter.attr("data-value");

        var $op = $(".advanced_search [data-type=compare][data-name=" + compare_field + "] option:selected");
        var op = $op.val();
        var op_name = $op.text();

        var $val = $(".advanced_search [data-type=value][data-name=" + value_field + "]");
        var val = $val.val();
        if ($val.prop("nodeName") == "SELECT") var val_name = $val.find("option:selected").text();
        else if ($val.prop("nodeName") == "OB-FIELD-LANGUAGE") var val_name = $val[0].currentLanguageName();
        else if ($val.prop("nodeName") == "OB-FIELD-COUNTRY") var val_name = $val[0].currentCountryName();

        // some basic validation
        if ((filter == "artist" || filter == "album" || filter == "title") && val == "") {
            $("#advanced_search_message").obWidget("error", filter_name + " text required.");
            return false;
        }

        if ((filter == "year" || filter == "duration") && val.match(/^[0-9]+$/) === null) {
            $("#advanced_search_message").obWidget("error", ["A valid %1 is required", filter_name.toLowerCase()]);
            return false;
        }

        $("#advanced_search_no_criteria").hide();
        OB.Sidebar.advanced_search_filter_id++;

        var filter_description = filter_name;

        if (compare_field == "select") filter_description += " " + op_name + " " + val_name;
        else if (compare_field == "number") filter_description += " " + op_name + " " + val;
        //T seconds
        else if (compare_field == "duration") filter_description += " " + op_name + " " + val + " " + OB.t("seconds");
        else filter_description += " " + op_name + ' "' + val + '"';
    }

    // container for added filter
    const container = document.createElement("div");
    container.id = "advanced_search_filter_" + OB.Sidebar.advanced_search_filter_id;
    container.setAttribute("data-filter_description", filter_description);
    container.setAttribute("data-filter", filter);
    container.setAttribute("data-op", op);
    container.setAttribute("data-val", val);

    // add delete button
    const deleteButton = document.createElement("a");
    deleteButton.href = "javascript: OB.Sidebar.advancedSearchRemove(" + OB.Sidebar.advanced_search_filter_id + ");";
    deleteButton.innerText = "[x]";
    container.appendChild(deleteButton);

    // add description
    const description = document.createElement("span");
    description.innerText = " " + filter_description;
    container.appendChild(description);

    // add the new element to the list
    document.getElementById("advanced_search_criteria_list").prepend(container);

    /*
    $("#advanced_search_criteria_list").prepend(
        '<div id="advanced_search_filter_' +
            OB.Sidebar.advanced_search_filter_id +
            '">' +
            '<a href="javascript: OB.Sidebar.advancedSearchRemove(' +
            OB.Sidebar.advanced_search_filter_id +
            ');">[x]</a> ' +
            htmlspecialchars(filter_description) +
            "</div>",
    );

    var filter_div = $("#advanced_search_filter_" + OB.Sidebar.advanced_search_filter_id);

    $(filter_div).attr("data-filter_description", filter_description);
    $(filter_div).attr("data-filter", filter);
    $(filter_div).attr("data-op", op);
    $(filter_div).attr("data-val", val);
    */
};

OB.Sidebar.advancedSearchRemove = function (id) {
    $("#advanced_search_filter_" + id).remove();
    if ($("#advanced_search_criteria_list").children().length < 1) $("#advanced_search_no_criteria").show();
};

OB.Sidebar.advanced_search_filters = null; // if this is not null, an advanced search will be done by OB.Sidebar.mediaSearch();

// complete our advanced search.
OB.Sidebar.advancedSearch = function () {
    var filters = OB.Sidebar.advancedSearchGetFilters();

    if (filters.length < 1) {
        //T Adding at least one search filter is required.
        $("#advanced_search_message").obWidget("error", "Adding at least one search filter is required.");
        return;
    }

    $("#sidebar_search_media_input").val("");

    OB.Sidebar.advanced_search_filters = filters;
    OB.Sidebar.mediaSearch();
    OB.UI.closeModalWindow();
};

OB.Sidebar.advancedSearchGetFilters = function () {
    var filters = new Array();

    $("#advanced_search_criteria_list")
        .children()
        .each(function (index, data) {
            filters.push({
                filter: $(data).attr("data-filter"),
                description: $(data).attr("data-filter_description"),
                op: $(data).attr("data-op"),
                val: $(data).attr("data-val"),
            });
        });

    return filters;
};
