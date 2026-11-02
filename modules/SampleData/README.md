# Sample Data

Sets up a sample station on a fresh install: media, playlists, a player and a schedule.

Install the module in Admin > Modules, then run it from Admin > Import Sample Data, or from the command line:

```
cli/ob sample data import en_community_radio
```

It needs the PHP zip extension. Only an administrator can import. The command imports as the first administrator and prints the player's sync password once.

It can be run again, and only adds what's missing. An install with its own media, playlists or players has to confirm first (add `confirm` on the command line). It then gets everything in the profile added beside its content, except the login message, welcome page, custom fields and daypart rules. A playlist or player with a name the profile uses is left as it is. Nothing is ever deleted, including when the module is uninstalled.

## Profiles

A profile is a folder in `profiles/`, named with lowercase letters, numbers and underscores. No code changes are needed to add one.

| File | Needed | What it holds |
|---|---|---|
| `manifest.json` | Yes | `name` and `description` for the profile list |
| `media.json` | Yes | The pack's `url`, `sha256`, `size`, `comments`, and `items`: `key`, `file` (its path in the zip), `title`, `artist`, `album`, `year`, `category`, `genre`, `dynamic_select`, `md5` |
| `playlists.json` | Yes | `name`, `description`, `type`, `items`, and `buttons` for Live Assist. An item is a media `key`, a `station_id` slot or a `dynamic` selection (`name`, `num_items`, `image_duration`, `query`). Images can only join a playlist through a dynamic selection |
| `schedule.json` | Yes | The `player` (`name`, `description`, `timezone`, `support_audio`, `support_video`, `support_images`, `default_playlist`, `station_id_keys`, `station_id_image_duration`), and `shows` with `playlist`, `start_time`, `duration`, `days`, and optionally `mode` (`daily`, `weekly` with `weekday`, `xdays` with `every`) |
| `categories.json` | No | Category names to add |
| `genres.json` | No | Genre names to add, under their category's name |
| `groups.json` | No | Permission groups: `name` and `permissions` |
| `settings.json` | No | `append_formats`, `client_login_message`, `client_welcome_page` |
| `metadata_fields.json` | No | Custom fields, as core's Media Settings page saves them |
| `dayparting.json` | No | `description`, `type` (`date`, `time`), `start`, `end`, `filters`. Media matching `filters` is held back while the rule is in effect |

Names are matched ignoring case and punctuation, so `Hip-Hop` won't add a second genre next to `Hip Hop`.

The download is checked against the sha256 in `media.json`. If the pack changes, update `url`, `sha256`, `size` and each item's `file` and `md5` there.
