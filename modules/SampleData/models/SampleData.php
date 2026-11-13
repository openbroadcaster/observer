<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

/**
 * Imports a sample station from a profile in the profiles folder.
 *
 * @package Model
 */

namespace OpenBroadcaster\Modules\SampleData\Models;

use DateTimeImmutable;
use DateTimeZone;
use OpenBroadcaster\Base\Model;

class SampleData extends Model
{
    private const STEPS = ['categories', 'settings', 'download', 'import', 'playlists', 'schedule'];
    private const IMPORT_BATCH = 10;

    private $profile;
    private $json = [];
    private $log = [];

    /**
     * Get the available profiles.
     *
     * @return profiles
     */
    public function list_profiles()
    {
        $profiles = [];

        foreach (glob(OB_LOCAL . '/modules/SampleData/profiles/*', GLOB_ONLYDIR) as $dir) {
            $manifest = json_decode(file_get_contents($dir . '/manifest.json'), true);
            $profiles[] = ['directory' => basename($dir), 'name' => $manifest['name'], 'description' => $manifest['description']];
        }

        return $profiles;
    }

    /**
     * Check a profile before an import starts.
     *
     * @param profile
     *
     * @return [problem, fresh, existing, download_mb, steps]
     */
    public function preflight($profile)
    {
        $problem = $this->open_profile($profile);

        //T The PHP zip extension is missing.
        if (!$problem && !class_exists('ZipArchive')) {
            $problem = 'The PHP zip extension is missing.';
        }

        if ($problem) {
            return ['problem' => $problem];
        }

        $existing = $this->existing_content();

        return [
            'problem' => null,
            'fresh' => !$existing,
            'existing' => $existing,
            'download_mb' => (int) round($this->json['media.json']['size'] / 1000000),
            'steps' => self::STEPS,
        ];
    }

    /**
     * Run one step of an import. Every step is safe to repeat: a rerun only completes what's missing.
     * The caller repeats a step until next_offset is null, waiting a few seconds first if details has busy set.
     *
     * @param profile
     * @param step
     * @param offset
     * @param confirmed
     *
     * @return [status, msg, [error, log, next_offset, details]]
     */
    public function run_step($profile, $step, $offset, $confirmed)
    {
        $this->log = [];

        //T Unknown step.
        if (!in_array($step, self::STEPS, true)) {
            return $this->step_result([false, 'Unknown step.']);
        }

        $problem = $this->open_profile($profile);
        if ($problem) {
            return $this->step_result([false, $problem]);
        }

        //T This install already has its own content, so the import needs confirming first.
        if (!$confirmed && $this->existing_content()) {
            return $this->step_result([false, 'This install already has its own content, so the import needs confirming first.']);
        }

        // one step at a time. a second tab, or a retry after a web server timeout, waits its turn.
        $lock = fopen(__FILE__, 'r');
        if (!flock($lock, LOCK_EX | LOCK_NB)) {
            $this->log[] = 'Another import step is still running, waiting for it…';
            return $this->step_result([true, null, ['next_offset' => $offset, 'details' => ['busy' => true]]]);
        }

        // all or nothing, so a rerun never finds half a job. if a step fails, not committing undoes it when the connection closes.
        $transaction = in_array($step, ['categories', 'playlists', 'schedule']);
        if ($transaction) {
            $this->db->autocommit(false);
        }

        $method = 'step_' . $step;
        $result = $this->$method($offset);

        if ($transaction && $result[0]) {
            // re-enabling autocommit commits the transaction.
            $this->db->autocommit(true);
        }

        return $this->step_result($result);
    }

    // OB.API.request only returns the data, so the page needs any error in there too.
    private function step_result($result)
    {
        $data = ($result[2] ?? []) + ['next_offset' => null, 'details' => []];
        $data['error'] = $result[0] ? null : $result[1];
        $data['log'] = $this->log;

        return [$result[0], $result[1] ?? 'Import step.', $data];
    }

    private function open_profile($profile)
    {
        $dir = OB_LOCAL . '/modules/SampleData/profiles/' . $profile;

        //T Profile not found.
        if (!preg_match('/^[a-z0-9_]+$/', $profile) || !is_dir($dir)) {
            return 'Profile not found.';
        }

        // the import changes site-wide settings and groups, so the permission alone isn't enough.
        //T Only an administrator can import sample data.
        if (!$this->user->is_admin) {
            return 'Only an administrator can import sample data.';
        }

        $this->profile = $profile;
        $this->json = [];
        foreach (glob($dir . '/*.json') as $file) {
            $this->json[basename($file)] = json_decode(file_get_contents($file), true);
        }

        foreach (['media.json', 'playlists.json', 'schedule.json'] as $file) {
            if (empty($this->json[$file])) {
                return $file . ' is missing or invalid.';
            }
        }

        return null;
    }

    private function step_categories()
    {
        $categories = $this->categories();
        $added = 0;
        foreach ($this->json['categories.json'] ?? [] as $name) {
            if (!isset($categories[$this->name_key($name)])) {
                $this->models->mediacategories('save', ['name' => $name, 'is_default' => 0]);
                $added++;
            }
        }
        $this->log[] = "Categories added: {$added}.";

        $categories = $this->categories();
        $genres = $this->genres();
        $added = 0;
        foreach ($this->json['genres.json'] ?? [] as $category_name => $names) {
            $category = $categories[$this->name_key($category_name)] ?? null;
            if (!$category) {
                return [false, "Category '{$category_name}' not found."];
            }
            foreach ($names as $name) {
                $key = $this->name_key($name);
                if (!isset($genres[$category['id']][$key])) {
                    $this->models->mediagenres('save', ['name' => $name, 'description' => $name, 'media_category_id' => (int) $category['id'], 'is_default' => 0]);
                    $genres[$category['id']][$key] = true;
                    $added++;
                }
            }
        }
        $this->log[] = "Genres added: {$added}.";

        // in this step because it's all or nothing: a rerun wouldn't fix a group left without its permissions.
        $added = 0;
        foreach ($this->json['groups.json'] ?? [] as $group) {
            if ($this->find_by_name('users_groups', $group['name'])) {
                continue;
            }
            $valid = $this->models->users('group_validate', $group);
            if (!$valid[0]) {
                return [false, "Group '{$group['name']}': {$valid[1][1]}"];
            }
            $this->models->users('group_save', $group);
            $added++;
        }
        $this->log[] = "Groups added: {$added}.";

        return [true];
    }

    private function step_settings()
    {
        $settings = $this->json['settings.json'] ?? [];

        // only ever add to the allowed formats, never replace what's there.
        $formats = $this->models->media('formats_get_all');
        $added = [];
        foreach ($settings['append_formats'] ?? [] as $name => $wanted) {
            $missing = array_diff($wanted, $formats[$name]);
            $formats[$name] = array_merge($formats[$name], $missing);
            $added = array_merge($added, $missing);
        }
        if ($added) {
            $this->models->media('formats_save', ['data' => $formats]);
            $this->log[] = 'Formats allowed: ' . implode(', ', $added) . '.';
        }

        if ($this->existing_content()) {
            $this->log[] = 'Login message, welcome page, custom fields and daypart rules left as they are.';
            return [true];
        }

        foreach (['client_login_message' => 'login message', 'client_welcome_page' => 'welcome page'] as $name => $label) {
            if (isset($settings[$name]) && empty($this->models->settings('setting_get', $name)[2])) {
                $this->models->settings('setting_set', $name, $settings[$name]);
                $this->log[] = "Set the {$label}.";
            }
        }

        foreach ($this->json['metadata_fields.json'] ?? [] as $field) {
            if ($this->find_by_name('media_metadata', $field['name'])) {
                continue;
            }
            $valid = $this->models->mediametadata('validate', $field, null);
            if (!$valid[0]) {
                return [false, "Field '{$field['name']}': {$valid[1]}"];
            }
            $this->models->mediametadata('save', $field, null);
            $this->log[] = "Added the custom field '{$field['description']}'.";
        }

        foreach ($this->json['dayparting.json'] ?? [] as $rule) {
            $this->db->where('description', $rule['description']);
            if ($this->db->get_one('dayparting')) {
                continue;
            }
            $rule['filters'] = json_encode($rule['filters']);
            $saved = $this->models->dayparting('save', $rule);
            if (!$saved[0]) {
                return [false, "Daypart rule '{$rule['description']}': {$saved[1]}"];
            }
            $this->log[] = "Added the daypart rule '{$rule['description']}'.";
        }

        return [true];
    }

    // the media host ignores range requests, so this is one long request. if a web server timeout
    // cuts the browser off, php carries on, and the lock in run_step makes a retry wait for it.
    private function step_download()
    {
        $media = $this->json['media.json'];
        $zip = $this->zip_path();

        if (count($this->imported_media()) == count($media['items'])) {
            $this->log[] = 'All media is already imported, nothing to download.';
            return [true];
        }

        if (is_file($zip) && hash_file('sha256', $zip) === $media['sha256']) {
            return [true];
        }

        set_time_limit(0);
        $file = fopen($zip, 'w');
        $curl = curl_init($media['url']);
        curl_setopt_array($curl, [
            CURLOPT_FILE => $file,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_FAILONERROR => true,
            // give up on a stalled connection, or it would hold the lock forever.
            CURLOPT_LOW_SPEED_LIMIT => 1024,
            CURLOPT_LOW_SPEED_TIME => 60,
        ]);
        $downloaded = curl_exec($curl);
        $error = curl_error($curl);
        fclose($file);

        if (!$downloaded) {
            unlink($zip);
            return [false, "Download failed: {$error}."];
        }
        if (hash_file('sha256', $zip) !== $media['sha256']) {
            unlink($zip);
            return [false, 'The downloaded pack doesn\'t match its expected checksum, so it wasn\'t used.'];
        }

        $this->log[] = 'Downloaded and checked the pack.';

        return [true];
    }

    private function step_import($offset)
    {
        $items = $this->json['media.json']['items'];
        $imported = $this->imported_media();
        $categories = $this->categories();
        $genres = $this->genres();

        foreach (array_slice($items, $offset, self::IMPORT_BATCH) as $item) {
            if (isset($imported[$item['key']])) {
                continue;
            }

            $category_id = $categories[$this->name_key($item['category'])]['id'] ?? null;
            $genre_id = $genres[$category_id][$this->name_key($item['genre'])]['id'] ?? null;

            $saved = $this->import_item($item, $category_id, $genre_id);
            if (!$saved[0]) {
                return $saved;
            }
            $this->log[] = "Added {$item['title']}.";
        }

        $next = $offset + self::IMPORT_BATCH;
        if ($next < count($items)) {
            return [true, null, ['next_offset' => $next]];
        }

        // everything's imported, so the download isn't needed any more.
        if (is_file($this->zip_path())) {
            unlink($this->zip_path());
        }

        return [true];
    }

    // goes through what a browser upload does: an uploads row, then core's media validate and save.
    // if a check fails, the row and file stay behind and expire like any other upload.
    private function import_item($item, $category_id, $genre_id)
    {
        $file = $item['file'];
        $key = bin2hex(random_bytes(16));
        $file_id = $this->db->insert('uploads', ['key' => $key, 'expiry' => strtotime('+24 hours')]);
        $upload_path = OB_UPLOADS . '/' . $file_id;

        if (!@copy('zip://' . $this->zip_path() . '#' . $file, $upload_path)) {
            return [false, "{$file}: could not copy the file."];
        }
        // the md5 is how a rerun finds this item again.
        if (md5_file($upload_path) !== $item['md5']) {
            return [false, "{$file} doesn't match its md5 in media.json."];
        }

        $info = $this->models->media('media_info', ['filename' => $upload_path]);
        $this->db->where('id', $file_id);
        $this->db->update('uploads', ['format' => $info['format'], 'type' => $info['type'], 'duration' => $info['duration']]);

        $media = [
            'file_id' => $file_id,
            'file_key' => $key,
            'file_info' => $this->models->uploads('file_info', $file_id, $key),
            'local_id' => $item['key'],
            'artist' => $item['artist'],
            'title' => $item['title'],
            'album' => $item['album'],
            'year' => $item['year'],
            'category_id' => $category_id,
            'genre_id' => $genre_id,
            'comments' => $this->json['media.json']['comments'],
            'country' => null,
            'language' => null,
            'is_approved' => 1,
            'is_copyright_owner' => 0,
            'dynamic_select' => $item['dynamic_select'] ? 1 : 0,
            'status' => 'public',
        ];

        $valid = $this->models->media('validate', ['item' => $media]);
        if (!$valid[0]) {
            return [false, "{$file}: {$valid[2]}"];
        }
        if (!$this->models->media('save', ['item' => $media])) {
            return [false, "{$file}: core could not save it."];
        }

        return [true];
    }

    private function step_playlists()
    {
        $imported = $this->imported_media();
        $buttons = [];

        foreach ($this->json['playlists.json'] as $playlist) {
            $name = $playlist['name'];

            if ($this->find_by_name('playlists', $name)) {
                $this->log[] = "'{$name}' already exists, so it was left as it is.";
                continue;
            }

            $id = $this->models->playlists('insert', [
                'name' => $name,
                'description' => $playlist['description'],
                'type' => $playlist['type'],
                'status' => 'public',
                'owner_id' => $this->user->param('id'),
                'created' => time(),
                'updated' => time(),
                'properties' => '[]',
            ]);

            foreach ($playlist['items'] as $index => $item) {
                $core_item = $this->core_item($item, $imported);
                $valid = $this->models->playlists('validate_playlist_item', $core_item);
                if (!$valid[0]) {
                    return [false, "'{$name}' item {$index}: {$valid[1]}"];
                }

                // core's playlist controller saves items this way. its model has no method for it.
                $row = ['playlist_id' => $id, 'item_type' => $item['type'], 'ord' => $index];
                if ($item['type'] == 'media') {
                    $row['item_id'] = $core_item['id'];
                } elseif ($item['type'] == 'dynamic') {
                    $row['properties'] = json_encode([
                        'num_items' => $item['num_items'],
                        'image_duration' => $item['image_duration'],
                        'query' => $item['query'],
                        'name' => $item['name'],
                        'crossfade' => 0,
                        'crossfade_last' => 0,
                    ]);
                }
                $this->db->insert('playlists_items', $row);
            }

            $buttons[$id] = $playlist['buttons'] ?? [];
            $this->log[] = "'{$name}': " . count($playlist['items']) . ' items.';
        }

        // after the loop, so a button can point at any playlist in the profile.
        foreach (array_filter($buttons) as $id => $names) {
            $ids = [];
            foreach ($names as $name) {
                $button = $this->find_by_name('playlists', $name);
                if (!$button) {
                    return [false, "Button playlist '{$name}' not found."];
                }
                $ids[] = $button['id'];
            }
            $this->models->playlists('update_liveassist_items', $id, $ids);
        }

        return [true];
    }

    // an item in the shape core's playlist validation expects.
    private function core_item($item, $imported)
    {
        if ($item['type'] == 'dynamic') {
            return [
                'type' => 'dynamic',
                'num_items' => $item['num_items'],
                'num_items_all' => false,
                'image_duration' => $item['image_duration'],
                'query' => json_encode($item['query']),
            ];
        }

        if ($item['type'] == 'media') {
            return ['type' => 'media', 'id' => $imported[$item['key']]['id'] ?? null, 'duration' => $imported[$item['key']]['duration'] ?? null];
        }

        return ['type' => $item['type']];
    }

    private function step_schedule()
    {
        $schedule = $this->json['schedule.json'];
        $config = $schedule['player'];

        // the player and its shows are created together, so if it's here there's nothing to do.
        if ($this->find_by_name('players', $config['name'])) {
            $this->log[] = "'{$config['name']}' already exists, so it was left as it is.";
            return [true];
        }

        $imported = $this->imported_media();
        $station_ids = [];
        foreach ($config['station_id_keys'] as $key) {
            $station_ids[] = $imported[$key]['id'];
        }

        $password = bin2hex(random_bytes(12));
        $player_id = $this->models->players('save', [
            'name' => $config['name'],
            'description' => $config['description'],
            'stream_url' => '',
            'password' => $password,
            'ip_address' => '',
            'support_audio' => $config['support_audio'] ? 1 : 0,
            'support_video' => $config['support_video'] ? 1 : 0,
            'support_images' => $config['support_images'] ? 1 : 0,
            'support_linein' => 0,
            'timezone' => $config['timezone'],
            'station_id_image_duration' => $config['station_id_image_duration'],
            'default_playlist_id' => $this->find_by_name('playlists', $config['default_playlist'])['id'],
            'parent_player_id' => null,
            'use_parent_dynamic' => 0,
            'use_parent_schedule' => 0,
            'use_parent_ids' => 0,
            'use_parent_playlist' => 0,
            'use_parent_alert' => 0,
            'station_ids' => $station_ids,
        ]);
        $this->log[] = "Created '{$config['name']}'.";

        // show times are the player's own wall clock. the first one is tomorrow at the earliest.
        $today = new DateTimeImmutable('today', new DateTimeZone($config['timezone']));

        foreach ($schedule['shows'] as $show) {
            $label = "'{$show['playlist']}' at {$show['start_time']}";
            $playlist = $this->find_by_name('playlists', $show['playlist']);
            $first = $today->modify(isset($show['weekday']) ? 'next ' . $show['weekday'] : '+1 day');
            $data = [
                'player_id' => $player_id,
                'mode' => $show['mode'] ?? 'daily',
                'x_data' => $show['every'] ?? 1,
                'start' => $first->format('Y-m-d') . ' ' . $show['start_time'] . ':00',
                'duration' => $show['duration'],
                'stop' => $first->modify('+' . $show['days'] . ' days')->format('Y-m-d'),
                'item_type' => 'playlist',
                'item_id' => $playlist['id'] ?? null,
            ];

            $valid = $this->models->shows('validate_show', $data);
            if (!$valid[0]) {
                return [false, "{$label}: {$valid[1]}"];
            }

            $this->models->shows('save_show', $data);
            $this->log[] = "Scheduled {$label}.";
        }

        $this->log[] = 'Your sample station is ready.';

        return [true, null, ['details' => ['player_id' => $player_id, 'player_password' => $password]]];
    }

    // anything here that isn't from this profile counts as the install's own content.
    private function existing_content()
    {
        return array_filter([
            //T media items: %1
            'media items: %1' => $this->count_others('media', 'file_hash', array_column($this->json['media.json']['items'], 'md5')),
            //T playlists: %1
            'playlists: %1' => $this->count_others('playlists', 'name', array_column($this->json['playlists.json'], 'name')),
            //T players: %1
            'players: %1' => $this->count_others('players', 'name', [$this->json['schedule.json']['player']['name']]),
        ]);
    }

    private function count_others($table, $column, $known)
    {
        $this->db->query('SELECT COUNT(*) AS total FROM `' . $table . '` WHERE `' . $column . '` IS NULL OR `' . $column . '` NOT IN (' . $this->quoted_list($known) . ')');

        return (int) $this->db->assoc_row()['total'];
    }

    // the profile's media already in the library, by item key. one query per step, because
    // media.file_hash has no index: a query per item would scan the whole table every time.
    private function imported_media()
    {
        $items = $this->json['media.json']['items'];

        $this->db->what('id');
        $this->db->what('file_hash');
        $this->db->what('duration');
        $this->db->where_string('is_archived = 0 AND file_hash IN (' . $this->quoted_list(array_column($items, 'md5')) . ')');
        $rows = array_column($this->db->get('media'), null, 'file_hash');

        $imported = [];
        foreach ($items as $item) {
            if (isset($rows[$item['md5']])) {
                $imported[$item['key']] = $rows[$item['md5']];
            }
        }

        return $imported;
    }

    private function quoted_list($values)
    {
        return '"' . implode('","', array_map([$this->db, 'escape'], $values)) . '"';
    }

    private function find_by_name($table, $name)
    {
        $this->db->where('name', $name);

        return $this->db->get_one($table);
    }

    private function categories()
    {
        $categories = [];
        foreach ($this->db->get('media_categories') as $row) {
            $categories[$this->name_key($row['name'])] = $row;
        }

        return $categories;
    }

    private function genres()
    {
        $genres = [];
        foreach ($this->db->get('media_genres') as $row) {
            $genres[$row['media_category_id']][$this->name_key($row['name'])] = $row;
        }

        return $genres;
    }

    // core's own lists and a profile can spell one name differently ("Hip Hop", "Hip-Hop").
    private function name_key($name)
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $name));
    }

    private function zip_path()
    {
        return OB_CACHE . '/sampledata-' . $this->profile . '.zip';
    }
}
