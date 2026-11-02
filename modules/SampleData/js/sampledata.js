// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OBModules.SampleData = new Object();

OBModules.SampleData.init = function () {
    OB.Callbacks.add("ready", 0, OBModules.SampleData.initMenu);
};

OBModules.SampleData.initMenu = function () {
    //T Import Sample Data
    OB.UI.addSubMenuItem(
        "admin",
        "Import Sample Data",
        "import_sample_data",
        OBModules.SampleData.importPage,
        50,
        "import_sample_data",
    );
};

// a bad status makes OB.API.request return false, but a dropped connection makes it throw.
OBModules.SampleData.request = async function (options) {
    try {
        return await OB.API.request(options);
    } catch (error) {
        return false;
    }
};

OBModules.SampleData.importPage = async function () {
    OB.UI.replaceMain("modules/SampleData/sampledata.html");

    OBModules.SampleData.profiles = {};

    const profiles = await OBModules.SampleData.request({ endpoint: "module/SampleData/profiles", method: "GET" });
    var $select = $("#sampledata_module-profile");

    if (!profiles || profiles.length === 0) {
        //T Couldn't load the sample data profiles. Reload the page to try again.
        $("#sampledata_module-info").text(
            OB.t("Couldn't load the sample data profiles. Reload the page to try again."),
        );
        return;
    }

    $.each(profiles, function (_, profile) {
        OBModules.SampleData.profiles[profile.directory] = profile;
        $select.append($("<option></option>").val(profile.directory).text(profile.name));
    });

    $select.on("change", OBModules.SampleData.check);
    $("#sampledata_module-import").on("click", OBModules.SampleData.start);
    OBModules.SampleData.check();
};

OBModules.SampleData.check = async function () {
    var dir = $("#sampledata_module-profile").val();
    $("#sampledata_module-description").text(OBModules.SampleData.profiles[dir].description);
    $("#sampledata_module-import").prop("hidden", true);

    const preflight = await OBModules.SampleData.request({
        endpoint: "module/SampleData/preflight?profile=" + encodeURIComponent(dir),
        method: "GET",
    });
    OBModules.SampleData.preflight = preflight;

    if (!preflight) {
        //T Couldn't check this install. Reload the page to try again.
        $("#sampledata_module-info").text(OB.t("Couldn't check this install. Reload the page to try again."));
        return;
    }
    if (preflight.problem) {
        //T Can't import: %1
        $("#sampledata_module-info").text(OB.t("Can't import: %1", OB.t(preflight.problem)));
        return;
    }
    //T No other content found on this install. Ready to import.
    var info = OB.t("No other content found on this install. Ready to import.");
    if (!preflight.fresh) {
        //T This install already has its own content (%1). Importing adds the sample station and any file formats it needs. Your own media, playlists and players stay as they are.
        info = OB.t(
            "This install already has its own content (%1). Importing adds the sample station and any file formats it needs. Your own media, playlists and players stay as they are.",
            Object.entries(preflight.existing)
                .map(([label, count]) => OB.t(label, count))
                .join(", "),
        );
    }
    $("#sampledata_module-info").text(info);
    $("#sampledata_module-import").prop("hidden", false);
};

OBModules.SampleData.start = function () {
    var preflight = OBModules.SampleData.preflight;
    //T Import this sample station? It downloads up to %1 MB of media.
    //T This install already has its own content. Add the sample station anyway? It downloads up to %1 MB of media.
    OB.UI.confirm(
        OB.t(
            preflight.fresh
                ? "Import this sample station? It downloads up to %1 MB of media."
                : "This install already has its own content. Add the sample station anyway? It downloads up to %1 MB of media.",
            preflight.download_mb,
        ),
        function () {
            OBModules.SampleData.run(!preflight.fresh);
        },
    );
};

OBModules.SampleData.run = async function (confirmed) {
    var dir = $("#sampledata_module-profile").val();
    var steps = OBModules.SampleData.preflight.steps;
    var $log = $("#sampledata_module-log").prop("hidden", false).text("");
    $("#sampledata_module-import").prop("hidden", true);
    $("#sampledata_module-profile").prop("disabled", true);
    $("#sampledata_module-player").prop("hidden", true);
    $("#sampledata_module-message").obWidget("hide");

    for (const [index, step] of steps.entries()) {
        var offset = 0;
        do {
            //T Step %1 of %2…
            $("#sampledata_module-info").text(OB.t("Step %1 of %2…", index + 1, steps.length));
            const result = await OBModules.SampleData.request({
                endpoint: "module/SampleData/step",
                method: "POST",
                data: { profile: dir, step: step, offset: offset, confirmed: confirmed },
            });
            if (!result || result.error) {
                //T no response from the server.
                //T Stopped: %1 You can press Import again to carry on.
                $("#sampledata_module-message").obWidget(
                    "error",
                    OB.t(
                        "Stopped: %1 You can press Import again to carry on.",
                        result ? OB.t(result.error) : OB.t("no response from the server."),
                    ),
                );
                OBModules.SampleData.finish();
                return;
            }
            if (result.log.length) {
                $log.append(document.createTextNode(result.log.join("\n") + "\n"));
            }
            if (result.details.player_password) {
                $("#sampledata_module-player-id").text(result.details.player_id);
                $("#sampledata_module-player-password").text(result.details.player_password);
                $("#sampledata_module-player").prop("hidden", false);
            }
            if (result.details.busy) {
                await new Promise((resolve) => setTimeout(resolve, 5000));
            }
            offset = result.next_offset;
        } while (offset !== null);
    }

    //T Sample data imported.
    $("#sampledata_module-message").obWidget("success", OB.t("Sample data imported."));
    OBModules.SampleData.finish();
};

// new media, playlists, categories and custom fields should show up without a page reload.
OBModules.SampleData.finish = function () {
    $("#sampledata_module-info").text("");
    $("#sampledata_module-import").prop("hidden", false);
    $("#sampledata_module-profile").prop("disabled", false);
    OB.Settings.getSettings();
    OB.Sidebar.mediaSearch();
    OB.Sidebar.playlistSearch();
};
