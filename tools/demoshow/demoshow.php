#!/usr/bin/env php

<?php

if (php_sapi_name() != 'cli') {
  exit("Script cannot be run from web environment.\n");
}

require(__DIR__.'/../../components.php');

$db = OBFDB::get_instance();
$models = OBFModels::get_instance();

$schedule_str  = file_get_contents(__DIR__ . '/schedule4.json');
$schedule_json = json_decode($schedule_str, true);

// GET FIRST USER

$db->query('SELECT * FROM `users` ORDER BY `id` ASC LIMIT 1');
$user_id = $db->assoc_list()[0]['id'] ?? null;

if (! $user_id) {
    exit("Failed to get user from database. Quitting.\n");
}

// CREATE PLAYER

$player_id = $models->players('save', [
    'owner_id'       => $user_id,
    'password'       => 'abc123',
    'name'           => 'Demo Player ' . uniqid(),
    'timezone'       => 'America/Whitehorse', // error on show display otherwise
    'support_audio'  => true,
    'support_video'  => true,
    'support_images' => true,
    'station_ids'         => '', // legacy code, avoids warning in players model
    'use_parent_schedule' => false,
]);

if (! $player_id) {
    exit("Failed to create player. Quitting.\n");
}

// CREATE PLAYLISTS

$playlists = [];
foreach ($schedule_json['schedule'] as $item) {
    $created = time();
    $playlist_id = $models->playlists('insert', [
        'owner_id'    => $user_id,
        'type'        => 'standard',
        'name'        => $item['title'],
        'description' => $item['description'],
        'status'      => 'public',
        'created'     => $created,
        'updated'     => $created,
    ]);

    if (! $playlist_id) {
        echo $db->error();
        exit("Failed to insert playlist. Quitting.\n");
    }

    $playlists[] = [
        'id'       => $playlist_id,
        'duration' => $item['duration'],
    ];
}

// var_export($playlists);

// CREATE SHOWS ON PLAYER

$next_datetime = date('Y-m-d H:i:s', strtotime('7am'));
foreach ($playlists as $playlist) {
    $models->shows('save_show', [
        'player_id' => $player_id,
        'user_id'   => $user_id,
        'item_id'   => $playlist['id'],
        'item_type' => 'playlist',
        'mode'      => 'xdays',
        'x_data'    => 3,
        'start'     => $next_datetime,
        'duration'  => intval($playlist['duration']) * 60,
        'stop'      => date('Y-m-d', strtotime($next_datetime) + (60 * 60 * 24 * 30 * 6)),
    ]);

    $next_datetime = date('Y-m-d H:i:s', strtotime($next_datetime) + (intval($playlist['duration']) * 60));
}
