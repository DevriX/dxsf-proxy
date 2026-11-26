# DXSF Proxy

An internal DevriX tool. This WordPress plugin connects a client site to the DevriX Stability Framework, which monitors site stability and health. It is installed and set up by the DevriX team only; it is not meant for general use.

## Requirements

- WordPress 6.0 or newer
- PHP 7.4 or newer
- WP-Cron running, or a system cron calling `wp cron event run --due-now`

## Installation

1. Install and activate the plugin from the latest release.
2. Connect the site with the command shown for it in the Stability Framework dashboard.

The internal guide covers the details.

## WP-CLI

| Command | What it does |
|---------|--------------|
| `wp dxsf connect` | Connect the site (the full command comes from the dashboard) |
| `wp dxsf push` | Send the site report now |
| `wp dxsf status` | Connection status and the last and next report |

## Development

```bash
npm install
npm run build
./zip.sh dxsf-wordpress-proxy.zip
```

Release: publish the GitHub release with the asset `dxsf-wordpress-proxy.zip` in the same step as the version bump on `master`, since installed sites update from it.

## Changelog

### 3.0.1

- The release zip holds a `dxsf-wordpress-proxy` folder, the name 2.x installs have, so the update replaces the plugin in place
- Updates keep the folder the plugin is installed in
- The error log setting refuses stream wrappers and PHP or config files
- The monitoring settings are registered only for DevriX administrators
- Saving the connection and Send inventory now return to the settings page instead of a blank page

### 3.0.0

- Connection to Stability Framework v2, with signed requests in both directions
- WP-CLI commands
- Settings page reworked
- Requires WordPress 6.0 and PHP 7.4
- Hardening and coding standards fixes
