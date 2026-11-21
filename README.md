# DXSF Proxy (WordPress plugin)

WordPress plugin that connects a site to the DevriX Stability Framework. It reports the site's inventory (WordPress core, PHP, database, web server, plugins, themes) to the Framework twice a day and answers the Framework's signed requests for data only WordPress can see, such as the PHP error log.

**Full guide (integration, uptime, company-wide):** [Stability Reporting on DevriX Verse](https://devrixverse.com/knowledgebase/stability-reporting-how-to-have-your-site-monitored-by-the-framework/).

## Requirements

- WordPress 6.0 or newer, tested up to 7.1
- PHP 7.4 or newer
- WP-Cron running, or a system cron calling `wp cron event run --due-now`

## Installation

1. Download the latest release from the [releases page](https://github.com/DevriX/dxsf-proxy/releases).
2. Install and activate the plugin.
3. Connect the site, either through WP-CLI (preferred) or through **DXSF Settings** in the admin.

### Connect with WP-CLI

The Framework dashboard shows the command and the key for each site. Run the command and paste the key when it asks; the input is hidden and stays out of the shell history:

```bash
wp dxsf connect --core-url=https://sf.devrix.com --site-id=<site id>
```

Scripts can pipe the key in instead: `wp dxsf connect ... < key.txt`.

`connect` saves the connection, schedules the push and sends the first inventory.

### Connect from the admin

**DXSF Settings** is shown to administrators with a `@devrix.com` email. Fill in the core URL, the site ID and the site key, then save. The key is never shown again after saving; a blank key field keeps the stored key.

### Connect with constants

Hosts that manage configuration in code can set the connection in `wp-config.php`. Constants take precedence over the saved settings:

```php
define( 'DXSF_CORE_URL', 'https://sf.devrix.com' );
define( 'DXSF_SITE_ID', '<site id>' );
define( 'DXSF_SITE_KEY', '<site key>' );
```

## WP-CLI

| Command | What it does |
|---------|--------------|
| `wp dxsf connect --core-url=<url> --site-id=<id>` | Save the connection (key asked for with hidden input, piped in, or `--site-key`), schedule the push, send the inventory |
| `wp dxsf push` | Send the inventory now |
| `wp dxsf status` | Connection, status, last push and next scheduled push |
| `wp dxsf inventory` | Print the inventory the plugin would send, as JSON |

## How it talks to the Framework

- **Push:** twice a day through WP-Cron (`dxsf_proxy_push`), the plugin sends its inventory to the Framework. The first run lands at a random offset of 1 to 60 minutes, so a fleet update does not push all at once.
- **Pull:** the Framework calls the plugin's REST routes under `/wp-json/dxsf-proxy/v1/` when it needs fresh data, for example the error log. The namespace is hidden from the `/wp-json/` index.
- **Signed requests:** every request in both directions carries the site ID, a timestamp, a single-use nonce and an HMAC-SHA256 signature made with the site key. Requests older than five minutes or with a nonce already seen are rejected. The plugin accepts an answer from the Framework only when the answer is signed with the same key.
- **Status:** the Framework can pause or revoke a site. A revoked site stops pushing and refuses every request until it is connected again with a new key.
- **Legacy access:** the error log, version and user routes still accept the Framework server's IP from `dxsf_remote_address` (or `DXSF_REMOTE`) so 2.x monitoring keeps working during the move to 3.x. Clear that setting once the site is connected with a key.

## Settings

| Option | Meaning |
|--------|---------|
| `dxsf_core_url`, `dxsf_site_id`, `dxsf_site_key` | The connection. Set through WP-CLI, the settings page or the constants above. |
| `dxsf_error_log_file` | Path to the PHP error log, or a full URL. Read by the `error-log` route. |
| `dxsf_date_format` | Date prefix of a log line, default `[d-M-Y`. The route returns only lines from the requested day. |
| `dxsf_email_extensions` | Comma-separated email domains; the inventory lists only users whose email ends with one of them. |
| `dxsf_remote_address` | Legacy: IP of the 2.x Framework server. `DXSF_REMOTE` overrides it. |

Deactivation clears the scheduled push. Uninstall removes the plugin's options and scheduled events.

## Updates

The plugin updates itself through the standard WordPress updater (`includes/classes/updater/class-update.php`): it reads the `Version:` header of `dxsf-proxy.php` on `master`, and when that is newer than the installed version it offers `releases/latest/download/dxsf-wordpress-proxy.zip`.

- Bump the version on `master` and publish the GitHub release with the asset `dxsf-wordpress-proxy.zip` in the same step. A bump without a release makes every site download the previous zip.
- The repository and its releases stay reachable without a token, because the update check is unauthenticated.

## Development

```bash
npm install
npm run build
./zip.sh dxsf-wordpress-proxy.zip
```

Entry point: `dxsf-proxy.php`. Classes live in `includes/classes/`, REST handlers in `includes/classes/handlers/`.

## Changelog

### 3.0.0

- Inventory push to the Framework: core, PHP, database, web server, plugins (must-use included), themes, users
- Signed requests in both directions, with replay protection
- WP-CLI commands: `connect`, `push`, `status`, `inventory`
- Settings page reworked: connection status, "Send inventory now", the key is never echoed
- Requires WordPress 6.0 and PHP 7.4; tested up to WordPress 7.1
- Hardening: stricter permission checks on the REST routes and the settings page, prepared user queries, error log fixes
- Coding standards and translation loading fixes
