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
