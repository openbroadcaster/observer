// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Callbacks = new Object();

OB.Callbacks.callbacks = new Object();

OB.Callbacks.callall = function (name) {
    // do we have any callbacks?
    if (typeof OB.Callbacks.callbacks[name] != "object") return;

    // order our callbacks appropriately
    OB.Callbacks.callbacks[name].sort(function (a, b) {
        return a.order - b.order;
    });

    // run our callbacks
    $.each(OB.Callbacks.callbacks[name], function (index, callback) {
        callback.func();
    });
};

OB.Callbacks.add = function (name, order, func) {
    if (typeof OB.Callbacks.callbacks[name] == "undefined") OB.Callbacks.callbacks[name] = new Array();

    OB.Callbacks.callbacks[name].push({ order: order, func: func });
};
