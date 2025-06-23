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

OB.Alert = new Object();

OB.Alert.init = function () {
    OB.Callbacks.add("ready", -4, OB.Alert.initMenu);
};

OB.Alert.initMenu = function () {
    //T Alerts
    OB.UI.addSubMenuItem("schedules", "Alerts", "alert", OB.Alert.alert, 30, "manage_alerts");
};

OB.Alert.player_id = null;

OB.Alert.alert = function () {
    OB.UI.replaceMain("alert/alert.html");

    OB.Alert.player_id = null;

    OB.Alert.alertInit();

    $("#alert_list_container").droppable({
        drop: function (event, ui) {
            if ($(ui.draggable).attr("data-mode") == "media") {
                //T You can schedule only one item at a time.
                if ($(".sidebar_search_media_selected").length != 1) {
                    OB.UI.alert("You can schedule only one item at a time.");
                    return;
                }

                var item_id = $(".sidebar_search_media_selected").first().attr("data-id");
                var item_name =
                    $(".sidebar_search_media_selected").first().attr("data-artist") +
                    " - " +
                    $(".sidebar_search_media_selected").first().attr("data-title");
                var item_type = $(".sidebar_search_media_selected").first().attr("data-type");

                var item_duration = $(".sidebar_search_media_selected").first().attr("data-duration");

                OB.Alert.addAlert(item_id, item_name, item_type);
            } else if ($(ui.draggable).attr("data-mode") == "playlist") {
                //T Alert playlists are not supported at this time.
                OB.UI.alert("Alert playlists are not supported at this time.");
            }
        },
    });
};

OB.Alert.alertInit = function () {
    var post = [];
    post.push(["players", "search", {}]);

    OB.API.multiPost(post, function (responses) {
        var players = responses[0].data;
        var last_player = OB.Settings.store("alerts-player");

        $.each(players, function (index, item) {
            if (item.use_parent_alert == "1") return; // player uses parent alerts, setting them here would not do anything.

            // make sure we have permission for this
            if (
                OB.Settings.permissions.indexOf("manage_alerts") == -1 &&
                OB.Settings.permissions.indexOf("manage_alerts:" + item.id) == -1
            )
                return;

            if (OB.Alert.player_id == null) OB.Alert.player_id = item.id; // default to first player.
            $("#alert_player_select").append(
                '<option value="' + item.id + '">' + htmlspecialchars(item.name) + "</option>",
            );
        });

        if (
            typeof last_player !== "undefined" &&
            $("#alert_player_select option[value=" + last_player.player + "]").length
        ) {
            $("#alert_player_select").val(last_player.player);
            OB.Alert.player_id = last_player.player;
        }

        OB.Alert.loadAlerts();
    });
};

OB.Alert.playerChange = function () {
    OB.Alert.player_id = $("#alert_player_select").val();
    OB.Alert.loadAlerts();
};

OB.Alert.loadAlerts = function () {
    var post = [];
    post.push(["alerts", "search", { player_id: OB.Alert.player_id }]);
    OB.Settings.store("alerts-player", { player: OB.Alert.player_id });

    OB.API.multiPost(post, function (responses) {
        if (responses[0].status == true) {
            var alerts = responses[0].data;

            $("#alert_list tbody").children().not("#alert_table_empty").remove();

            if ($(alerts).length > 0) {
                $("#alert_table_empty").hide();

                $.each(alerts, function (index, data) {
                    if (data.duration) var duration = Math.round(data.duration) + " seconds";
                    else var duration = "";

                    $("#alert_list tbody").append(
                        '<tr id="alert_' +
                            data.id +
                            '"><td>' +
                            htmlspecialchars(data.name) +
                            "</td></td><td>" +
                            format_timestamp(data.start) +
                            "</td><td>" +
                            format_timestamp(data.stop) +
                            "</td><td>" +
                            data.frequency +
                            " " +
                            OB.t("seconds") +
                            "</td><td>" +
                            secsToTime(data.duration) +
                            "</td><td>" +
                            htmlspecialchars(data.item_name) +
                            "</td>",
                    );

                    $("#alert_" + data.id).dblclick(function (eventObj) {
                        OB.Alert.editAlert(data.id);
                    });
                });
            } else $("#alert_table_empty").show();
        }
    });
};

OB.Alert.saveAlert = function () {
    fields = new Object();

    fields.name = $("#alert_name").val();

    fields.player_id = OB.Alert.player_id;

    fields.frequency = $("#alert_frequency").val();
    fields.duration = parseInt($("#alert_duration_minutes").val() * 60) + parseInt($("#alert_duration_seconds").val());

    var start_date = new Date($("#alert_start_datetime").val());
    if (!start_date) fields.start = "";
    else fields.start = Math.round(start_date.getTime() / 1000) + "";

    var stop_date = new Date($("#alert_stop_datetime").val());
    if (!stop_date) fields.stop = "";
    else fields.stop = Math.round(stop_date.getTime() / 1000) + "";

    fields.id = $("#alert_id").val();
    fields.item_id = document.querySelector("#alert_item_id").value[0];

    fields.mode = document.querySelector("#alert_mode").value;
    fields.properties = {
        voicetrack_volume: document.querySelector("#alert_voicetrack_volume").value,
        voicetrack_fadeout_before: document.querySelector("#alert_voicetrack_fadeout_before").value,
        voicetrack_fadein_after: document.querySelector("#alert_voicetrack_fadein_after").value,
    };

    OB.API.post("alerts", "save", fields, function (data) {
        if (data.status == true) {
            OB.UI.closeModalWindow();
            OB.Alert.loadAlerts();
        } else {
            $("#alert_addedit_message").obWidget("error", data.msg);
        }
    });
};

OB.Alert.addeditAlertWindow = function () {
    OB.UI.openModalWindow("alert/addedit.html");
};

OB.Alert.editAlert = function (id) {
    OB.API.post("alerts", "get", { id: id }, function (data) {
        if (data.status == true) {
            emerg = data.data;

            OB.Alert.addeditAlertWindow();
            $(".edit_only").show();

            if (emerg.item_type == "image") {
                $("#alert_duration").show();

                var duration = Math.round(emerg.duration);
                var duration_seconds = duration % 60;
                var duration_minutes = (duration - duration_seconds) / 60;

                $("#alert_duration_minutes").val(duration_minutes);
                $("#alert_duration_seconds").val(duration_seconds);
            } else $("#alert_duration").hide();

            $("#alert_name").val(emerg.name);
            $("#alert_frequency").val(emerg.frequency);
            document.querySelector("#alert_item_id").value = [emerg.item_id];
            $("#alert_id").val(emerg.id);

            $("#alert_start_datetime").val(new Date(parseInt(emerg.start) * 1000));
            $("#alert_stop_datetime").val(new Date(parseInt(emerg.stop) * 1000));

            document.querySelector("#alert_mode").value = emerg.mode;
            if (emerg.properties) {
                document.querySelector("#alert_voicetrack_volume").value = emerg.properties.voicetrack_volume;
                document.querySelector("#alert_voicetrack_offset").value = emerg.properties.voicetrack_offset;
                document.querySelector("#alert_voicetrack_fadeout_before").value =
                    emerg.properties.voicetrack_fadeout_before;
                document.querySelector("#alert_voicetrack_fadein_after").value =
                    emerg.properties.voicetrack_fadein_after;
            }
        } else OB.UI.alert(data.msg);
    });
};

OB.Alert.addAlert = function (item_id, item_name, item_type) {
    OB.Alert.addeditAlertWindow();
    $(".edit_only").hide();

    document.querySelector("#alert_item_id").value = [item_id];

    if (item_type == "image") $("#alert_duration").show();
    else $("#alert_duration").hide();
};

OB.Alert.deleteAlert = function (confirm) {
    if (confirm) {
        OB.API.post("alerts", "delete", { id: $("#alert_id").val() }, function (data) {
            if (data.status == true) {
                OB.UI.closeModalWindow();
                OB.Alert.loadAlerts();
            } else {
                $("#alert_addedit_message").obWidget("error", data.msg);
            }
        });
    } else {
        //T Are you sure you want to delete this alert?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Are you sure you want to delete this alert?",
            function () {
                OB.Alert.deleteAlert(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    }
};

OB.Alert.changeMode = function () {
    if (document.querySelector("#alert_mode").value === "voicetrack") {
        document.querySelectorAll(".alert_mode_voicetrack").forEach((node) => (node.style.display = "flex"));
    } else {
        document.querySelectorAll(".alert_mode_voicetrack").forEach((node) => (node.style.display = "none"));
    }
};
