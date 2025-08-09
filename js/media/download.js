// Copyright 2012-2024 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OB.Media.download = function (id, version) {
    if (version) {
        OB.API.download("downloads/media/" + id + "/version/" + version);
    } else {
        OB.API.download("downloads/media/" + id);
    }
};
