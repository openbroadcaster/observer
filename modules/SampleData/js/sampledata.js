// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

OBModules.SampleData = new Object();

OBModules.SampleData.init = function () {
    OB.Callbacks.add('ready', 0, OBModules.SampleData.initMenu);
};

OBModules.SampleData.initMenu = function () {
    OB.UI.addSubMenuItem('admin', 'Import Sample Data', 'import_sample_data', OBModules.SampleData.importPage, 110, 'import_sample_data');
};

OBModules.SampleData.importPage = async function () {
    // Cache key preserves the module dir's actual case (core/controllers/UI.php
    // find_module_html_files) — must be 'SampleData', not 'sampledata'.
    OB.UI.replaceMain('modules/SampleData/sampledata.html');

    OBModules.SampleData.profiles = {};
    $('#sampledata_module-profile').html('<option value="">Loading…</option>');
    $('#sampledata_module-import').prop('disabled', true);
    $('#sampledata_module-log').hide().text('');

    const profiles = await OB.API.request({ endpoint: 'module/SampleData/profiles', method: 'GET' });
    var $select = $('#sampledata_module-profile').empty();

    if (!profiles || profiles.length === 0) {
        $select.append('<option value="">No profiles found</option>');
        $('#sampledata_module-info').text('No sample data profiles found.');
        return;
    }

    $.each(profiles, function (_, profile) {
        OBModules.SampleData.profiles[profile.directory] = profile;
        $select.append($('<option></option>').val(profile.directory).text(profile.name));
    });

    OBModules.SampleData.updateDescription();
    $('#sampledata_module-import').prop('disabled', false);

    $('#sampledata_module-profile').off('change').on('change', OBModules.SampleData.updateDescription);
    $('#sampledata_module-import').off('click').on('click', function () { OBModules.SampleData.runImport(false); });
};

OBModules.SampleData.updateDescription = function () {
    var profile = OBModules.SampleData.profiles[$('#sampledata_module-profile').val()];
    $('#sampledata_module-description').text(profile ? (profile.description || '') : '');
};

OBModules.SampleData.runImport = function (confirmed) {
    var dir = $('#sampledata_module-profile').val();
    if (!dir) return;

    if (!confirmed) {
        OB.UI.confirm(
            'Import sample data profile "' + dir + '"?\n\nThis will create permission groups, users, playlists, a sample player and schedule, and apply settings. The action is idempotent — existing items are skipped — but it cannot be undone.',
            function () { OBModules.SampleData.runImport(true); },
            'Yes, Import', 'No, Cancel', 'delete'
        );
        return;
    }

    $('#sampledata_module-info').text('Importing… (first run downloads ~73MB of media; this can take a minute)');
    $('#sampledata_module-import').prop('disabled', true);
    $('#sampledata_module-log').show().text('');

    OB.API.request({ endpoint: 'module/SampleData/run', method: 'POST', data: { profile: dir } }).then(function (result) {
        $('#sampledata_module-import').prop('disabled', false);
        if (!result) {
            $('#sampledata_module-info').text('Import failed: no response from server.');
            return;
        }
        $('#sampledata_module-log').text((result.log || []).join('\n'));
        $('#sampledata_module-info').text(result.success ? 'Sample data imported.' : 'Import failed: ' + (result.error || 'unknown error'));
    });
};
