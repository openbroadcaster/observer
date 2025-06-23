<?php

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

$DEBUG = true;

require_once('../../components.php');

if (php_sapi_name()!='cli') {
    die('Command line tool only.');
}

if (!defined('OB_SUBSONIC_URL') || !defined('OB_SUBSONIC_USER') || !defined('OB_SUBSONIC_HASH') || !defined('OB_SUBSONIC_SALT')) {
    echo 'The following constants must be defined in config.php:

    OB_SUBSONIC_URL : API URL (http://subsonic.example.com/rest/)
    OB_SUBSONIC_USER: username
    OB_SUBSONIC_HASH: md5(password+salt)
    OB_SUBSONIC_SALT: salt used for hash

See http://www.subsonic.org/pages/api.jsp for more information regarding authentication information.
';
    die();
}

if (!is_writable(OB_ASSETS .'/uploads')) {
    exit("Uploads directory isn't writable.");
}

$db = OBFDB::get_instance();
$models = OBFModels::get_instance();

function subsonic_request($path, $args=[], $decode=true)
{
    $auth = [
    'u'=>OB_SUBSONIC_USER,
    't'=>OB_SUBSONIC_HASH,
    's'=>OB_SUBSONIC_SALT,
    'v'=>'1.16.1',
    'c'=>'obimport',
    'f'=>'json'
  ];
    $response = file_get_contents(OB_SUBSONIC_URL.$path.'?'.http_build_query($auth + $args));
    if ($decode) {
        $response = json_decode($response, true);
    }
    return $response;
}

/* Check the settings table for required metadata. Because we're using custom fields,
it's important that none of the usual fields are required, just in case the imported
data is missing some. */
$db->where('name', 'core_metadata');
$fields = json_decode($db->get_one('settings')['value'], true);
foreach ($fields as $field => $value) {
    if ($value == 'required') {
        die('Required metadata fields can cause problems when importing from Subsonic. Please update media metadata settings to set any required fields to enabled.');
    }
}

/* Create columns for Subsonic metadata fields if they don't exist yet. */
$subsonic_meta_fields = ['id', 'parent', 'track', 'genre', 'coverart', 'size', 'contenttype', 'suffix',
  'duration', 'bitrate', 'path', 'playcount', 'discnumber', 'created', 'albumid', 'type'];
foreach ($subsonic_meta_fields as $meta_field) {
    $db->where('name', 'subsonic_' . $meta_field);
    if (!$db->get_one('media_metadata')) {
        $models->mediametadata('save', [
      'name' => 'subsonic_' . $meta_field,
      'description' => 'Subsonic ' . $meta_field,
      'type' => 'text',
      'default' => ''
    ], null);
    }
}

// auth check
$ping = subsonic_request('ping.view');
if (empty($ping['subsonic-response']['status']) || $ping['subsonic-response']['status']!='ok') {
    die('Authorization failure or unexpected response from Subsonic API.'.PHP_EOL);
}

/* Check if we already have a remaining artists file. If so, we don't need to spend
all that time waiting on the API again, and can just continue where we left off. */
if (file_exists('tools/subsonic/artists_remaining.json')) {
    echo "Getting artists from artists_remaining.json.\n";
    $artists = json_decode(file_get_contents('tools/subsonic/artists_remaining.json'), true);
    if (!$artists) {
        die('Remaining artists file exists but could not be decoded, and is possibly corrupted. Please delete artists_remaining.json and try again.');
    }
} else {
    /* Artists are returned indexed from A-Z. To make it easier to go through them,
    we'll iterate through all the indices, adding all artists (and some metadata, if
    available) to our own artists array. */
    echo "Getting artists from Subsonic API.\n";
    $response = subsonic_request('getIndexes');
    if ($response['subsonic-response']['status'] != 'ok') {
        die('Failed to get list of artists from API.');
    }

    $artists = [];
    foreach ($response['subsonic-response']['indexes']['index'] as $index) {
        foreach ($index['artist'] as $artist) {
            $artists[] = $artist;
            /* Each artist has the following fields set:
              'id',
              'name',
              'artistImageUrl' OPTIONAL
            */
        }
    }

    /* Limit number of artists when in debug mode to avoid having too much data to
    handle. */
    if ($DEBUG) {
        $artists = array_slice($artists, 0, 2);
    }
    echo "Retrieved artists from Subsonic API: " . count($artists) . "\n";

    /* Save artists array to a file. If the script gets interrupted, we can then
    reload the file the next time and will be able to skip any artists we've already
    imported. */
    file_put_contents('tools/subsonic/artists_remaining.json', json_encode($artists));
}

/* For each artist, retrieve a listing of all media files. Make sure to process them
piecewise before moving on, so as to avoid blowing up the amount of data we're
dealing with at any one time. */
function get_all_media($directory_id)
{
    // echo 'dir id: ' . $directory_id . PHP_EOL;
    $media = [];

    $response = subsonic_request('getMusicDirectory', ['id' => $directory_id]);
    if ($response['subsonic-response']['status'] != 'ok') {
        die('Failed to get music directory from API.');
    }
    $items = $response['subsonic-response']['directory']['child'] ?? [];
    // echo 'items found: ' . count($items) . PHP_EOL;

    foreach ($items as $item) {
        if ($item['isDir']) {
            $media = array_merge($media, get_all_media($item['id']));
        } else {
            $media[] = $item;
        }
    }

    return $media;
}

$total_size = 0;
$artists_processed = 0;
$skipped = [];
foreach ($artists as $key => $artist) {
    $media = get_all_media($artist['id']);

    foreach ($media as $media_item) {
        if (!isset($media_item['size'])) {
            echo "Corrupted media file on Subsonic server. Skipping.\n";
            $skipped[] = $media_item;
            file_put_contents('tools/subsonic/item_skipped.json', json_encode($skipped));
            continue;
        }

        /* Check the media table for the Subsonic ID. If it's already in there, it means
        we already imported the media file a previous time, so we can skip this one. */
        $db->where('metadata_subsonic_id', $media_item['id']);
        $exists = $db->get('media');
        if ($exists) {
            echo "Subsonic ID found in table. Media file already imported. Skipping.\n";
            $skipped[] = $media_item;
            file_put_contents('tools/subsonic/item_skipped.json', json_encode($skipped));
            continue;
        }

        /* Add row to uploads table, download the file from Subsonic and put it in the
        right directory. */
        $file_id = $db->insert('uploads', [
      'key'    => bin2hex(openssl_random_pseudo_bytes(16)),
      'expiry' => time() + 86400
    ]);

        $file = subsonic_request('download', ['id' => $media_item['id']], false);
        file_put_contents(OB_ASSETS . '/uploads/' . $file_id, $file);

        $info = $models->media('media_info', ['filename' => OB_ASSETS . '/uploads/' . $file_id]);
        $db->where('id', $file_id);
        $db->update('uploads', [
      'type' => $info['type'] ?? null,
      'duration' => $info['duration'] ?? null,
      'format' => $info['format'] ?? null
    ]);

        /* Validate media item and finally save it to the media table, including as
        much of the Subsonic metadata as possible. */
        $item = [
      'file_id'   => $file_id,
      'file_info' => $info,
      'title'     => $media_item['title'],
      'status'    => 'visible',
      'owner_id'  => 1,
      'local_id'  => 1,
      'is_copyright_owner' => 0,
      'is_approved' => 1,
      'dynamic_select' => 1,
      'metadata_subsonic_id' => $media_item['id']
    ];

        if (!empty($media_item['year'])) {
            $item['year'] = $media_item['year'];
        }
        if (isset($media_item['artist'])) {
            $item['artist'] = $media_item['artist'];
        }
        if (isset($media_item['album'])) {
            $item['album'] = $media_item['album'];
        }

        if (isset($media_item['parent'])) {
            $item['metadata_subsonic_parent'] = $media_item['parent'];
        }
        if (isset($media_item['track'])) {
            $item['metadata_subsonic_track'] = $media_item['track'];
        }
        if (isset($media_item['genre'])) {
            $item['metadata_subsonic_genre'] = $media_item['genre'];
        }
        if (isset($media_item['coverArt'])) {
            $item['metadata_subsonic_coverart'] = $media_item['coverArt'];
        }
        if (isset($media_item['size'])) {
            $item['metadata_subsonic_size'] = $media_item['size'];
        }
        if (isset($media_item['contentType'])) {
            $item['metadata_subsonic_contenttype'] = $media_item['contentType'];
        }
        if (isset($media_item['suffix'])) {
            $item['metadata_subsonic_suffix'] = $media_item['suffix'];
        }
        if (isset($media_item['duration'])) {
            $item['metadata_subsonic_duration'] = $media_item['duration'];
        }
        if (isset($media_item['bitRate'])) {
            $item['metadata_subsonic_bitrate'] = $media_item['bitRate'];
        }
        if (isset($media_item['path'])) {
            $item['metadata_subsonic_path'] = $media_item['path'];
        }
        if (isset($media_item['playCount'])) {
            $item['metadata_subsonic_playcount'] = $media_item['playCount'];
        }
        if (isset($media_item['discNumber'])) {
            $item['metadata_subsonic_discnumber'] = $media_item['discNumber'];
        }
        if (isset($media_item['created'])) {
            $item['metadata_subsonic_created'] = $media_item['created'];
        }
        if (isset($media_item['albumId'])) {
            $item['metadata_subsonic_albumid'] = $media_item['albumId'];
        }
        if (isset($media_item['type'])) {
            $item['metadata_subsonic_type'] = $media_item['type'];
        }

        $valid = $models->media('validate', ['item' => $item]);
        if (!$valid[0]) {
            echo "Media item failed validation check. Skipping.\n";
            $skipped[] = $media_item;
            file_put_contents('tools/subsonic/item_skipped.json', json_encode($skipped));
        }

        $media_id = $models->media('save', ['item' => $item]);

        $total_size += $media_item['size'];
    }

    $artists_processed += 1;
    if ($artists_processed % 50 == 0) {
        echo "Artists processed: " . $artists_processed . ".\n";
    }

    unset($artists[$key]);
    if (!$DEBUG) {
        file_put_contents('tools/subsonic/artists_remaining.json', json_encode($artists));
    }
}
if (!$DEBUG) {
    unlink('tools/subsonic/artists_remaining.json');
}

echo "Total size of media imported: " . ($total_size / 1000000) . "MB.\n";
