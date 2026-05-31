<?php

// Copyright 2012-2026 OpenBroadcaster, Inc.
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OpenBroadcaster\Modules\SampleData\Models;

use OpenBroadcaster\Base\Model;

class SampleData extends Model
{
    private string $profileDir;
    private int $adminUserId;
    private array $log = [];

    /**
     * List profiles by reading each subdirectory's manifest.json.
     */
    public function listProfiles(): array
    {
        $root = $this->profilesRoot();
        if (!is_dir($root)) {
            return [];
        }
        $out = [];
        foreach (scandir($root) as $dir) {
            if ($dir[0] === '.' || !is_dir($root . '/' . $dir)) {
                continue;
            }
            $manifest = $this->readJson($root . '/' . $dir . '/manifest.json') ?: [];
            $out[] = [
                'directory' => $dir,
                'name' => $manifest['name'] ?? $dir,
                'description' => $manifest['description'] ?? '',
            ];
        }
        return $out;
    }

    /**
     * Run a profile. Returns ['success' => bool, 'log' => string[], 'error' => string|null].
     */
    public function runProfile(string $profile): array
    {
        $this->log = [];

        if (!preg_match('/^[a-z0-9_]+$/', $profile)) {
            return $this->fail('Invalid profile name.');
        }
        $this->profileDir = $this->profilesRoot() . '/' . $profile;
        if (!is_dir($this->profileDir)) {
            return $this->fail("Profile not found: {$profile}");
        }
        $manifest = $this->readJson($this->profileDir . '/manifest.json');
        if (!$manifest) {
            return $this->fail('Profile manifest missing or invalid.');
        }

        $this->log('Seeding profile: ' . ($manifest['name'] ?? $profile));
        $this->log(str_repeat('-', 60));

        $this->db->query('SELECT * FROM `users` ORDER BY `id` ASC LIMIT 1');
        $admin = $this->db->assoc_list();
        $this->adminUserId = $admin[0]['id'] ?? 1;

        $steps = [
            ['seedCategories', 'categories.json', 'Categories'],
            ['seedGenres', 'genres.json', 'Genres'],
            ['seedGroups', 'groups.json', 'Permission Groups'],
            ['seedUsers', 'users.json', 'Users'],
            ['seedSettings', 'settings.json', 'Settings'],
            ['seedMetadataFields', 'metadata_fields.json', 'Custom Metadata Fields'],
            ['seedMedia', 'media.json', 'Media Library'],
            ['seedPlaylists', 'playlists.json', 'Playlists'],
            ['seedSchedule', 'schedule.json', 'Player & Schedule'],
        ];

        foreach ($steps as [$method, $file, $label]) {
            $data = $this->readJson($this->profileDir . '/' . $file);
            if ($data === null) {
                $this->log("[SKIP] {$label} — {$file} not found or invalid.");
                continue;
            }
            $this->log('');
            $this->log("[{$label}]");
            try {
                $this->$method($data);
            } catch (\Throwable $e) {
                return $this->fail("Error in {$label}: " . $e->getMessage());
            }
        }

        $this->log('');
        $this->log(str_repeat('-', 60));
        $this->log('Seed complete.');
        return ['success' => true, 'log' => $this->log, 'error' => null];
    }

    // -------- helpers --------

    private function profilesRoot(): string
    {
        return OB_LOCAL . '/modules/SampleData/profiles';
    }

    private function log(string $line): void
    {
        $this->log[] = $line;
    }

    private function fail(string $msg): array
    {
        return ['success' => false, 'log' => $this->log, 'error' => $msg];
    }

    private function readJson(string $path): ?array
    {
        if (!file_exists($path)) {
            return null;
        }
        $decoded = json_decode(file_get_contents($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    private function findByName(string $table, string $name)
    {
        $this->db->where('name', $name);
        return $this->db->get_one($table);
    }

    // -------- seed steps --------

    private function seedCategories(array $names): void
    {
        $ins = $skip = 0;
        foreach ($names as $name) {
            if ($this->findByName('media_categories', $name)) {
                $skip++;
                continue;
            }
            $this->db->insert('media_categories', ['name' => $name]);
            $ins++;
        }
        $this->log("  Inserted: {$ins}, Skipped (already exist): {$skip}");
    }

    private function seedGenres(array $genresByCategory): void
    {
        $ins = $skip = $err = 0;
        foreach ($genresByCategory as $catName => $genres) {
            $cat = $this->findByName('media_categories', $catName);
            if (!$cat) {
                $this->log("  Warning: Category '{$catName}' not found, skipping its genres.");
                $err += count($genres);
                continue;
            }
            foreach ($genres as $g) {
                $this->db->where('name', $g['name']);
                $this->db->where('media_category_id', $cat['id']);
                if ($this->db->get_one('media_genres')) {
                    $skip++;
                    continue;
                }
                $this->db->insert('media_genres', [
                    'name' => $g['name'],
                    'description' => $g['description'] ?? $g['name'],
                    'media_category_id' => (int) $cat['id'],
                ]);
                $ins++;
            }
        }
        $line = "  Inserted: {$ins}, Skipped: {$skip}";
        if ($err) {
            $line .= ", Errors: {$err}";
        }
        $this->log($line);
    }

    private function seedGroups(array $groups): void
    {
        $ins = $skip = 0;
        foreach ($groups as $group) {
            if ($this->findByName('users_groups', $group['name'])) {
                $skip++;
                continue;
            }
            $gid = $this->db->insert('users_groups', ['name' => $group['name']]);
            if (!$gid) {
                continue;
            }
            $count = 0;
            foreach ($group['permissions'] as $perm) {
                $row = $this->findByName('users_permissions', $perm);
                if (!$row) {
                    $this->log("  Warning: permission '{$perm}' not found.");
                    continue;
                }
                $this->db->insert('users_permissions_to_groups', [
                    'permission_id' => $row['id'],
                    'group_id' => $gid,
                ]);
                $count++;
            }
            $this->log("  Created '{$group['name']}' with {$count} permissions.");
            $ins++;
        }
        $this->log("  Inserted: {$ins}, Skipped: {$skip}");
    }

    private function seedUsers(array $users): void
    {
        $ins = $skip = 0;
        foreach ($users as $u) {
            $this->db->where('username', $u['username']);
            if ($this->db->get_one('users')) {
                $skip++;
                continue;
            }
            $uid = $this->db->insert('users', [
                'name' => $u['name'], 'username' => $u['username'], 'email' => $u['email'],
                'password' => $this->user->password_hash($u['password']),
                'display_name' => $u['display_name'],
                'enabled' => $u['enabled'] ? 1 : 0,
                'created' => time(), 'last_access' => 0,
            ]);
            if (!$uid) {
                continue;
            }
            if (!empty($u['group'])) {
                $g = $this->findByName('users_groups', $u['group']);
                if ($g) {
                    $this->db->insert('users_to_groups', ['user_id' => $uid, 'group_id' => $g['id']]);
                }
            }
            $ins++;
        }
        $this->log("  Inserted: {$ins}, Skipped: {$skip}");
    }

    /**
     * Settings are overwrite-only by design: format whitelists, core metadata
     * required-field flags, login message, welcome page.
     */
    private function seedSettings(array $data): void
    {
        $settings = $data['settings'] ?? [];

        // Format whitelists.
        $formatKeys = ['audio_formats', 'video_formats', 'image_formats', 'document_formats'];
        foreach ($formatKeys as $k) {
            if (isset($settings[$k])) {
                $v = is_array($settings[$k]) ? implode(',', $settings[$k]) : $settings[$k];
                $this->writeSetting($k, $v);
                $this->log("  Set '{$k}'.");
            }
        }

        // Core metadata flags. Profiles may use country_id/language_id; canonical is country/language.
        if (isset($settings['core_metadata'])) {
            $cm = is_string($settings['core_metadata'])
                ? (json_decode($settings['core_metadata'], true) ?: [])
                : $settings['core_metadata'];
            $this->writeSetting('core_metadata', json_encode([
                'artist' => $cm['artist'] ?? 'disabled',
                'album' => $cm['album'] ?? 'disabled',
                'year' => $cm['year'] ?? 'disabled',
                'category_id' => $cm['category_id'] ?? 'disabled',
                'country' => $cm['country'] ?? $cm['country_id'] ?? 'disabled',
                'language' => $cm['language'] ?? $cm['language_id'] ?? 'disabled',
                'comments' => $cm['comments'] ?? 'disabled',
            ]));
            $this->log('  Set core metadata fields.');
        }

        // Free-form settings (everything not handled above).
        foreach ($settings as $name => $value) {
            if (in_array($name, $formatKeys, true) || $name === 'core_metadata') {
                continue;
            }
            $this->writeSetting($name, is_array($value) ? json_encode($value) : (string) $value);
            $this->log("  Set '{$name}'.");
        }

        if (!empty($data['client_login_message'])) {
            $this->writeSetting('client_login_message', $data['client_login_message']);
            $this->log('  Set login message.');
        }

        if (!empty($data['client_welcome_page'])) {
            $this->setClientWelcome($data['client_welcome_page']);
            $this->log('  Set welcome page.');
        }
    }

    private function writeSetting(string $name, string $value): void
    {
        $this->db->where('name', $name);
        $this->db->delete('settings');
        $this->db->insert('settings', ['name' => $name, 'value' => $value]);
    }

    private function setClientWelcome(string $html): void
    {
        // Welcome page lives in client_storage as a global (user_id=0) entry
        // for obapp_web_client. Same shape ClientSettings uses.
        $this->db->where('user_id', 0);
        $this->db->where('client_name', 'obapp_web_client');
        $existing = $this->db->get_one('client_storage');
        $data = $existing ? (json_decode($existing['data'], true) ?: []) : [];
        $data['welcome_message'] = $html;
        $encoded = json_encode($data);
        if ($existing) {
            $this->db->where('id', $existing['id']);
            $this->db->update('client_storage', ['data' => $encoded]);
        } else {
            $this->db->insert('client_storage', ['user_id' => 0, 'client_name' => 'obapp_web_client', 'data' => $encoded]);
        }
    }

    /**
     * Custom metadata fields. Uses the MediaMetadata model so the ALTER TABLE
     * on the media table happens correctly.
     */
    private function seedMetadataFields(array $fields): void
    {
        $ins = $skip = 0;
        foreach ($fields as $field) {
            if ($this->findByName('media_metadata', $field['name'])) {
                $skip++;
                continue;
            }
            if ($this->models->mediametadata('save', $field, null)) {
                $ins++;
            }
        }
        $this->log("  Inserted: {$ins}, Skipped: {$skip}");
    }

    private function seedPlaylists(array $playlists): void
    {
        $ins = $skip = 0;
        foreach ($playlists as $p) {
            if ($this->findByName('playlists', $p['name'])) {
                $skip++;
                continue;
            }
            $this->insertPlaylist($p['name'], $p['description'] ?? '', $p['type'] ?? 'standard', $p['status'] ?? 'public');
            $ins++;
        }
        $this->log("  Inserted: {$ins}, Skipped: {$skip}");
    }

    private function insertPlaylist(string $name, string $description, string $type, string $status = 'public'): ?int
    {
        $now = time();
        $id = $this->db->insert('playlists', [
            'owner_id' => $this->adminUserId,
            'type' => $type,
            'name' => $name,
            'description' => $description,
            'status' => $status,
            'created' => $now,
            'updated' => $now,
        ]);
        return $id ?: null;
    }

    private function seedSchedule(array $data): void
    {
        if (empty($data['player']) || empty($data['shows'])) {
            $this->log('  No player or shows defined.');
            return;
        }
        $pd = $data['player'];
        if ($this->findByName('players', $pd['name'])) {
            $this->log("  Player '{$pd['name']}' already exists, skipping schedule.");
            return;
        }

        $playerId = $this->db->insert('players', [
            'name' => $pd['name'], 'description' => 'Sample player created by seed profile.',
            'timezone' => $pd['timezone'] ?? 'America/Toronto',
            'support_audio' => $pd['support_audio'] ? 1 : 0,
            'support_video' => $pd['support_video'] ? 1 : 0,
            'support_images' => $pd['support_images'] ? 1 : 0,
            'support_linein' => 0,
            'password' => password_hash('changeme' . OB_HASH_SALT, PASSWORD_DEFAULT),
            'owner_id' => $this->adminUserId,
            'use_parent_schedule' => 0, 'use_parent_ids' => 0,
            'use_parent_dynamic' => 0, 'use_parent_playlist' => 0,
            'stream_url' => '', 'version' => '',
        ]);
        if (!$playerId) {
            $this->log('  Error creating player.');
            return;
        }
        $this->log("  Created player '{$pd['name']}'.");

        $count = 0;
        foreach ($data['shows'] as $show) {
            $existing = $this->findByName('playlists', $show['title']);
            $playlistId = $existing
                ? $existing['id']
                : $this->insertPlaylist($show['title'], $show['description'] ?? '', 'standard');
            if (!$playlistId) {
                continue;
            }
            $start = date('Y-m-d') . ' ' . $show['start_time'] . ':00';
            $stop = date('Y-m-d', strtotime('+' . ($show['recurring_days'] ?? 180) . ' days'));
            $this->models->shows('save_show', [
                'player_id' => $playerId,
                'user_id' => $this->adminUserId,
                'item_id' => $playlistId,
                'item_type' => 'playlist',
                'mode' => $show['mode'] ?? 'daily',
                'x_data' => 1,
                'start' => $start,
                'duration' => intval($show['duration']) * 60,
                'stop' => $stop,
            ]);
            $count++;
        }
        $this->log("  Scheduled {$count} shows on '{$pd['name']}'.");
    }

    /**
     * Download + import the official OB sample media bundle. Files land in
     * OB_MEDIA via the standard 2-char file_location convention; rows go into
     * the media table directly. Idempotent via SHA1 hash check.
     */
    private function seedMedia(array $data): void
    {
        $url = $data['url'] ?? null;
        if (!$url) {
            $this->log('  No media bundle URL provided.');
            return;
        }
        $cacheDir = OB_LOCAL . '/media_data/cache/sampledata';
        if (!is_dir($cacheDir) && !mkdir($cacheDir, 0755, true)) {
            $this->log('  Failed to create cache directory.');
            return;
        }
        $zipPath = $cacheDir . '/' . basename(parse_url($url, PHP_URL_PATH));
        if (!$this->downloadBundle($url, $zipPath)) {
            return;
        }
        $extractDir = $cacheDir . '/extracted';
        if (!$this->extractBundle($zipPath, $extractDir)) {
            return;
        }
        $catIds = $this->resolveDefaultCategories($data['default_categories_by_type'] ?? []);

        $ins = $skip = $err = 0;
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($extractDir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iter as $f) {
            if (!$f->isFile()) {
                continue;
            }
            $result = $this->importMediaFile($f, $catIds);
            if ($result === null) {
                continue; // unsupported extension, ignore silently
            }
            if ($result === true) {
                $ins++;
            } elseif ($result === 'skip') {
                $skip++;
            } else {
                $err++;
            }
        }
        $line = "  Imported: {$ins}, Skipped (already exist by hash): {$skip}";
        if ($err) {
            $line .= ", Errors: {$err}";
        }
        $this->log($line);
    }

    private function downloadBundle(string $url, string $zipPath): bool
    {
        if (file_exists($zipPath)) {
            $this->log('  Using cached bundle (' . round(filesize($zipPath) / 1048576, 1) . ' MB).');
            return true;
        }
        $this->log("  Downloading bundle from {$url}...");
        $fp = fopen($zipPath, 'w');
        if (!$fp) {
            $this->log('  Could not open cache file for writing.');
            return false;
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 600,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $ok = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        fclose($fp);
        if (!$ok || $code >= 400) {
            $this->log("  Download failed (HTTP {$code}): {$err}");
            @unlink($zipPath);
            return false;
        }
        $this->log('  Downloaded ' . round(filesize($zipPath) / 1048576, 1) . ' MB.');
        return true;
    }

    private function extractBundle(string $zipPath, string $extractDir): bool
    {
        if (is_dir($extractDir)) {
            return true;
        }
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->log('  Failed to open zip archive.');
            return false;
        }
        if (!mkdir($extractDir, 0755, true)) {
            $zip->close();
            $this->log('  Failed to create extract directory.');
            return false;
        }
        $zip->extractTo($extractDir);
        $zip->close();
        $this->log('  Extracted bundle.');
        return true;
    }

    private function resolveDefaultCategories(array $map): array
    {
        $out = [];
        foreach ($map as $type => $catName) {
            $row = $this->findByName('media_categories', $catName);
            if ($row) {
                $out[$type] = (int) $row['id'];
            }
        }
        return $out;
    }

    /**
     * Import one media file. Returns true on import, 'skip' on dedup hit, false on error,
     * null when extension is unsupported (caller should ignore).
     */
    private function importMediaFile(\SplFileInfo $f, array $categoryIds)
    {
        static $types = [
            'mp3' => 'audio', 'ogg' => 'audio', 'wav' => 'audio', 'flac' => 'audio',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
            'mp4' => 'video', 'mov' => 'video', 'avi' => 'video', 'mpg' => 'video', 'ogv' => 'video', 'webm' => 'video',
            'pdf' => 'document',
        ];
        $ext = strtolower($f->getExtension());
        if (!isset($types[$ext])) {
            return null;
        }
        $src = $f->getPathname();
        $hash = sha1_file($src);
        if (!$hash) {
            return false;
        }

        $this->db->where('file_hash', $hash);
        if ($this->db->get_one('media')) {
            return 'skip';
        }

        $type = $types[$ext];
        $location = $this->models->media('rand_file_location');
        // Insert placeholder, then store file as "<id>.<ext>" and update filename
        // to match -- Observer reads filename to locate the source for previews.
        $now = time();
        $id = $this->db->insert('media', [
            'title' => $f->getBasename('.' . $ext),
            'type' => $type,
            'category_id' => $categoryIds[$type] ?? null,
            'is_approved' => 1, 'comments' => '', 'filename' => '',
            'file_hash' => $hash, 'file_location' => $location, 'format' => $ext,
            'is_copyright_owner' => 1, 'owner_id' => $this->adminUserId,
            'created' => $now, 'updated' => $now,
            'is_archived' => 0, 'status' => 'public', 'dynamic_select' => 0,
        ]);
        if (!$id) {
            return false;
        }
        $disk = $id . '.' . $ext;
        $dest = OB_MEDIA . '/' . $location[0] . '/' . $location[1] . '/' . $disk;
        if (!@copy($src, $dest)) {
            $this->db->where('id', $id);
            $this->db->delete('media');
            return false;
        }
        $this->db->where('id', $id);
        $this->db->update('media', ['filename' => $disk]);

        if ($type === 'image') {
            $this->stageImageThumbnail($src, $id, $location, $ext);
        }
        return true;
    }

    /**
     * Workaround for an OB core bug at core/support/Helpers.php:192 where
     * `new Imagick()` is unqualified inside the OpenBroadcaster\Support
     * namespace, so first-time thumbnail generation fatals. We sidestep it by
     * pre-staging a "cached thumbnail" using the source image. Media's
     * thumbnail_file() returns early on glob match (see
     * core/models/Media.php:343-347) so the buggy path never fires. When the
     * core is fixed this method can be removed.
     */
    private function stageImageThumbnail(string $src, int $id, string $location, string $ext): void
    {
        $dir = OB_CACHE . '/thumbnails/media/' . $location[0] . '/' . $location[1];
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @copy($src, $dir . '/' . $id . '.' . $ext);
    }
}
