// Copyright 2012-2025 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

// This is the first js file to be outputted.

OB = new Object();
OBModules = new Object();

$(document).ready(function () {
    $.each(OB, function (name, item) {
        if (typeof OB[name].init == "function") OB[name].init();
    });

    $.each(OBModules, function (name, item) {
        if (typeof OBModules[name].init == "function") OBModules[name].init();
    });

    OB.Callbacks.callall("ready");
});

jQuery.fn.showFlex = function () {
    $(this).each(function (index, element) {
        $(element).css("display", "flex");
        if ($(element).css("display") != "flex") $(element).css("display", "-webkit-flex");
    });
};
