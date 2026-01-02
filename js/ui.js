// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.UI = new Object();

OB.UI.htmlCache = new Array();

OB.UI.init = function () {
    // watch for changes to DOM and then do things.
    new MutationObserver(function (mutations) {
        OB.UI.domChangeCallback();
    }).observe(document.querySelector("html"), {
        attributes: false,
        childList: true,
        characterData: false,
        subtree: true,
    });

    OB.Callbacks.add("ready", -50, OB.UI.initLayout);

    // add drag helper html
    $("body").append('<div id="drag_helper"></div>');
    $("body").on("mousemove", OB.UI.dragHelperMove);
};

OB.UI.initLayout = function () {
    OB.API.post(
        "ui",
        "html",
        {},
        function (response) {
            OB.UI.htmlCache = response.data;

            $("#main_container").html(OB.UI.getHTML("layout.html"));
            $("#sidebar_player").html(OB.UI.getHTML("sidebar/player.html"));
            $("#sidebar_search").html(OB.UI.getHTML("sidebar/search.html"));
        },
        "sync",
    );

    // we might need to adjust things if window resizes.
    $(window).resize(OB.UI.domChangeCallback);
};

OB.UI.dragHelperOn = function (html, data) {
    $("#drag_helper").html(html).addClass("active");
    window.dragHelperData = data;
};

OB.UI.dragHelperOff = function () {
    $("#drag_helper").removeClass("active");
    $("#drag_helper").css({
        left: "-1000px",
        top: "-1000px",
    });
    window.dragHelperData = null;
};

OB.UI.dragHelperMove = function (e) {
    if (!$("#drag_helper").hasClass("active")) return;

    var left = e.pageX - $("#drag_helper").outerWidth() / 2;
    var top = e.pageY - $("#drag_helper").outerHeight() / 2;

    $("#drag_helper").css({
        left: left + "px",
        top: top + "px",
    });
};

// TODO fix this with better html/css, shouldn't be necessary.
OB.UI.sidebarSearchResultsHeight = function () {
    if ($("#sidebar_search_media_container").is(":visible")) {
        $("#sidebar_search_media_results_container").css("height", 0);
        var mediaResultsHeight = $("#sidebar_search_media_container").height() - 20;
        $("#sidebar_search_media_container > *").each(function () {
            if ($(this).attr("id") != "sidebar_search_media_results_container")
                mediaResultsHeight -= $(this).outerHeight();
        });
        $("#sidebar_search_media_results_container").css("height", Math.max(0, mediaResultsHeight) + "px");
    }

    if ($("#sidebar_search_playlist_container").is(":visible")) {
        $("#sidebar_search_playlist_results_container").css("height", 0);
        var playlistResultsHeight = $("#sidebar_search_playlist_container").height() - 20;
        $("#sidebar_search_playlist_container > *").each(function () {
            if ($(this).attr("id") != "sidebar_search_playlist_results_container")
                playlistResultsHeight -= $(this).outerHeight();
        });
        $("#sidebar_search_playlist_results_container").css("height", Math.max(0, playlistResultsHeight) + "px");
    }
};

OB.UI.domChangeCallback = function () {
    OB.UI.sidebarSearchResultsHeight();

    $("ob-tabs").each(function (index, container) {
        OB.UI.tabsInit(container);
    });

    $("ob-duration-input").each(function (index, input) {
        OB.UI.durationInputInit(input);
    });

    $("ob-datetime-input").each(function (index, input) {
        OB.UI.datetimeInputInit(input);
    });

    $("ob-date-input").each(function (index, input) {
        OB.UI.dateInputInit(input);
    });

    $("ob-time-input").each(function (index, input) {
        OB.UI.timeInputInit(input);
    });

    $("ob-tag-input").each(function (index, tag) {
        OB.UI.tagInputInit(tag);
    });

    $("ob-user-input").each(function (index, input) {
        OB.UI.userInputInit(input);
    });

    $("ob-group-input").each(function (index, input) {
        OB.UI.groupInputInit(input);
    });

    $("ob-html-input").each(function (index, input) {
        OB.UI.htmlInputInit(input);
    });

    $("ob-media-input").each(function (index, input) {
        OB.UI.mediaInputInit(input);
    });

    $("ob-playlist-input").each(function (index, input) {
        OB.UI.playlistInputInit(input);
    });

    OB.UI.translateHTML();
};

OB.UI.tabsInit = function (container) {
    if ($(container).attr("data-ready")) return true;

    var tabs = $(container).find("> ob-tab");
    var $select = $("<ob-tab-select></ob-tab-select>");

    $(tabs).each(function (index, tab) {
        var name = $(tab).attr("data-name");
        if (!name) name = "Untitled";
        $select.append($("<button></button").text(name).attr("data-index", index).click(OB.UI.tabsClick));
        $(tab).attr("data-index", index);
    });
    $(container).prepend($select);
    $(container).find("> ob-tab-select > button").first().click();
    $(container).attr("data-ready", true);
};

OB.UI.tabsClick = function () {
    var $container = $(this).parents("ob-tabs").first();
    $container.find("> ob-tab").hide();
    $container.find("> ob-tab-select > button").removeClass("active");
    $container.find("> ob-tab[data-index=" + $(this).attr("data-index") + "]").show();
    $(this).addClass("active");
};

OB.UI.formVal = function (form, value) {
    if (typeof value == "object") {
        $(form)
            .find("[name], [data-name]")
            .each(function (index, input) {
                var type = ($(input).attr("type") ?? "").toLowerCase();
                var name = $(input).attr("data-name");
                if (!name) name = $(input).attr("name");
                if (name) {
                    // for checkbox, check if found in comma separated list
                    if (type == "checkbox") {
                        var values = (value[name] ?? "").split(",");
                        if (values.indexOf($(input).val()) != -1) $(input).prop("checked", true);
                    }

                    // non-checkbox value set
                    else {
                        $(input).val(value[name]);
                        $(input).trigger("change");
                    }
                }
            });
    } else {
        var values = {};

        $(form)
            .find("[name], [data-name]")
            .each(function (index, input) {
                var type = ($(input).attr("type") ?? "").toLowerCase();

                // skip unchecked checkbox
                if (type == "checkbox" && !$(input).is(":checked")) return;

                // skip hidden item (unless type hidden)
                if (type != "hidden" && !$(input).is(":visible")) return;

                var name = $(input).attr("data-name");
                if (!name) name = $(input).attr("name");
                if (name) {
                    if (typeof values[name] == "undefined") values[name] = $(input).val();
                    // handle multiple values by putting them in an array (i.e., checkboxes)
                    else if (typeof values[name] == "object") values[name].push($(input).val());
                    else values[name] = [values[name], $(input).val()];
                }
            });

        return values;
    }
};

OB.UI.durationInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;

    $(input).html('<input type="text" placeholder="e.g. &quot;90 minutes&quot;" />');
    $(input)
        .find("input")
        .change(function () {
            var duration = null;
            var string = $(this).val().trim();

            // try to parse as hh:mm:ss
            var string_split = string.split(":");
            var digits = /^[0-9]+$/;
            if (string_split.length == 1 && string_split[0].match(digits)) duration = string_split[0] * 1000;
            else if (string_split.length == 2 && string_split[0].match(digits) && string_split[1].match(digits))
                duration = string_split[1] * 1000 + string_split[0] * 1000 * 60;
            else if (
                string_split.length == 3 &&
                string_split[0].match(digits) &&
                string_split[1].match(digits) &&
                string_split[2].match(digits)
            )
                duration = string_split[2] * 1000 + string_split[1] * 1000 * 60 + string_split[0] * 1000 * 3600;

            // if not yet parsed, try string parse
            if (duration === null) duration = parseDuration(string);

            // still nothing, clear value to restore placeholder
            if (duration === null) {
                $(this).val("");
                $(this).parent().removeAttr("data-duration");
            }

            // set duration
            else {
                duration = Math.floor(duration / 1000);

                var tmp = duration;
                var seconds = tmp % 60;
                tmp = (tmp - seconds) / 60;
                var minutes = tmp % 60;
                tmp = (tmp - minutes) / 60;
                var hours = tmp;

                seconds = seconds.toString().padStart(2, "0");
                minutes = minutes.toString().padStart(2, "0");
                hours = hours.toString().padStart(2, "0");

                $(this).val(hours + ":" + minutes + ":" + seconds);
                $(this).parent().attr("data-duration", duration);
            }
        });

    $(input).attr("data-ready", true);

    if ($(input).attr("data-value")) {
        $(input).val($(input).attr("data-value"));
        $(input).removeAttr("data-value");
    }
};

OB.UI.durationInputVal = function (input, value) {
    if (typeof value == "number" || typeof value == "string") {
        $(input).find("input").val(value).change();
    } else {
        var duration = $(input).attr("data-duration");
        if (!duration) return "";
        else return duration;
    }
};

OB.UI.datetimeInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;

    $(input).html('<input type="text" placeholder="e.g. &quot;Dec 16 5pm&quot;" />');
    $(input)
        .find("input")
        .change(function () {
            var string = $(this).val();
            var format = moment.parseFormat(string);
            var datetime = moment(string, format);
            if (isNaN(datetime.hour())) {
                $(this).val("");
                $(this).parent().removeAttr("data-date");
                $(this).parent().removeAttr("data-time");
            } else {
                $(this).val(datetime.format("YYYY-MM-DD HH:mm:ss"));
                $(this).parent().attr("data-date", datetime.format("YYYY-MM-DD"));
                $(this).parent().attr("data-time", datetime.format("HH:mm:ss"));
            }
        });

    $(input).attr("data-ready", true);

    if ($(input).attr("data-value")) {
        $(input).val($(input).attr("data-value"));
        $(input).removeAttr("data-value");
    }
};

OB.UI.datetimeInputVal = function (input, value) {
    if (value && value instanceof Date) {
        $(input).find("input").val(moment(value).format("YYYY-MM-DD HH:mm:ss")).change();
    } else if (value) {
        $(input).find("input").val(value).change();
    } else {
        var date = $(input).attr("data-date");
        var time = $(input).attr("data-time");
        if (!date) return "";
        else return date + " " + time;
    }
};

OB.UI.dateInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;

    var placeholder = $(input).attr("data-placeholder") ?? 'e.g. "Mar 2, 2021"';
    var display = $(input).attr("data-format") ?? "YYYY-MM-DD";

    $(input).html($('<input type="text" />').attr("placeholder", placeholder));
    $(input)
        .find("input")
        .change(function () {
            var string = $(this).val();
            var format = moment.parseFormat(string);
            var datetime = moment(string, format);
            if (isNaN(datetime.hour())) {
                $(this).val("");
                $(this).parent().removeAttr("data-date");
            } else {
                $(this).val(datetime.format(display));
                $(this).parent().attr("data-date", datetime.format("YYYY-MM-DD"));
            }
        });

    $(input).attr("data-ready", true);

    if ($(input).attr("data-value")) {
        $(input).val($(input).attr("data-value"));
        $(input).removeAttr("data-value");
    }
};

OB.UI.dateInputVal = function (input, value) {
    if (value && value instanceof Date) {
        $(input).find("input").val(moment(value).format("YYYY-MM-DD")).change();
    } else if (value) {
        $(input).find("input").val(value).change();
    } else {
        var date = $(input).attr("data-date");
        if (!date) return "";
        else return date;
    }
};

OB.UI.timeInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;

    $(input).html('<input type="text" placeholder="e.g. &quot;11:30am&quot;" />');
    $(input)
        .find("input")
        .change(function () {
            var string = $(this).val();
            var format = moment.parseFormat(string);
            var datetime = moment(string, format);
            if (isNaN(datetime.hour())) {
                $(this).val("");
                $(this).parent().removeAttr("data-time");
            } else {
                $(this).val(datetime.format("HH:mm:ss"));
                $(this).parent().attr("data-time", datetime.format("HH:mm:ss"));
            }
        });

    $(input).attr("data-ready", true);

    if ($(input).attr("data-value")) {
        $(input).val($(input).attr("data-value"));
        $(input).removeAttr("data-value");
    }
};

OB.UI.timeInputVal = function (input, value) {
    if (value && value instanceof Date) {
        $(input).find("input").val(moment(value).format("HH:mm:ss")).change();
    } else if (value) {
        $(input).find("input").val(value).change();
    } else {
        var time = $(input).attr("data-time");
        if (!time) return "";
        else return time;
    }
};

OB.UI.htmlInputInit = function (input) {
    if ($(input).attr("data-ready") || $(input).attr("data-pending")) return true;
    $(input).attr("data-pending", true);

    var id = 0;
    while ($(".ob-html-input-" + id).length == 1) id++;

    $(input)
        .addClass("ob-html-input-" + id)
        .append($("<textarea/>"));

    tinymce.init({
        selector: ".ob-html-input-" + id + " textarea",
        theme: "silver",
        plugins: "link",
        setup: function (editor) {
            editor.on("init", function (e) {
                $(input).removeAttr("data-pending");
                $(input).attr("data-ready", true);

                if ($(input).data("initValue")) {
                    OB.UI.htmlInputVal(input, $(input).data("initValue"));
                    $(input).removeData("initValue");
                }
            });
        },
        menubar: false,
        toolbar: "undo redo | bold italic | link",
        branding: false,
    });
};

OB.UI.htmlInputVal = function (input, value) {
    // Set HTML
    if (typeof value === "string") {
        if (!$(input).attr("data-ready")) {
            $(input).data("initValue", value);
            return $(input);
        }

        tinymce.get($(input).find("textarea").attr("id")).setContent(value);
    }

    // Get HTML
    return tinymce.get($(input).find("textarea").attr("id")).getContent();
};

OB.UI.mediaInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;
    $(input).attr("data-ready", true);

    $(input).addClass("droppable_target_media");

    $(input).droppable({
        drop: function (event, ui) {
            if ($(ui.draggable).attr("data-mode") == "media") {
                $(".sidebar_search_media_selected").each(function (index, element) {
                    var data_id = $(element).attr("data-id");

                    var is_dup = $(input).find("ob-media[data-id=" + data_id + "]").length > 0;
                    if (is_dup) {
                        return true;
                    }

                    if ($(element).attr("data-visibility") == "private") {
                        OB.UI.alert("Private media items not allowed.");
                        return true;
                    }

                    var label = $(element).attr("data-artist") + " - " + $(element).attr("data-title");
                    var rmbutton = $("<span/>").click(OB.UI.mediaInputDelete);
                    var result = $("<ob-media/>")
                        .text(label)
                        .attr("data-id", $(element).attr("data-id"))
                        .append(rmbutton);

                    if ($(input).attr("data-single") != undefined) {
                        $(input).html(result);
                    } else {
                        $(input).append(result);
                    }
                });
            }

            OB.Sidebar.mediaSelectNone();
        },
    });
};

OB.UI.mediaReadOnly = function (input) {
    $(input).droppable("destroy");
    $(input).addClass("readonly");
    $(input).removeClass("droppable_target_media");
};

OB.UI.mediaInputVal = function (input, value) {
    // Update values.
    if (Array.isArray(value)) {
        $(input).find("ob-media").remove();

        if (!value.length) return $(input);

        value = [...new Set(value)];
        if ($(input).attr("data-single") != undefined) {
            value = [value[0]];
        }

        var post = [];
        value.forEach(function (media_id) {
            post.push(["media", "get", { id: media_id }]);
        });

        $(input).attr("data-loading", "true");

        OB.API.multiPost(post, function (data) {
            $(input).removeAttr("data-loading");

            data.forEach(function (response) {
                if (response.status != true) return false;
                var media_item = $("<ob-media/>").attr("data-id", response.data.id);
                var label = response.data.artist + " - " + response.data.title;
                var rmbutton = $("<span/>").click(OB.UI.mediaInputDelete);
                media_item.text(label);
                media_item.append(rmbutton);
                $(input).append(media_item);
            });
        });
        return $(input);
    }

    // Get values.
    var media = [];
    $(input)
        .find("ob-media")
        .each(function (index, item) {
            var media_id = $(item).attr("data-id");
            if (media_id) media.push(parseInt(media_id));
        });
    return media;
};

OB.UI.mediaInputDelete = function () {
    $(this).parent().remove();
};

OB.UI.playlistInputInit = function (input) {
    if ($(input).attr("data-ready")) return true;
    $(input).attr("data-ready", true);

    $(input).addClass("droppable_target_playlist");

    $(input).droppable({
        drop: function (event, ui) {
            if ($(ui.draggable).attr("data-mode") == "playlist") {
                $(".sidebar_search_playlist_selected").each(function (index, element) {
                    var data_id = $(element).attr("data-id");

                    var is_dup = $(input).find("ob-playlist[data-id=" + data_id + "]").length > 0;
                    if (is_dup) {
                        return true;
                    }

                    if ($(element).attr("data-visbility") == "private") {
                        //T Private playlist items not allowed.
                        OB.UI.alert("Private playlist items not allowed.");
                        return true;
                    }

                    var label = $(element).attr("data-name") + " - " + $(element).attr("data-description");
                    var rmbutton = $("<span/>").click(OB.UI.playlistInputDelete);
                    var result = $("<ob-playlist/>")
                        .text(label)
                        .attr("data-id", $(element).attr("data-id"))
                        .append(rmbutton);

                    if ($(input).attr("data-single") != undefined) {
                        $(input).html(result);
                    } else {
                        $(input).append(result);
                    }
                });
            }

            OB.Sidebar.playlistSelectNone();
        },
    });
};

OB.UI.playlistReadOnly = function (input) {
    $(input).droppable("destroy");
    $(input).addClass("readonly");
    $(input).removeClass("droppable_target_playlist");
};

OB.UI.playlistInputVal = function (input, value) {
    // Update values.
    if (Array.isArray(value)) {
        $(input).find("ob-playlist").remove();

        if (!value.length) return $(input);

        value = [...new Set(value)];
        if ($(input).attr("data-single") != undefined) {
            value = [value[0]];
        }

        var post = [];
        value.forEach(function (playlist_id) {
            post.push(["playlists", "get", { id: playlist_id }]);
        });

        $(input).attr("data-loading", "true");

        OB.API.multiPost(post, function (data) {
            $(input).removeAttr("data-loading");

            data.forEach(function (response) {
                if (response.status != true) return false;
                var playlist_item = $("<ob-playlist/>").attr("data-id", response.data.id);
                var label = response.data.name + " - " + response.data.description;
                var rmbutton = $("<span/>").click(OB.UI.playlistInputDelete);
                playlist_item.text(label);
                playlist_item.append(rmbutton);
                $(input).append(playlist_item);
            });
        });
        return $(input);
    }

    // Get values.
    var playlists = [];
    $(input)
        .find("ob-playlist")
        .each(function (index, item) {
            var playlist_id = $(item).attr("data-id");
            if (playlist_id) playlists.push(parseInt(playlist_id));
        });
    return playlists;
};

OB.UI.playlistInputDelete = function () {
    $(this).parent().remove();
};

OB.UI.userInputInit = function (input) {
    if ($(input).attr("data-ready") || $(input).attr("data-pending")) return true;
    $(input).attr("data-pending", true);

    OB.API.post("users", "user_list", {}, function (response) {
        if (!response.status) return;

        $(input).removeAttr("data-pending");
        $(input).attr("data-ready", true);

        //T Select User
        $select = $('<select><option value="">' + OB.t("Select User") + "</option></select>");

        //T All Users
        if ($(input).attr("data-all-option") !== undefined) {
            $select.append($("<option />").text(OB.t("All Users")).attr("value", "all"));
        }

        response.data.forEach(function (user) {
            $select.append(
                $("<option />")
                    .text(user.display_name + " (" + user.email + ")")
                    .attr("value", user.id),
            );
        });

        $(input).html($select);
        $(input).find("select").change(OB.UI.userInputSelect);

        // set initial value if we have it
        if ($(input).data("initValue")) {
            OB.UI.userInputVal(input, $(input).data("initValue"));
            $(input).removeData("initValue");
        }

        if ($(input).attr("data-initcallback")) {
            getFunctionByName($(input).attr("data-initcallback"))();
        }
    });
};

OB.UI.userReadOnly = function (input) {
    $(input).addClass("readonly");
};

OB.UI.userInputSelect = function () {
    if ($(this).parent().attr("data-single") != undefined) {
        $(this).parent().find("ob-user").remove();
        $(this).children().prop("disabled", false);
    }

    $option = $(this).find("option:selected");

    $(this)
        .parent()
        .append($("<ob-user />").text($option.text()).append("<span></span>").attr("data-id", $option.attr("value")));
    $(this).parent().find("ob-user span").click(OB.UI.userInputDelete);

    $(this).val("");
    $option.prop("disabled", true);
};

OB.UI.userInputVal = function (input, value) {
    // set value
    if (Array.isArray(value)) {
        // if widget not ready, store value and set once initialized (see OB.UI.userInputInit)
        if (!$(input).attr("data-ready")) {
            $(input).data("initValue", value);
            return $(input);
        }

        value = [...new Set(value)];
        if ($(input).attr("data-single") != undefined) {
            value = [value[0]];
        }

        $(input).find("ob-user").remove();
        $(input).find("option").prop("disabled", false);
        value.forEach(function (user) {
            $(input).find("select").val(user).change();
        });
        return $(input);
    }

    // get value
    var users = [];

    $(input)
        .find("ob-user")
        .each(function (index, user) {
            var user_id = $(user).attr("data-id");
            if (user_id) users.push(parseInt(user_id));
        });

    return users;
};

OB.UI.userInputDelete = function () {
    var id = $(this).parent().attr("data-id");
    $(this)
        .parents("ob-user-input")
        .first()
        .find("select option[value=" + id + "]")
        .prop("disabled", false);
    $(this).parent().remove();
};

OB.UI.groupInputInit = function (input) {
    if ($(input).attr("data-ready") || $(input).attr("data-pending")) return true;
    $(input).attr("data-pending", true);

    OB.API.post("users", "group_list", {}, function (response) {
        if (!response.status) return;

        $(input).removeAttr("data-pending");
        $(input).attr("data-ready", true);

        //T Select Group
        $select = $('<select><option value="">' + OB.t("Select Group") + "</option></select>");

        response.data.forEach(function (group) {
            $select.append($("<option />").text(group.name).attr("value", group.id));
        });

        $(input).html($select);
        $(input).find("select").change(OB.UI.groupInputSelect);

        // set initial value if we have it
        if ($(input).data("initValue")) {
            OB.UI.groupInputVal(input, $(input).data("initValue"));
            $(input).removeData("initValue");
        }
    });
};

OB.UI.groupInputSelect = function () {
    $option = $(this).find("option:selected");

    $(this)
        .parent()
        .append($("<ob-group />").text($option.text()).append("<span></span>").attr("data-id", $option.attr("value")));
    $(this).parent().find("ob-group span").click(OB.UI.groupInputDelete);

    $(this).val("");
    $option.prop("disabled", true);
};

OB.UI.groupInputVal = function (input, value) {
    // set value
    if (Array.isArray(value)) {
        // if widget not ready, store value and set once initialized (see OB.UI.groupInputInit)
        if (!$(input).attr("data-ready")) {
            $(input).data("initValue", value);
            return $(input);
        }

        $(input).find("ob-group").remove();
        $(input).find("option").prop("disabled", false);
        value.forEach(function (group) {
            $(input).find("select").val(group).change();
        });
        return $(input);
    }

    // get value
    var groups = [];

    $(input)
        .find("ob-group")
        .each(function (index, group) {
            var group_id = $(group).attr("data-id");
            if (group_id) groups.push(parseInt(group_id));
        });

    return groups;
};

OB.UI.groupInputDelete = function () {
    var id = $(this).parent().attr("data-id");
    $(this)
        .parents("ob-group-input")
        .first()
        .find("select option[value=" + id + "]")
        .prop("disabled", false);
    $(this).parent().remove();
};

OB.UI.tagInputInit = function (tag) {
    if ($(tag).attr("data-ready")) return true;
    $(tag).attr("contenteditable", true);
    $(tag).attr("data-ready", true);
    $(tag).keypress(function (e) {
        if (e.which == 13 || e.which == 44) {
            e.preventDefault();

            // get text not in ob-tag, ob-suggestions
            $tag = $(tag).clone();
            $tag.find("ob-tag").remove();
            $tag.find("ob-suggestions").remove();
            var newtag = $tag.text();

            OB.UI.tagInputAdd(tag, newtag);
        }
    });

    $(tag).keydown(function (e) {
        if (
            e.originalEvent.getModifierState("Control") ||
            e.originalEvent.getModifierState("Alt") ||
            e.originalEvent.getModifierState("Meta")
        )
            return;

        var textNode = false;
        $.each($(tag)[0].childNodes, function (index, node) {
            if (node.nodeType == 3) {
                textNode = node;
                return false;
            }
        });

        if (textNode === false) {
            textNode = document.createTextNode("");
            $(tag)[0].appendChild(textNode);
        }

        if (e.key.length == 1) textNode.nodeValue = textNode.nodeValue.trim() + e.key;
        else if (e.key == "Backspace") {
            var text = textNode.nodeValue.trim();
            if (text.length > 0) textNode.nodeValue = text.substr(0, text.length - 1);
        } else return;

        setEndOfContenteditable($(tag)[0]);
        e.preventDefault();
    });

    // keyup or focus in will search for tags
    $(tag).focusin(function () {
        OB.UI.tagSuggestions(tag);
    });
    $(tag).keyup(function (e) {
        // suggesitons
        OB.UI.tagSuggestions(tag);
    });

    // force plain text paste
    // https://stackoverflow.com/questions/12027137/javascript-trick-for-paste-as-plain-text-in-execcommand
    $(tag).on("paste", function (e) {
        e.preventDefault();
        var text = "";
        if (e.clipboardData || e.originalEvent.clipboardData) {
            text = (e.originalEvent || e).clipboardData.getData("text/plain");
        } else if (window.clipboardData) {
            text = window.clipboardData.getData("Text");
        }
        if (document.queryCommandSupported("insertText")) {
            document.execCommand("insertText", false, text);
        } else {
            document.execCommand("paste", false, text);
        }
    });

    $(tag).find("ob-tag").attr("contenteditable", false);
};

OB.UI.tagSuggestions = function (tag) {
    if ($(tag).attr("data-suggestions") == "false") return;

    // add if needed (possible to get deleted with contenteditable, or on first init)
    if (!$(tag).find("ob-tag-suggestions").length)
        $(tag).prepend($("<ob-tag-suggestions></ob-tag-suggestions>").attr("contenteditable", false));
    setEndOfContenteditable($(tag)[0]);

    // search
    var $search = $(tag).clone();
    $search.find("ob-tag").remove();
    var search = $search.text().trim();
    var id = $search.attr("data-field-id");

    if (OB.UI.tagInputXhr[id] !== undefined) {
        OB.API.abort(OB.UI.tagInputXhr[id]);
        OB.UI.tagInputXhr[id] = undefined;
    }

    var xhrid = OB.API.post("metadata", "metadata_tag_search", { id: id, search: search }, function (response) {
        $suggestions = $(tag).find("ob-tag-suggestions");
        if (!$suggestions.attr("data-simplebar")) new SimpleBar($suggestions[0]);
        $suggestions.find("ob-tag").remove();

        if (response.status != true) return;

        $.each(response.data, function () {
            var suggestion = this;
            var unique = true;

            $(tag)
                .find("ob-tag")
                .each(function () {
                    if ($(this).text() == suggestion) unique = false;
                });

            if (unique) $suggestions.find(".simplebar-content").append($("<ob-tag></ob-tag>").text(suggestion));
        });

        $suggestions.find("ob-tag").click(function (e) {
            OB.UI.tagInputAdd(tag, $(this).text());
            $(this).remove();
        });
    });

    OB.UI.tagInputXhr[id] = xhrid;
};

OB.UI.tagInputAdd = function (tag, newtag) {
    var tagset = new Set();
    $(tag)
        .find("> ob-tag")
        .each(function (index, obtag) {
            tagset.add($(obtag).text());
        });

    tagset.forEach(function (value) {
        if (value.toLowerCase() == newtag.toLowerCase()) tagset.delete(value);
    });
    tagset.add(newtag);

    OB.UI.tagInputVal(tag, Array.from(tagset));

    // cancel search if needed
    var id = $(tag).attr("data-field-id");
    if (OB.UI.tagInputXhr[id] !== undefined) {
        OB.API.abort(OB.UI.tagInputXhr[id]);
        OB.UI.tagInputXhr[id] = undefined;
    }

    OB.UI.tagSuggestions(tag);
};

OB.UI.tagInputXhr = {};

OB.UI.tagInputVal = function (tag, value) {
    // set value
    if (Array.isArray(value)) {
        // remove current tags
        $(tag).find("> ob-tag").remove();

        // remove text not in tag
        $(tag)
            .contents()
            .filter(function () {
                return this.nodeType == 3;
            })
            .remove();

        var tagset = new Set();
        value.forEach(function (item) {
            item = item.toString().trim();
            if (item != "") tagset.add(item);
        });
        tagset = Array.from(tagset);
        tagset.sort(function (a, b) {
            return a.toLowerCase().localeCompare(b.toLowerCase());
        });
        tagset.forEach(function (value) {
            value = value.trim();
            if (value != "") $(tag).append($("<ob-tag />").text(value).append("<span></span>"));
        });
        $(tag).find("> ob-tag").attr("contenteditable", false);
        $(tag).find("> ob-tag span").click(OB.UI.tagInputDelete);
        return $(tag);
    }

    // get value
    var tagset = new Set();
    $(tag)
        .find("> ob-tag")
        .each(function (index, item) {
            item = $(item).text().trim();
            if (item != "") tagset.add(item);
        });
    return Array.from(tagset);
};

OB.UI.tagInputDelete = function () {
    var input_tag = $(this).parents("ob-tag-input").first()[0];
    $(this).parents("ob-tag").first().remove();
    OB.UI.tagSuggestions(input_tag);
};

OB.UI.getHTML = function (what) {
    return this.htmlCache[what];
};

OB.UI.ajaxLoader = function (where, type) {
    if (type == undefined) type = "";
    else type = "-" + type;

    $(where).html('<center><img src="/images/ajax-loader' + type + '.gif"></center>');
};

OB.UI.ajaxLoaderOn = function () {
    $("#footer_ajax").css("visibility", "visible");
    $("#footer_ajax").animate({ opacity: 1 }, "fast");
};

OB.UI.ajaxLoaderOff = function () {
    $("#footer_ajax").animate({ opacity: 0 }, "fast");
    $("#footer_ajax").css("visibility", "hidden");
};

OB.UI.alert = function (text) {
    OB.UI.alertOff();
    OB.UI.confirmOff();

    $("body").append(OB.UI.getHTML("alert.html"));
    $("#alert").draggable({ containment: "document" });

    // hook up things and string translate
    OB.UI.widgetHTML($("#alert_container"));

    if (text) $("#alert_message").text(OB.t(text));
};

OB.UI.alertOff = function () {
    $("#alert_container").remove();
};

OB.UI.confirm = function (text, callback, okay_text, cancel_text, okay_class, cancel_class) {
    if (text && text.constructor === Object) {
        var args = text;
        if (args.text) text = args.text;
        if (args.callback) callback = args.callback;
        if (args.okay_text) okay_text = args.okay_text;
        if (args.cancel_text) cancel_text = args.cancel_text;
        if (args.okay_class) okay_class = args.okay_class;
        if (args.cancel_class) cancel_class = args.cancel_class;
    }

    OB.UI.alertOff();
    OB.UI.confirmOff();

    $("body").append(OB.UI.getHTML("confirm.html"));
    $("#confirm").draggable({ containment: "document" });

    // hook up things and string translate
    OB.UI.widgetHTML($("#confirm_container"));

    if (text) $("#confirm_message").text(OB.t(text));
    if (okay_text) $("#confirm_button_okay").text(OB.t(okay_text));
    if (cancel_text) $("#confirm_button_cancel").text(OB.t(cancel_text));
    if (okay_class) $("#confirm_button_okay").addClass(okay_class);
    if (cancel_class) $("#confirm_button_cancel").addClass(cancel_class);

    if (callback) OB.UI.confirmCallback = callback;
    else
        OB.UI.confirmCallback = function () {
            return;
        };
};

OB.UI.confirmOff = function (callback) {
    $("#confirm_container").remove();
    if (callback) OB.UI.confirmCallback();
};

OB.UI.confirmCallback = null;

OB.UI.permissionsUpdate = function (context) {
    if (typeof context === "undefined") context = "body";

    var available_permissions = new Array();

    // determine available permissions without item id.  (we just need access to 1 item in order to show) if item id is not specified.
    $.each(OB.Settings.permissions, function (index, permission) {
        var permission_array = permission.split(":");
        available_permissions.push(permission_array[0]);
    });

    // show/hide based on available permissions.
    $(context)
        .find("[data-permissions]")
        .each(function (index, element) {
            var permissions_list = $(element).attr("data-permissions").split(" ");
            var has_permission = false;

            for (var i in permissions_list) {
                if (
                    available_permissions.indexOf(permissions_list[i]) != -1 ||
                    OB.Settings.permissions.indexOf(permissions_list[i]) != -1
                ) {
                    has_permission = true;
                    break;
                }
            }

            if (has_permission) $(element).show();
            else $(element).hide();
        });

    // if our menu has no visible items, hide the menu...
    $(context)
        .find("#obmenu > li")
        .each(function (index, element) {
            var count = 0;

            $(element)
                .find("li")
                .each(function (i, e) {
                    if ($(e).css("display") != "none") count++;
                });

            if (count == 0) $(element).hide();
            else $(element).show();
        });
};

OB.UI.replaceMain = function (file, attrs) {
    $("#layout_main").html(OB.UI.getHTML(file));
    $("#layout_main").attr("data-src", file);

    // form tag is used for layout information, but we never do a regular form submit.
    $("#layout_main form").submit(function () {
        event.preventDefault();
    });

    // add attributes if we have them
    if (attrs)
        $.each(attrs, function (key, value) {
            $("#layout_main").attr(key, value);
        });

    OB.UI.widgetHTML($("#layout_main"));
};

OB.UI.scrollIntoView = function ($element) {
    if ($element.closest("#layout_main_container").length) var $container = $("#layout_main_container").first();
    else if ($element.closest("#layout_modal_window").length) var $container = $("#layout_modal_window").first();
    else return;

    // TODO: some instances where element is not entirely visible still return as visible.
    if (!$element.visible(false)) {
        $container.scrollTo($element, { offset: { top: -10 } });
    }
};

OB.UI.openModalWindow = function (file) {
    $("#layout_modal_window").remove();
    $("#layout_modal_container").append('<div id="layout_modal_window"></div>');

    $("body").addClass("modal-open");

    // TODO draggable modal disabled because interfearing with contenteditable tags focus; need to fix.
    // $('#layout_modal_window').draggable({ containment: 'document' });

    if (file) {
        $("#layout_modal_window").html(OB.UI.getHTML(file));
        OB.UI.widgetHTML($("#layout_modal_window"));
    } else $("#layout_modal_window").html("");

    // trigger dom callback, mutation observer not always called in time.
    OB.UI.domChangeCallback();
};

OB.UI.closeModalWindow = function () {
    $("body").removeClass("modal-open");
    $("#layout_modal_window").html(""); // clear out html to avoid ID conflicts, etc.
};

OB.UI.addMenuItem = function (name, slug, order) {
    // make sure this doesn't already exist.
    if ($("#obmenu > li[data-slug=" + slug + "]").length) return;

    // add
    var html = '<li style="display: none;" data-slug="' + slug + '" data-order="' + order + '"><a></a><ul></ul></li>';
    $("#obmenu > li").each(function (index, li) {
        if ($(li).attr("data-order") > order) {
            $(li).before(html);
            return false;
        }
    });

    // add to end if didn't get added yet.
    if (!$("#obmenu > li[data-slug=" + slug + "]").length) $("#obmenu").append(html);

    // name menu
    $("#obmenu > li[data-slug=" + slug + "] > a").text(OB.t(name));
};

OB.UI.addSubMenuItem = function (parent_slug, name, slug, onclick, order, permissions) {
    // make sure we have the requested parent.
    if (!$("#obmenu > li[data-slug=" + parent_slug + "]").length) return;

    // make sure this doesn't already exist.
    if ($("#obmenu > li[data-slug=" + parent_slug + "] > ul > li[data-slug=" + slug + "]").length) return;

    // add
    var html = '<li data-slug="' + slug + '" data-order="' + order + '"><a href="#" onclick="return false;"></a></li>';
    $("#obmenu > li[data-slug=" + parent_slug + "] > ul > li").each(function (index, li) {
        if ($(li).attr("data-order") > order) {
            $(li).before(html);
            return false;
        }
    });

    // add to end if didn't get added yet.
    if (!$("#obmenu > li[data-slug=" + parent_slug + "] > ul > li[data-slug=" + slug + "]").length)
        $("#obmenu > li[data-slug=" + parent_slug + "] > ul").append(html);

    // set properties
    $("#obmenu > li[data-slug=" + parent_slug + "] > ul > li[data-slug=" + slug + "] > a")
        .text(OB.t(name))
        .click(onclick);
    if (permissions)
        $("#obmenu > li[data-slug=" + parent_slug + "] > ul > li[data-slug=" + slug + "]").attr(
            "data-permissions",
            permissions,
        );

    // update item visibility
    OB.UI.permissionsUpdate();
};

OB.UI.translateHTML = function () {
    $("[data-t]").each(function (index, element) {
        $(element).text(OB.t($(element).text()));
        $(element).removeAttr("data-t");
    });
};

// translate based on namespace, name. returns name (which should be human readable ish at least) if no translation found.
OB.UI.translate = function (input, ...data) {
    // don't have string? huh.
    if (typeof input == "undefined") return "";

    // figure out our string name. if input is an array, string is first element and subsequent elements are data.
    if (typeof input == "object" && typeof input[0] == "string") {
        var name = input[0];
        data = input.splice(1);
    } else if (typeof input == "string") {
        var name = input;
    } else return "";

    /* if(typeof(OB.UI.strings[namespace])=='undefined') return name;
  if(typeof(OB.UI.strings[namespace][name])=='undefined') return name; */

    var string = name;
    if (OB.UI.strings[name] !== undefined) {
        var string = OB.UI.strings[name];
    }

    string = string.replace(/(\\)?%([1-9])/g, function (match_string, is_escaped, data_index) {
        if (is_escaped) return "%" + data_index;
        if (!data || !data[data_index - 1]) return "";
        return data[data_index - 1];
    });

    return string;
};

OB.t = OB.UI.translate;

// provide val function support for custom input tags
$.fn.oldVal = $.fn.val;
$.fn.val = function (value) {
    var tag = $(this).prop("tagName");

    if (tag == "OB-FORM") return typeof value == "undefined" ? OB.UI.formVal($(this)) : OB.UI.formVal($(this), value);
    if (tag == "OB-DURATION-INPUT")
        return typeof value == "undefined" ? OB.UI.durationInputVal($(this)) : OB.UI.durationInputVal($(this), value);
    if (tag == "OB-DATETIME-INPUT")
        return typeof value == "undefined" ? OB.UI.datetimeInputVal($(this)) : OB.UI.datetimeInputVal($(this), value);
    if (tag == "OB-DATE-INPUT")
        return typeof value == "undefined" ? OB.UI.dateInputVal($(this)) : OB.UI.dateInputVal($(this), value);
    if (tag == "OB-TIME-INPUT")
        return typeof value == "undefined" ? OB.UI.timeInputVal($(this)) : OB.UI.timeInputVal($(this), value);
    if (tag == "OB-TAG-INPUT")
        return typeof value == "undefined" ? OB.UI.tagInputVal($(this)) : OB.UI.tagInputVal($(this), value);
    if (tag == "OB-USER-INPUT")
        return typeof value == "undefined" ? OB.UI.userInputVal($(this)) : OB.UI.userInputVal($(this), value);
    if (tag == "OB-GROUP-INPUT")
        return typeof value == "undefined" ? OB.UI.groupInputVal($(this)) : OB.UI.groupInputVal($(this), value);

    if (tag == "OB-MEDIA-INPUT")
        return typeof value == "undefined" ? OB.UI.mediaInputVal($(this)) : OB.UI.mediaInputVal($(this), value);
    if (tag == "OB-PLAYLIST-INPUT")
        return typeof value == "undefined" ? OB.UI.playlistInputVal($(this)) : OB.UI.playlistInputVal($(this), value);

    if (tag == "OB-HTML-INPUT")
        return typeof value == "undefined" ? OB.UI.htmlInputVal($(this)) : OB.UI.htmlInputVal($(this), value);

    return typeof value == "undefined" ? $(this).oldVal() : $(this).oldVal(value);
};

// fix droppable to limit to modal window if modal window open
$.fn.oldDroppable = $.fn.droppable;
$.fn.droppable = function (...args) {
    if (typeof args[0] == "object" && typeof args[0].drop == "function") {
        args[0].oldDrop = args[0].drop;
        args[0].drop = function (event, ui) {
            // if modal window is open but this isn't inside the modal window, ignore drop.
            if ($("#layout_modal_window:visible").length && !$(this).parents("#layout_modal_window").length)
                return false;

            // otherwise, do the usual.
            args[0].oldDrop(event, ui);
        };
    }

    $(this).oldDroppable(...args);
};
