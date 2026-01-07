// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Dayparting = {};

OB.Dayparting.load = function () {
    //T Loading
    $("#dayparting_table tbody").html("<tr><td><span data-t>Loading</span>...</td></tr>");

    OB.API.post("dayparting", "search", {}, function (response) {
        if (!response.status) return;

        $("#dayparting_table tbody").html("");

        $.each(response.data, function (index, row) {
            var $tr = $("<tr></td>");
            $tr.append($("<td></td>").text(row["description"]));

            var range = "";
            if ((row["start"] ?? "").match(/^\d+$/)) {
                var date = new Date(2021, 0);
                date.setDate(parseInt(row["start"]) + 1);
                range += moment(date).format("MMM Do");
                range += " - ";

                date = new Date(2021, 0);
                date.setDate(parseInt(row["end"]) + 1);
                range += moment(date).format("MMM Do");
            } else if (row["start"]) {
                range += row["start"] + " - " + row["end"];
            } else {
                range += (row["dow"] ?? "").split(",").join(", ");
            }
            $tr.append($("<td></td>").text(range));

            $tr.append($("<td></td>").html(OB.Dayparting.queryDescription($.parseJSON(row["filters"]))));

            //T Edit
            $tr.append(
                $("<td></td>").append(
                    $(
                        '<ob-element-button data-style="edit" data-text="Edit" data-icon-name="pen" data-icon-style="solid" data-t>Edit</ob-element-button>',
                    ).click(function () {
                        OB.Dayparting.open(row["id"]);
                    }),
                ),
            );
            $("#dayparting_table tbody").append($tr);
        });

        if (!response.data.length) {
            //T No items found.
            $("#dayparting_table tbody").html("<tr><td><span data-t>No items found.</span></td></tr>");
        }
    });
};

OB.Dayparting.new = function () {
    OB.UI.openModalWindow("media/dayparting_addedit.html");

    $("#dayparting_type")
        .change(function () {
            $("#dayparting_dates").toggle($(this).val() == "date");
            $("#dayparting_dow").toggle($(this).val() == "dow");
            $("#dayparting_times").toggle($(this).val() == "time");
        })
        .change();

    var query = OB.Sidebar.media_search_last_query;
    $("#dayparting_filter").val($.toJSON(query));
    $("#dayparting_query").html(OB.Dayparting.queryDescription(query));
};

OB.Dayparting.queryDescription = function (query) {
    if (query.mode == "advanced") {
        var description = "";
        $.each(query.filters, function (index, filter) {
            description += htmlspecialchars(filter.description) + "<br>";
        });
    } else if (query.string == "") {
        //T All Media Search
        var description = "<span data-t>All Media Search</span>";
    } else {
        //T Standard Search
        var description = htmlspecialchars('"' + query.string + '"');
    }

    return description;
};

OB.Dayparting.open = function (id) {
    OB.API.post("dayparting", "get", { id: id }, function (response) {
        OB.Dayparting.new();

        // convert day of year to date
        if ((response.data["start"] ?? "").match(/^\d+$/)) {
            var date = new Date(2021, 0);
            date.setDate(parseInt(response.data["start"]) + 1);
            response.data["start"] = date.format("yyyy-mm-dd");

            date = new Date(2021, 0);
            date.setDate(parseInt(response.data["end"]) + 1);
            response.data["end"] = date.format("yyyy-mm-dd");
        }

        $("#dayparting_form").val(response.data);
        if (response.data["type"] == "date") {
            $("#dayparting_times input").val("");
        } else if (response.data["type"] == "time") {
            $("#dayparting_dates input").val("");
        }
        $("#dayparting_delete").show();
        $("#dayparting_query").html(OB.Dayparting.queryDescription($.parseJSON($("#dayparting_filter").val())));
    });
};

OB.Dayparting.save = function () {
    var data = $("#dayparting_form").val();

    OB.API.post("dayparting", "save", data, function (response) {
        if (response.status == true) {
            OB.UI.closeModalWindow();
            OB.Dayparting.load();
        } else {
            $("#dayparting_message").obWidget("error", response.msg);
        }
    });
};

OB.Dayparting.delete = function (confirm) {
    if (confirm) {
        OB.API.post("dayparting", "delete", { id: $("#dayparting_form [name=id]").val() }, function (response) {
            OB.UI.closeModalWindow();
            OB.Dayparting.load();
        });
    } else {
        //T Are you sure you want to delete this item?
        //T Yes, Delete
        //T No, Cancel
        OB.UI.confirm(
            "Are you sure you want to delete this item?",
            function () {
                OB.Dayparting.delete(true);
            },
            "Yes, Delete",
            "No, Cancel",
            "delete",
        );
    }
};
