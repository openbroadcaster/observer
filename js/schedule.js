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

OB.Schedule = new Object();

OB.Schedule.init = function () {
    OB.Callbacks.add("ready", -5, OB.Schedule.initMenu);
};

OB.Schedule.initMenu = function () {
    //T Schedules
    OB.UI.addMenuItem("Schedules", "schedules", 40);
    //T Shows
    OB.UI.addSubMenuItem("schedules", "Shows", "shows", OB.Schedule.schedule);
    //T Timeslots
    OB.UI.addSubMenuItem("schedules", "Timeslots", "timeslot", OB.Schedule.scheduleTimeslots, 20, "manage_timeslots");
};

OB.Schedule.player_id = null;

OB.Schedule.schedule_mode = null;

OB.Schedule.user_list = null;

OB.Schedule.schedule = function () {
    OB.Schedule.player_id = null;

    OB.UI.replaceMain("schedule/schedule.html");

    OB.Schedule.schedule_mode = "schedule";
    OB.Schedule.setScheduleDates();
    OB.Schedule.scheduleInit();

    //T Shows
    $("#schedule_heading").text(OB.t("Shows"));
    //T Drag media or playlist onto schedule. Double-click show to edit.
    $("#schedule_welcome").text(OB.t("Drag media or playlist onto schedule. Double-click show to edit."));
    $("#schedule_welcome").prepend(
        '<span id="schedule_linein" class="hidden"><button onclick="OB.Schedule.addShowWindow(\'linein\');"></button> &nbsp; </span>',
    );
    $("#schedule_linein button").text(OB.t("Schedule Line-In"));

    // setup schedule table as drop area for playlists and media
    $("#schedule_container").addClass("droppable_target_media");
    $("#schedule_container").addClass("droppable_target_playlist");

    $("#schedule_container").droppable({
        drop: function (event, ui) {
            if ($(ui.draggable).attr("data-mode") == "media") {
                //T You can schedule only one item at a time.
                if ($(".sidebar_search_media_selected").length != 1) {
                    OB.UI.alert("You can schedule only one item at a time.");
                    return;
                }

                var item_type = "media";
                var item_id = $(".sidebar_search_media_selected").first().attr("data-id");
                var item_name =
                    $(".sidebar_search_media_selected").first().attr("data-artist") +
                    " - " +
                    $(".sidebar_search_media_selected").first().attr("data-title");
            } else if ($(ui.draggable).attr("data-mode") == "playlist") {
                //T You can schedule only one item at a time.
                if ($(".sidebar_search_playlist_selected").length != 1) {
                    OB.UI.alert("You can schedule only one item at a time.");
                    return;
                }

                var item_type = "playlist";
                var item_id = $(".sidebar_search_playlist_selected").first().attr("data-id");
                var item_name = $(".sidebar_search_playlist_selected").first().attr("data-name");
            } else return; // media_dynamic not supported yet.

            var item_duration = $(".sidebar_search_media_selected").first().attr("data-duration");

            OB.Schedule.addShowWindow(item_type, item_id, item_name, item_duration);
        },
    });
};

OB.Schedule.scheduleTimeslots = function () {
    OB.Schedule.player_id = null;

    OB.UI.replaceMain("schedule/schedule.html");

    OB.Schedule.schedule_mode = "timeslot";
    OB.Schedule.setScheduleDates();
    OB.Schedule.scheduleInit();

    //T Timeslots
    $("#schedule_heading").text(OB.t("Timeslots"));

    //T Add Timeslot
    var add_text = htmlspecialchars(OB.t("Add Timeslot"));
    //T or double-click a box to edit/delete.
    var instructions_text = htmlspecialchars(OB.t("or double-click a box to edit/delete."));

    $("#schedule_welcome").html(
        '<button onclick="OB.Schedule.addTimeslotWindow();">' + add_text + "</button> " + instructions_text,
    );
};

OB.Schedule.scheduleInit = function () {
    var post = [];
    post.push(["players", "search", {}]);

    if (OB.Schedule.schedule_mode === "timeslot") var last_player = OB.Settings.store("timeslots-player");
    else var last_player = OB.Settings.store("shows-player");

    OB.API.multiPost(post, function (responses) {
        var players = responses[0].data;

        $.each(players, function (index, item) {
            if (item.use_parent_schedule == "1") return; // player uses parent schedule, setting schedule would not do anything.

            // skip players we don't have permission for.  (timeslot page only)
            if (
                OB.Schedule.schedule_mode == "timeslot" &&
                OB.Settings.permissions.indexOf("manage_timeslots") == -1 &&
                OB.Settings.permissions.indexOf("manage_timeslots:" + item.id) == -1
            )
                return;

            if (OB.Schedule.player_id == null) OB.Schedule.player_id = item.id; // default to first player.

            $("#schedule_player_select").append(
                '<option value="' +
                    item.id +
                    '" data-linein="' +
                    item.support_linein +
                    '">' +
                    htmlspecialchars(item.name) +
                    "</option>",
            );
        });

        // if we have a valid last player for this schedule, set that.
        if (
            typeof last_player !== "undefined" &&
            $("#schedule_player_select option[value=" + last_player.player + "]").length
        ) {
            $("#schedule_player_select").val(last_player.player);
            OB.Schedule.player_id = last_player.player;
        }

        OB.Schedule.loadSchedule();
    });
};

OB.Schedule.playerChange = function () {
    OB.Schedule.player_id = $("#schedule_player_select").val();
    OB.Schedule.loadSchedule();
};

OB.Schedule.deleteShow = function (confirm) {
    if (confirm) {
        OB.API.post("shows", "delete", { id: $("#show_id").val() }, function (data) {
            if (data.status == true) {
                OB.UI.closeModalWindow();
                OB.Schedule.loadSchedule();
            } else {
                $("#show_addedit_message").obWidget("error", data.msg);
            }
        });
    } else {
        //T Delete this show?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Delete this show?",
            function () {
                OB.Schedule.deleteShow(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    }
};

OB.Schedule.deleteTimeslot = function (confirm) {
    if (confirm) {
        OB.API.post("timeslots", "delete", { id: $("#timeslot_id").val() }, function (data) {
            if (data.status == true) {
                OB.UI.closeModalWindow();
                OB.Schedule.loadSchedule();
            } else {
                $("#timeslot_addedit_message").obWidget("error", data.msg);
            }
        });
    } else {
        //T Delete this timeslot?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Delete this timeslot?",
            function () {
                OB.Schedule.deleteTimeslot(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    }
};

OB.Schedule.saveShow = function () {
    fields = new Object();

    fields.id = $("#show_id").val();

    fields.mode = $("#show_mode").val();
    fields.x_data = $("#show_x_data").val();

    fields.start = $("#show_start_datetime").val();

    fields.duration = $("#show_duration").val();

    if (fields.mode != "once" && $("#show_stop_date").val()) {
        /* var stop_date_array = $('#show_stop_date').val().split('-');
    var stop_date = new Date(parseInt(stop_date_array[0]),parseInt(stop_date_array[1]-1),parseInt(stop_date_array[2]),23,59,59,0);

    fields.stop = Math.round(stop_date.getTime()/1000)+''; */
        fields.stop = $("#show_stop_date").val();
    } else fields.stop = "";

    fields.player_id = OB.Schedule.player_id;

    fields.item_type = $("#show_item_type").val();
    fields.item_id = $("#show_item_id").val();

    OB.API.post("shows", "save", fields, function (data) {
        if (data.status == true) {
            OB.UI.closeModalWindow();
            OB.Schedule.loadSchedule();
        } else {
            $("#show_addedit_message").obWidget("error", data.msg);
        }
    });
};

OB.Schedule.saveTimeslot = function () {
    fields = new Object();

    fields.id = $("#timeslot_id").val();

    fields.description = $("#timeslot_description").val();

    fields.user_id = $("#timeslot_user_id").val();
    fields.mode = $("#timeslot_mode").val();
    fields.x_data = $("#timeslot_x_data").val();

    /*var start_date = new Date($('#timeslot_start_datetime').val());
  if(!start_date) fields.start = '';
  else fields.start = Math.round(start_date.getTime()/1000)+'';*/
    fields.start = $("#timeslot_start_datetime").val();

    fields.duration = $("#timeslot_duration").val();

    /*if(fields.mode!='once' && $('#timeslot_stop_date').val())
  {

    var stop_date_array = $('#timeslot_stop_date').val().split('-');
    var stop_date = new Date(parseInt(stop_date_array[0]),parseInt(stop_date_array[1]-1),parseInt(stop_date_array[2]),23,59,59,0);

    fields.stop = Math.round(stop_date.getTime()/1000)+'';

  }
  else fields.stop = '';*/
    fields.stop = $("#timeslot_stop_date").val();

    fields.player_id = OB.Schedule.player_id;

    OB.API.post("timeslots", "save", fields, function (data) {
        if (data.status == true) {
            OB.UI.closeModalWindow();
            OB.Schedule.loadSchedule();
        } else {
            $("#timeslot_addedit_message").obWidget("error", data.msg);
        }
    });
};

OB.Schedule.addeditModeChange = function (where) {
    var val = $("#" + where + "_mode").val();

    if (val == "once") {
        $("#" + where + "_addedit_x_data").hide();
        $("#" + where + "_addedit_stop").hide();

        return;
    }

    $("#" + where + "_addedit_stop").show();

    if (val == "xweeks" || val == "xmonths" || val == "xdays") $("#" + where + "_addedit_x_data").show();
    else $("#" + where + "_addedit_x_data").hide();

    //T Weeks
    if (val == "xweeks") $("#" + where + "_addedit_x_data_interval").text(OB.t("Weeks"));
    //T Days
    if (val == "xdays") $("#" + where + "_addedit_x_data_interval").text(OB.t("Days"));
    //T Months
    if (val == "xmonths") $("#" + where + "_addedit_x_data_interval").text(OB.t("Months"));
};

OB.Schedule.addeditShowWindow = function (timeslots) {
    // no advanced schedule and no timeslots for user? display alert instead.
    if (timeslots.length == 0 && OB.Settings.permissions.indexOf("advanced_show_scheduling") == -1) {
        //T You do not have any timeslots available for the selected week.
        OB.UI.alert("You do not have any timeslots available for the selected week.");
        return;
    }

    OB.UI.openModalWindow("schedule/show_addedit.html");
    $("#show_mode").change(function () {
        OB.Schedule.addeditModeChange("show");
    });

    // fill up time slots.
    $.each(timeslots, function (index, slot) {
        var start = new Date(slot.start * 1000);
        var description =
            slot.description +
            ": " +
            OB.t(month_name(start.getMonth())) +
            " " +
            start.getDate() +
            ", " +
            timepad(start.getHours()) +
            ":" +
            timepad(start.getMinutes()) +
            ":" +
            timepad(start.getSeconds()) +
            " (" +
            secsToTime(slot.duration, "hms") +
            ")";

        var date = start.getFullYear() + "-" + timepad(start.getMonth() + 1) + "-" + timepad(start.getDate());

        $("#show_time_slot").append(
            '<option data-start_date="' +
                date +
                '" data-start_hour="' +
                start.getHours() +
                '" data-start_minute="' +
                start.getMinutes() +
                '" data-start_second="' +
                start.getSeconds() +
                '" data-duration="' +
                slot.duration +
                '">' +
                htmlspecialchars(description) +
                "</option>",
        );
    });

    if (OB.Settings.permissions.indexOf("advanced_show_scheduling") == -1) {
        $("#timeslot_manual_input_option").remove();
        $("#show_time_slot").val($("#show_time_slot").children().first().val());
        $("#show_time_slot").change();
    } // we have advanced permissions
    else {
    }
};

OB.Schedule.addeditShowWindowTimeslotChange = function () {
    var selected = $("#show_time_slot option:selected");

    if (!$(selected).attr("data-duration")) {
        $("#show_addedit_form input").add("#show_addedit_form select").attr("disabled", false);
        return;
    }

    $("#show_addedit_form input[type=text]")
        .add("#show_addedit_form select")
        .not("#show_time_slot")
        .attr("disabled", true);

    $("#show_mode").val("once");
    $("#show_start_datetime").val(
        $(selected).attr("data-start_date") +
            " " +
            timepad($(selected).attr("data-start_hour")) +
            ":" +
            timepad($(selected).attr("data-start_minute")) +
            ":" +
            timepad($(selected).attr("data-start_second")),
    );

    var duration = $(selected).attr("data-duration");
    $("#show_duration").val(duration);
};

OB.Schedule.addShowWindow = function (type, id, name, duration) {
    if (!id) id = 0;

    var pfields = new Object();
    pfields.start = String(Math.round(OB.Schedule.schedule_start.getTime() / 1000));
    pfields.end = String(Math.round(OB.Schedule.schedule_end.getTime() / 1000));
    pfields.player = OB.Schedule.player_id;
    pfields.user_id = OB.Account.user_id;

    OB.API.post("timeslots", "search", pfields, function (timeslots) {
        OB.Schedule.addeditShowWindow(timeslots.data);
        $(".edit_only").hide();

        // if not using advanced permissions, the selected timeslot will be the first one.
        // run the timeslot change callback so that the start/duration is filled out.
        if (OB.Settings.permissions.indexOf("advanced_show_scheduling") == -1) {
            if ($("#show_time_slot option").length > 0) OB.Schedule.addeditShowWindowTimeslotChange();
        }

        // otherwise (using advanced permissions), automatically fill out media duration if this is to schedule media.
        else if (type == "media") {
            $("#show_duration").val(Math.ceil(duration));
        }

        if (type == "linein") {
            //T Line-In
            $("#show_item_info").text(OB.t("Line-In"));
        } else $("#show_item_info").text(name + " (" + type + " #" + id + ")");

        $("#show_item_type").val(type);
        $("#show_item_id").val(id);
    });
};

OB.Schedule.editShowWindow = function (id, recurring) {
    var data_method = "get";

    OB.API.post("shows", data_method, { id: id }, function (data) {
        var pfields = new Object();
        pfields.start = String(Math.round(OB.Schedule.schedule_start.getTime() / 1000));
        pfields.end = String(Math.round(OB.Schedule.schedule_end.getTime() / 1000));
        pfields.player = OB.Schedule.player_id;
        pfields.user_id = OB.Account.user_id;

        if (data.status == true) {
            OB.API.post("timeslots", "search", pfields, function (timeslots) {
                show = data.data;

                OB.Schedule.addeditShowWindow(timeslots.data);

                $(".edit_only").show();

                if (show.item_type == "linein") {
                    //T Line-In
                    $("#show_item_info").text(OB.t("Line-In"));
                } else $("#show_item_info").text(show.item_name + " (" + show.item_type + " #" + show.item_id + ")");

                var show = data.data;

                var start_time = new Date(parseInt(show.start) * 1000);
                // $('#show_start_datetime').val(start_time);
                $("#show_start_datetime").val(show.start);

                if (show.recurring_end !== undefined) {
                    $("#show_stop_date").val(show.recurring_end);
                }

                $("#show_duration").val(show.duration);
                $("#show_id").val(show.id);
                $("#show_x_data").val(show.x_data);
                $("#show_mode").val(show.mode);

                // if we have timeslots, and this is scheduled once only, see if the duration/start lines up with one of the timeslots. (then select that timeslot).
                if ($("#show_time_slot option").length > 0 && !recurring) {
                    $(
                        "#show_time_slot option[data-start_date=" +
                            $("#show_start_datetime").attr("data-date") +
                            "][data-start_hour=" +
                            start_time.getHours() +
                            "][data-start_minute=" +
                            start_time.getMinutes() +
                            "][data-start_second=" +
                            start_time.getSeconds() +
                            "][data-duration=" +
                            show.duration +
                            "]",
                    ).attr("selected", true);
                }

                OB.Schedule.addeditModeChange("show");
            });
        } else OB.UI.alert(data.msg);
    });
};

OB.Schedule.addeditTimeslotWindow = function () {
    OB.UI.openModalWindow("schedule/timeslot_addedit.html");

    // populate users...
    $.each(OB.Schedule.user_list, function (index, value) {
        $("#timeslot_user_id").append(
            '<option value="' + value.id + '">' + htmlspecialchars(value.display_name) + "</option>",
        );
    });

    $("#timeslot_mode").change(function () {
        OB.Schedule.addeditModeChange("timeslot");
    });
};

OB.Schedule.addTimeslotWindow = function () {
    OB.API.post("users", "user_list", {}, function (data) {
        OB.Schedule.user_list = data.data;
        OB.Schedule.addeditTimeslotWindow();

        $(".edit_only").hide();
    });
};

OB.Schedule.editTimeslotWindow = function (id) {
    var data_method = "get";

    OB.API.post("users", "user_list", {}, function (user_data) {
        OB.API.post("timeslots", data_method, { id: id }, function (data) {
            OB.Schedule.user_list = user_data.data;
            OB.Schedule.addeditTimeslotWindow();

            $(".edit_only").show();

            var timeslot = data.data;

            $("#timeslot_mode").val(timeslot.mode);

            $("#timeslot_user_id").val(timeslot.user_id);

            //var start_time = new Date(parseInt(timeslot.start)*1000);
            $("#timeslot_start_datetime").val(timeslot.start);

            /*if(timeslot.stop!==undefined)
    {
      var stop_date = new Date(parseInt(timeslot.stop)*1000);
      $('#timeslot_stop_date').val(stop_date.getFullYear()+'-'+timepad(stop_date.getMonth()+1)+'-'+timepad(stop_date.getDate()));
    }*/
            $("#timeslot_stop_date").val(timeslot.recurring_end);

            $("#timeslot_duration").val(timeslot.duration);
            $("#timeslot_id").val(timeslot.id);
            $("#timeslot_x_data").val(timeslot.recurring_interval);
            $("#timeslot_description").val(timeslot.description);

            OB.Schedule.addeditModeChange("timeslot");
        });
    });
};

OB.Schedule.setScheduleDates = function () {
    var today = new Date();

    OB.Schedule.schedule_start = new Date();
    OB.Schedule.schedule_start.setDate(today.getDate() - today.getDay());

    OB.Schedule.schedule_start.setHours(0);
    OB.Schedule.schedule_start.setMinutes(0);
    OB.Schedule.schedule_start.setSeconds(0);

    OB.Schedule.schedule_end = new Date();
    OB.Schedule.schedule_end.setTime(OB.Schedule.schedule_start.getTime() + 604799 * 1000);

    OB.Schedule.dateRangeText();
};

OB.Schedule.nextWeek = function () {
    OB.Schedule.schedule_start.setDate(OB.Schedule.schedule_start.getDate() + 7);
    OB.Schedule.schedule_end.setDate(OB.Schedule.schedule_end.getDate() + 7);
    OB.Schedule.dateRangeText();
    OB.Schedule.loadSchedule();
};

OB.Schedule.prevWeek = function () {
    OB.Schedule.schedule_start.setDate(OB.Schedule.schedule_start.getDate() - 7);
    OB.Schedule.schedule_end.setDate(OB.Schedule.schedule_end.getDate() - 7);
    OB.Schedule.dateRangeText();
    OB.Schedule.loadSchedule();
};

OB.Schedule.dateRangeText = function () {
    $("#schedule_date_range").html(
        htmlspecialchars(OB.t(month_name(OB.Schedule.schedule_start.getMonth()))) +
            " " +
            OB.Schedule.schedule_start.getDate() +
            ", " +
            OB.Schedule.schedule_start.getFullYear() +
            " &nbsp; - &nbsp; " +
            htmlspecialchars(OB.t(month_name(OB.Schedule.schedule_end.getMonth()))) +
            " " +
            OB.Schedule.schedule_end.getDate() +
            ", " +
            OB.Schedule.schedule_end.getFullYear(),
    );

    var tmp_date = new Date(OB.Schedule.schedule_start.getTime());

    for (var count = 0; count < 7; count++) {
        $("#schedule_days_" + count).text(tmp_date.getDate());
        $("#schedule_days_" + count).attr("data-date", moment(tmp_date.getTime()).format("Y-MM-DD"));

        tmp_date.setDate(tmp_date.getDate() + 1);
    }
};

OB.Schedule.schedule_start = null;
OB.Schedule.schedule_end = null;

OB.Schedule.loadSchedule = function () {
    OB.Schedule.schedule_data = Array();

    if (OB.Schedule.schedule_mode == "timeslot") {
        var post = [];
        post.push([
            "timeslots",
            "search",
            {
                start: String(Math.round(OB.Schedule.schedule_start.getTime() / 1000)),
                end: String(Math.round(OB.Schedule.schedule_end.getTime() / 1000)),
                player: OB.Schedule.player_id,
            },
        ]);
        OB.Settings.store("timeslots-player", {
            player: OB.Schedule.player_id,
        });

        OB.API.multiPost(post, function (responses) {
            if (responses[0].status == true) {
                OB.Schedule.schedule_data = responses[0].data;
                OB.Schedule.refreshData();
            }
        });
    } else if (OB.Schedule.schedule_mode == "schedule") {
        var post = [];

        // adjust request to account for timezone discrepancies (since we're not being smart about getting the exact start/end timestamps in the player's timezone)
        // TODO this could be improved by requesting in local time (i.e., YYYY-MM-DD HH:MM:SS).
        // https://www.quora.com/Can-time-difference-between-two-places-be-greater-than-24-hours

        var schedule_request_start = String(Math.round(OB.Schedule.schedule_start.getTime() / 1000 - 60 * 60 * 26));
        var schedule_request_end = String(Math.round(OB.Schedule.schedule_end.getTime() / 1000 + 60 * 60 * 26));

        post.push([
            "shows",
            "search",
            {
                start: schedule_request_start,
                end: schedule_request_end,
                player: OB.Schedule.player_id,
            },
        ]);
        /*post.push(['shows', 'search', {'start': moment(OB.Schedule.schedule_start.getTime()).format('Y-MM-DD HH:mm:ss'), 'end': moment(OB.Schedule.schedule_end.getTime()).format('Y-MM-DD HH:mm:ss'), 'player': OB.Schedule.player_id}]);*/
        OB.Settings.store("shows-player", { player: OB.Schedule.player_id });

        OB.API.multiPost(post, function (responses) {
            if (responses[0].status == true) {
                $("#schedule_linein").toggle($("#schedule_player_select option:selected").attr("data-linein") == "1");
                OB.Schedule.schedule_data = responses[0].data;
                OB.Schedule.refreshData();
            }
        });
    }
};

OB.Schedule.refreshData = function () {
    $(".schedule_data").html("");

    // create a copy of our schedule data (since we will be manipulating it for proper display)
    var schedule_data = new Array();

    $.each(OB.Schedule.schedule_data, function (index, data) {
        let newData = new CloneObject(data);
        newData.block_date = data.exp_start.substr(0, 10);
        schedule_data.push(newData);
    });

    // split up blocks which go over midnight
    $.each(schedule_data, function (index, data) {
        var start = new Date();

        // prevent out of range problem when setting month and date invalid (i.e., no 31st)
        // https://stackoverflow.com/questions/30561576/why-is-setutcfullyear-and-setutcmonth-return-different-results
        start.setUTCDate(15);

        start.setUTCFullYear(data.exp_start.substr(0, 4));
        start.setUTCMonth(data.exp_start.substr(5, 2) - 1);
        start.setUTCDate(data.exp_start.substr(8, 2));
        start.setUTCHours(data.exp_start.substr(11, 2));
        start.setUTCMinutes(data.exp_start.substr(14, 2));
        start.setUTCSeconds(data.exp_start.substr(17, 2));

        var start_seconds_from_midnight =
            start.getUTCSeconds() + start.getUTCMinutes() * 60 + start.getUTCHours() * 3600;
        var end_seconds_from_midnight = parseInt(start_seconds_from_midnight) + parseInt(data.duration);

        // spans multiple days, need to split up.
        if (end_seconds_from_midnight > 86400) {
            data.block_offset = start_seconds_from_midnight;
            data.block_duration = 86400 - start_seconds_from_midnight;
            data.day = start.getUTCDay();

            // check if we're out of range at the beinning here (might just be an overlap into our range of interest)
            if (start.getTime() < OB.Schedule.schedule_start.getTime()) {
                data.day = null;
            }

            var seconds_over_midnight = end_seconds_from_midnight - 86400;

            var count = 1;

            while (
                seconds_over_midnight > 0 &&
                start.getTime() + count * (86400 * 1000) <= OB.Schedule.schedule_end.getTime()
            ) {
                var new_data = new CloneObject(data);

                new_data.block_offset = 0;
                new_data.block_duration = Math.min(86400, seconds_over_midnight);
                new_data.day = (start.getUTCDay() + count) % 7;
                new_data.block_date = moment(new Date(start.getTime() + 86400000).getTime()).format("Y-MM-DD");

                schedule_data.push(new_data);

                count++;
                seconds_over_midnight -= 86400;
            }
        }

        // does not span multiple days. nothing special needed.
        else {
            data.block_offset = start_seconds_from_midnight;
            data.block_duration = data.duration;
            data.day = start.getUTCDay();
        }
    });

    var interval_height = $(".schedule_time_interval").height() / 60;

    $.each(schedule_data, function (index, data) {
        if (data.day === null) return;

        // skip if date number mismatch (occurs because extra data is obtained from server due to potential client/player timezone discrepancy)
        if ($("#schedule_days_" + data.day).attr("data-date") != data.block_date) return;

        // see if our block passes through an expanded hour
        var block_duration = parseInt(data.block_duration);
        var block_offset = parseInt(data.block_offset);

        if (OB.Schedule.zoom_hour_val != null) {
            var zoom_hour_val_start = OB.Schedule.zoom_hour_val * 3600;
            var zoom_hour_val_end = zoom_hour_val_start + 3600;

            if (OB.Schedule.zoom_minute_val != null) {
                var zoom_minute_val_start = zoom_hour_val_start + OB.Schedule.zoom_minute_val * 3600;
                var zoom_minute_val_end = zoom_minute_val_start + 3600;
            } else {
                var zoom_minute_val_start = null;
                var zoom_minute_val_end = null;
            }
        } else {
            var zoom_hour_val_start = null;
            var zoom_hour_val_end = null;
            var zoom_minute_val_start = null;
            var zoom_minute_val_end = null;
        }

        // block intersects a zoomed area
        if (
            zoom_hour_val_start !== null &&
            block_offset < zoom_hour_val_end &&
            block_offset + block_duration > zoom_hour_val_start
        ) {
            // figure out how much of it overlaps, then increase the duration appropriately.
            var overlap_end = Math.min(zoom_hour_val_end, block_offset + block_duration);
            var overlap_start = Math.max(zoom_hour_val_start, block_offset);
            var overlap_amount = overlap_end - overlap_start;

            block_offset += 59 * (overlap_start - zoom_hour_val_start);
            block_duration += 59 * overlap_amount;

            if (
                zoom_minute_val_start != null &&
                block_offset < zoom_minute_val_end &&
                block_offset + block_duration > zoom_minute_val_start
            ) {
                overlap_end = Math.min(zoom_minute_val_end, block_offset + block_duration);
                overlap_start = Math.max(zoom_minute_val_start, block_offset);
                overlap_amount = overlap_end - overlap_start;

                block_offset += 59 * (overlap_start - zoom_minute_val_start);
                block_duration += 59 * overlap_amount;
            } else if (zoom_minute_val_start !== null && block_offset > zoom_minute_val_start) {
                block_offset += 212400;
            }
        }

        // otherwise, block is after a zoomed area
        else if (zoom_hour_val_start !== null && block_offset > zoom_hour_val_start) {
            block_offset += 212400;

            if (zoom_minute_val_start != null) block_offset += 212400;
        }

        if (!data.mode) var recurring = false;
        else var recurring = true;

        if (OB.Schedule.schedule_mode == "timeslot") {
            $("#schedule_data_" + data.day).append(
                '<div class="schedule_datablock" data-id="' +
                    data.exp_id +
                    '" ondblclick="clearSelection(); OB.Schedule.editTimeslotWindow(' +
                    data.id +
                    "," +
                    recurring +
                    ');" style="top: ' +
                    Math.round((block_offset / 60) * interval_height) +
                    "px; height: " +
                    Math.round((block_duration / 60) * interval_height) +
                    'px;">' +
                    htmlspecialchars(data.description) +
                    "</div>",
            );
        } else if (OB.Schedule.schedule_mode == "schedule") {
            //T You do not have permission to edit this item.
            if (
                parseInt(data.user_id) != parseInt(OB.Account.user_id) &&
                OB.Settings.permissions.indexOf("manage_timeslots") == -1
            )
                var dblclick = "OB.UI.alert('You do not have permission to edit this item.');";
            else var dblclick = "clearSelection(); OB.Schedule.editShowWindow(" + data.id + "," + recurring + ");";

            //T Line-In
            $("#schedule_data_" + data.day).append(
                '<div class="schedule_datablock" data-id="' +
                    data.exp_id +
                    '" ondblclick="' +
                    dblclick +
                    '" style="top: ' +
                    Math.round((block_offset / 60) * interval_height) +
                    "px; height: " +
                    Math.round((block_duration / 60) * interval_height) +
                    'px; overflow: hidden;">' +
                    htmlspecialchars(data.type == "linein" ? OB.t("Line-In") : data.name) +
                    "</div>",
            );
        }

        $(".schedule_datablock[data-id=" + data.exp_id + "]").data("details", data);

        $(".schedule_datablock[data-id=" + data.exp_id + "]").hover(
            function (e) {
                OB.Schedule.scheduleDetails(e, data.exp_id);
            },
            function (e) {
                $("#schedule_details").hide();
            },
        );
    });

    $(".schedule_datablock").bind("mousemove", OB.Schedule.scheduleDetailsMove);
};

OB.Schedule.scheduleDetailsMove = function (e) {
    $("#schedule_details").css("top", e.pageY);
    $("#schedule_details").css("left", e.pageX);

    var lr = e.pageX < $("#layout_main_container").width() / 2 ? "left" : "right";
    var tb = e.pageY < $("#layout_main_container").height() / 2 ? "top" : "bottom";

    if (tb == "top") $("#schedule_details").removeClass("bottom");
    else $("#schedule_details").removeClass("top");

    if (lr == "left") $("#schedule_details").removeClass("right");
    else $("#schedule_details").removeClass("left");

    $("#schedule_details").addClass(tb + " " + lr);
};

OB.Schedule.scheduleDetails = function (e, id) {
    var data = $(".schedule_datablock[data-id=" + id + "]").data("details");

    if (!data) return;

    OB.Schedule.scheduleDetailsMove(e);

    $("#schedule_details").show();

    if (OB.Schedule.schedule_mode == "schedule") {
        $("#schedule_details_name").text(data.name + " (" + data.item_type + " #" + data.item_id + ")");
        //T Scheduled By
        $("#schedule_details_user").parent().find("td:first-child").text(OB.t("Scheduled By"));
    } else {
        //T Schedule For
        $("#schedule_details_user").parent().find("td:first-child").text(OB.t("Scheduled For"));
        $("#schedule_details_name").text(data.description);
    }

    $("#schedule_details_datetime").text(data.exp_start);
    $("#schedule_details_duration").text(secsToTime(data.duration));
    $("#schedule_details_user").text(data.user);

    if (OB.Schedule.schedule_mode == "schedule" && data.item_type == "playlist") {
        if (data.description.length > 150) var truncated_description = data.description.substr(0, 150) + "...";
        else var truncated_description = data.description;

        $("#schedule_details_description").text(truncated_description);
        $("#schedule_details_description").parent().show();
    } else {
        $("#schedule_details_description").parent().hide();
    }

    if (OB.Schedule.schedule_mode == "schedule" && data.item_type != "linein") {
        $("#schedule_details_owner").text(data.owner);
        $("#schedule_details_owner").parent().show();
    } else {
        $("#schedule_details_owner").parent().hide();
    }

    if (OB.Schedule.schedule_mode == "schedule" && data.item_type == "linein") {
        //T Line-In
        $("#schedule_details_name").text(OB.t("Line-In"));
    }
};

OB.Schedule.zoom_hour_val = null;
OB.Schedule.zoom_minute_val = null;

OB.Schedule.zoomHour = function (hour) {
    OB.Schedule.zoom_minute_val = null;

    if (OB.Schedule.zoom_hour_val !== null) {
        $("#schedule_time_" + OB.Schedule.zoom_hour_val).html("");
        $(".schedule_time_minute_select").remove();
    }

    if (hour != OB.Schedule.zoom_hour_val) {
        $("#schedule_time_" + hour).append('<div id="schedule_time_' + hour + '_0"></div>');

        var display_hour = hour % 12;
        if (display_hour == 0) display_hour = 12;

        for (minute = 1; minute < 60; minute++) {
            $("#schedule_time_" + hour).append(
                '<div class="schedule_time_interval"><a href="javascript: OB.Schedule.zoomMinute(' +
                    minute +
                    ');">' +
                    display_hour +
                    ":" +
                    timepad(minute) +
                    '</a></div><div id="schedule_time_' +
                    hour +
                    "_" +
                    minute +
                    '"></div>',
            );
        }

        $("#schedule_row_" + hour + " .schedule_time_hour").append(
            ' <a class="schedule_time_minute_select" href="javascript:OB.Schedule.zoomMinute(0);">' +
                display_hour +
                ":00</a>",
        );
        OB.Schedule.zoom_hour_val = hour;
    } else {
        OB.Schedule.zoom_hour_val = null;
    }

    OB.Schedule.refreshData();
};

OB.Schedule.zoomMinute = function (minute) {
    if (OB.Schedule.zoom_hour_val === null) return;

    if (OB.Schedule.zoom_minute_val !== null) {
        $("#schedule_time_" + OB.Schedule.zoom_hour_val + "_" + OB.Schedule.zoom_minute_val).html("");
    }
    if (minute != OB.Schedule.zoom_minute_val) {
        var display_hour = OB.Schedule.zoom_hour_val % 12;
        if (display_hour == 0) display_hour = 12;

        for (second = 1; second < 60; second++) {
            $("#schedule_time_" + OB.Schedule.zoom_hour_val + "_" + minute).append(
                '<div class="schedule_time_interval" id="schedule_time_' +
                    OB.Schedule.zoom_hour_val +
                    "_" +
                    minute +
                    "_" +
                    second +
                    '">' +
                    display_hour +
                    ":" +
                    timepad(minute) +
                    ":" +
                    timepad(second) +
                    "</div>",
            );
        }

        OB.Schedule.zoom_minute_val = minute;
    } else {
        OB.Schedule.zoom_minute_val = null;
    }

    OB.Schedule.refreshData();
};
