// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Layout = new Object();

OB.Layout.init = function () {
    OB.Callbacks.add("ready", -40, OB.Layout.layoutInit);
};

OB.Layout.layoutInit = function () {
    OB.Layout.home();
};

OB.Layout.home = function () {
    OB.UI.replaceMain("main.html");

    OB.API.post("clientsettings", "get_welcome_page", {}, function (response) {
        if (response.status) {
            $("#main_page_content").html(response.data);
        } else {
            $("#main_page_content").text("Welcome to OpenBroadcaster.");
        }
    });
};

OB.Layout.tableFixedHeaders = function ($headers, $table) {
    $headers.width($table.width());

    $headers.find("th:visible").each(function (index, element) {
        if (!$(element).attr("data-column")) return;

        $column = $table.find("td:visible[data-column=" + $(element).attr("data-column") + "]").first();
        if (!$column.length) return;

        // wrap out table heading if <div> so we can have it cut off if too long.
        if (!$(element).find("div").length)
            $(element).html(
                '<div style="overflow: hidden; white-space: nowrap; width: ' +
                    $column.width() +
                    'px;">' +
                    $(element).html() +
                    "</div>",
            );

        $(element).width($column.width());
    });
};
