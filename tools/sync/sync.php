<?php

/*
    Copyright 2012-2021 OpenBroadcaster, Inc.

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

// required: apt install libchromaprint-tools

if (php_sapi_name()!='cli') {
    die('Command line tool only.');
}

header('Content-Type: application/json');
require_once('../../components.php');
require_once('extras/getid3/getid3/getid3.php');
$getID3 = new getID3();
$db = OBFDB::get_instance();
$models = OBFModels::get_instance();
$user_agent = 'OpenBroadcaster/'.trim(file_get_contents('VERSION'));

if (!defined('OB_SYNC_USERID') || !defined('OB_SYNC_SOURCE') || !defined('OB_ACOUSTID_KEY')) {
    die('OB_SYNC_USERID, OB_SYNC_SOURCE, and OB_ACOUSTID_KEY must be defined in config.php.'.PHP_EOL);
}

// create thumbnail directory if needed
if (!file_exists(OB_CACHE.'/thumbnails')) {
    if (!mkdir(OB_CACHE.'/thumbnails', 0755)) {
        die('Unable to create thumbnail directory. Make sure the OB cache directory is writable.'.PHP_EOL);
    }
}

echo 'getting file list'.PHP_EOL;

// get our file list
$files = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(OB_SYNC_SOURCE));
$it->rewind();
while ($it->valid()) {
    if (!$it->isDot()) {
        $files[] = $it->key();
    }
    $it->next();
}

echo 'found '.count($files).' files'.PHP_EOL;
echo 'getting data to check duplicates'.PHP_EOL;

// get our data for checking duplicates
$rows = $db->get('media_metadata');
$duplicate_file = [];
$duplicate_acoustid = [];
foreach ($rows as $row) {
    if (!empty($row['sync_path']) && !empty($row['sync_size'])) {
        $duplicate_file[$row['sync_path']] = $row['sync_size'];
    }
    if (!empty($row['sync_acoustid'])) {
        $duplicate_acoustid[$row['sync_acoustid']] = true;
    }
}

echo 'randomizing file order to make test import interesting'.PHP_EOL;
shuffle($files);
echo 'done'.PHP_EOL;

$last_request_time = 0;

foreach ($files as $file) {
    $file_explode = explode('/', $file);
    if ($file_explode[count($file_explode)-1][0]=='.') {
        echo 'skipping file starting with dot (hidden)'.PHP_EOL;
        continue;
    }

    $processing_times = [];
    $timetmp = microtime(true);

    // default to store in db
    $acoustid_raw_response = '';

    $filesize = filesize($file);

    // possible current upload check
    $file_stat = stat($file);
    if ($file_stat[7]==0) {
        echo 'skipping possible current upload (zero byte): '.$file.PHP_EOL;
        continue;
    }
    if ($file_stat['ctime']>strtotime('-15 minutes')) {
        echo 'skipping possible current upload (recent ctime): '.$file.PHP_EOL;
        continue;
    }
    if ($file_stat['mtime']>strtotime('-15 minutes')) {
        echo 'skipping possible current upload (recent mtime): '.$file.PHP_EOL;
        continue;
    }

    // duplicate check (filename+size)
    if (isset($duplicate_file[$file]) && $duplicate_file[$file]==$filesize) {
        echo 'skipping duplicate (path/size): '.$file.PHP_EOL;
        continue;
    }

    $processing_times['duplicate check'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    echo 'importing '.$file.PHP_EOL;

    // get some id3 info which helps get best match and/or fill in missing data
    $id3raw = $getID3->analyze($file);
    getid3_lib::CopyTagsToComments($id3raw);
    $id3 = [];

    if (isset($id3raw['comments']) && is_array($id3raw['comments'])) {
        foreach ($id3raw['comments'] as $index=>$value) {
            if ($index=='genre' && is_array($value)) {
                $id3[$index] = array_map('strtolower', $value);
            } elseif ($index=='genre' && is_string($value)) {
                $id3[$index] = strtolower($value);
            } elseif (is_string($value)) {
                $id3[$index] = trim($value);
            } elseif (is_array($value)) {
                $id3[$index] = trim(@implode(', ', $value));
            }
        }
    }

    $processing_times['id3 analysis'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    $fpcalc = shell_exec('fpcalc '.escapeshellarg($file));
    if (!$fpcalc) {
        continue;
    }

    $fpcalc_lines = explode(PHP_EOL, $fpcalc);
    $info = [];
    foreach ($fpcalc_lines as $fpcalc_line) {
        if (strpos($fpcalc_line, 'DURATION=')===0) {
            $info['duration'] = preg_replace('/DURATION\=/', '', $fpcalc_line, 1);
        }
        if (strpos($fpcalc_line, 'FINGERPRINT=')===0) {
            $info['fingerprint'] = preg_replace('/FINGERPRINT\=/', '', $fpcalc_line, 1);
        }
    }

    $processing_times['fpcalc'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    $data = [
    'client' => OB_ACOUSTID_KEY,
    'format'=>'json',
    'duration'=>$info['duration'],
    'fingerprint'=>$info['fingerprint'],
    'meta'=>'releases tracks releasegroups'
  ];

    // prevent exceeding acoutid rate limit
    $last_request_delta = microtime(true) - $last_request_time;
    echo 'delta '.$last_request_delta.' seconds'.PHP_EOL;
    if ($last_request_delta<1) {
        usleep((1 - $last_request_delta)*1000000);
    }
    $last_request_time = microtime(true);

    $ch = curl_init('https://api.acoustid.org/v2/lookup');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, $user_agent);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $acoustid_raw_response = curl_exec($ch);
    $http_response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_response_code!=200 && $http_response_code!=404) {
        echo 'acoustid unexpected response code '. $http_response_code.', wait 5 seconds then skip.'.PHP_EOL;
        sleep(5);
        continue;
    } else {
        echo 'acoustid http response code '.$http_response_code.PHP_EOL;
    }
    curl_close($ch);

    $processing_times['acoustid request'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    $response = json_decode($acoustid_raw_response);

    if (!$response || !isset($response->status) || $response->status!='ok') {
        echo 'AcoustID service error (skipping).'.PHP_EOL;
        continue;
    }

    if (empty($response->results)) {
        echo 'No results from AcoustID (skipping)'.PHP_EOL;
        continue;
    }

    // get best result from fingerprint
    $results = $response->results;
    $result_score = 0;
    $result = false;
    foreach ($results as $tmp) {
        if ($tmp->score > $result_score) {
            $result = $tmp;
            $result_score = $tmp->score;
        }
    }
    $acoustid = $tmp->id;

    // duplicate check (acoustid)
    if (isset($duplicate_acoustid[$acoustid])) {
        echo 'skipping duplicate (acoustid): '.$file.PHP_EOL;
        continue;
    }

    // get best album result (match acoustid against pathname or id3)
    $artist = false;
    $album = false;
    $album_year = false;
    $earliest_year = INF;
    $track_number = false;
    $title = false;
    $releasegroup_id = false;
    $album_match_source = (isset($id3['album']) && $id3['album']!='') ? $id3['album'] : '';
    $album_match_score = -INF;

    // no potential album name from id3? use directory.
    if ($album_match_source=='') {
        $file_explode = explode('/', $file);
        $album_match_source = $file_explode[count($file_explode)-2];
    }

    // make sure we have releases
    if (!isset($result->releasegroups) || !is_array($result->releasegroups)) {
        echo 'missing release information for: '.$file.PHP_EOL;
        continue;
    }

    // get best text match for album
    foreach ($result->releasegroups as $releasegroup) {
        foreach ($releasegroup->releases as $release) {
            if (($album_match_score_tmp = similar_text($album_match_source, $release->title)) > $album_match_score) {
                $album = $release->title;
                $artist = $release->artists[0]->name ?? false;
                $title = $release->mediums[0]->tracks[0]->title ?? false;
                $album_year = $release->date->year ?? false;
                $earliest_year = min($earliest_year, $release->date->year ?? INF);
                $track_number = $release->mediums[0]->tracks[0]->position ?? false;
                $album_match_score = $album_match_score_tmp;
                $album_match_release = $release;
                $releasegroup_id = $releasegroup->id;
            }
        }
    }

    // override with id3 year if we have it
    if (isset($id3['year']) && preg_match('/^[0-9]{4}$/', $id3['year'])) {
        $album_year = $id3['year'];
        $earliest_year = min($earliest_year, $album_year);
    }
    if ($earliest_year==INF) {
        $earliest_year = false;
    }

    // override with id3 track number if we have it
    if (isset($id3['track_number']) && (int) $id3['track_number']!=0) {
        $track_number = (int) $id3['track_number'];
    }

    // fill in missing info with id3, skip if we don't have it.
    if (!$artist && isset($id3['artist']) && $id3['artist']!='') {
        $artist = $id3['artist'];
    }
    if (!$album && isset($id3['album']) && $id3['album']!='') {
        $album = $id3['album'];
    }
    if (!$title && isset($id3['title']) && $id3['title']!='') {
        $title = $id3['title'];
    }
    if (!$artist || !$album || !$title) {
        echo 'missing artist/album/title for '.$file.PHP_EOL;
        continue;
    }

    $processing_times['acoustid process'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    // get our genres from musicbrainz (looks like acoustid doesn't provide directly)
    // update: see sync-genres.php to allow more parallel processing
    $genres = [];

    // id3 as backup genres
    if (empty($genres) && !empty($id3['genre'])) {
        $genres = $id3['genre'];
    }

    // prepare import
    $file_id = $db->insert('uploads', [
    'key'    => bin2hex(openssl_random_pseudo_bytes(16)),
    'expiry' => time() + 86400
  ]);
    copy($file, OB_ASSETS.'/uploads/'.$file_id);

    $processing_times['copy file'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    $info = $models->media('media_info', ['filename' => OB_ASSETS . '/uploads/' . $file_id]);

    $processing_times['get media info'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    $db->where('id', $file_id);
    $db->update('uploads', [
    'type' => $info['type'] ?? null,
    'duration' => $info['duration'] ?? null,
    'format' => $info['format'] ?? null
  ]);

    $processing_times['save upload row'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    // save
    $item = [
    'file_id' => $file_id,
    'file_info' => $info,
    'artist' => $artist,
    'album' => $album,
    'title' => $title,
    'status' => 'visible',
    'owner_id' => OB_SYNC_USERID,
    'local_id' => 1,
    'is_copyright_owner' => 0,
    'is_approved' => 1,
    'dynamic_select' => 1,
    'metadata_genres' => $genres,
    'metadata_year_earliest' => $earliest_year ? $earliest_year : '',
    'metadata_year_album' => $album_year ? $album_year : '',
    'metadata_track_number' => $track_number ? $track_number : '',
    'metadata_sync_acoustid' => $acoustid,
    'metadata_sync_acoustid_raw' => $acoustid_raw_response,
    'metadata_sync_releasegroup_id' => $releasegroup_id,
    //'metadata_sync_coverart_raw' => $coverart_raw_response,
    //'metadata_sync_musicbrainz_raw' => $musicbrainz_raw_response,
    'metadata_sync_path' => $file,
    'metadata_sync_size' => $filesize
  ];

    echo 'validating media entry'.PHP_EOL;
    $valid = $models->media('validate', ['item' => $item]);

    $processing_times['validate media item'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    echo 'saving media entry'.PHP_EOL;
    if ($valid[0]) {
        $media_id = $models->media('save', ['item' => $item]);
    }

    $processing_times['save media item'] = microtime(true) - $timetmp;
    $timetmp = microtime(true);

    echo PHP_EOL.PHP_EOL;
    var_dump($processing_times);
    echo PHP_EOL.PHP_EOL;
}
