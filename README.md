# DXSF Proxy (WordPress plugin)

WordPress plugin that exposes REST API endpoints so the [DX Stability Framework](https://github.com/DevriX/dx-stability-framework) can read site-internal data without SSH access to the server.

**Full guide (integration, uptime, company-wide):** [Stability Reporting on DevriX Verse](https://devrixverse.com/knowledgebase/stability-reporting-how-to-have-your-site-monitored-by-the-framework/) → links to the shared Google Doc.

## Related repositories

| Repository | Role |
|------------|------|
| **[dx-stability-framework](https://github.com/DevriX/dx-stability-framework)** | Core library that calls this plugin and runs stability checks |
| **[dxsf-client-rules](https://github.com/DevriX/dxsf-client-rules)** | Per-project cron scripts that orchestrate checks and Slack alerts |
| **[dxsf-proxy](https://github.com/DevriX/dxsf-proxy)** (this repo) | Plugin installed on each monitored WordPress site |

## How it fits in

The Stability Framework server runs cron jobs from [dxsf-client-rules](https://github.com/DevriX/dxsf-client-rules). Those scripts use the [core](https://github.com/DevriX/dx-stability-framework) to:

- Fetch public HTML (GTM, noindex, etc.)
- Call **this plugin** for WordPress-only data: error logs, plugin/core/theme versions, user lists

Without dxsf-proxy on a site, proxy-backed rules in client-rules will not return data.

## Installation

1. Download the latest release from the [releases page](https://github.com/DevriX/dxsf-proxy/releases).
2. Install and activate the plugin on the WordPress site.
3. Open **DXSF Settings** in the WordPress admin and configure the error log path or endpoint URL.

> **Important:** Log in with a `@devrix.com` email, or set `DXSF_DEBUG` to `true` in `dxsf-proxy.php` for local testing.

## Development

```bash
npm install
npm run build   # if using webpack assets
```

Plugin entry point: `dxsf-proxy.php`

Handlers live under `includes/classes/handlers/` (error log, core/theme/plugin versions, users).

## Releases

Tag and publish via GitHub releases; client sites should update from the releases page rather than cloning this repository directly.
