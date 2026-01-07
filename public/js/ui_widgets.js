// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.UI.Widgets = new Object();

OB.UI.widgetHTML = function ($elements) {
    // TODO some way of better dealing with widget code. (widgets.js?)
    $elements.find("obwidget").each(function (index, element) {
        var attributes = $(element).getAttributes();

        // we require an ID on widgets.
        // if(!attributes.id) return;

        // deal with widget type "message".
        if ($(element).attr("type") == "message") {
            delete attributes["type"];

            $div = $("<div></div>");

            $.each(attributes, function (attribute, value) {
                $div.attr(attribute, value);
            });

            $div.addClass("obwidget");
            $div.addClass("message");
            $div.addClass("hidden");

            $div.attr("data-type", "message");

            $(element).replaceWith($div);
        }
    });
};

OB.UI.Widgets.message = function ($element, type, ...message) {
    // validate args.
    if (!type) return false;
    if (type != "hide" && !message.length) return false;
    if ($.inArray(type, ["hide", "info", "warning", "error", "success"]) < 0) return;

    if (type == "hide") $element.hide();
    else {
        $element.removeClass("info");
        $element.removeClass("success");
        $element.removeClass("warning");
        $element.removeClass("error");

        $element.addClass(type);

        $element.text(OB.t(...message));
        $element.show();

        OB.UI.scrollIntoView($element);
    }

    return true;
};

$.fn.obWidget = function (...args) {
    if (!this.hasClass("obwidget") || !this.attr("data-type") || !OB.UI.Widgets[this.attr("data-type")]) return false;
    return OB.UI.Widgets[this.attr("data-type")](this, ...args);
};
