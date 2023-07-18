# DXSF WordPress Proxy

This is a tool that exposes few REST API endpoints to allow WordPress to communicate with the Stability Framework.

## Installation
- Download the latest release from the [releases page](https://github.com/DevriX/dxsf-proxy/releases) and install it as a plugin in your WordPress installation.

- Go to the `DXSF Settings` page inside the dashboard and enter the path to the error log file or endpoint URL. This is the file that the Stability Framework is going to read from and report.
> IMPORTANT! Make sure you are logged in with a @devrix.com email or set the `DXSF_DEBUG` define to `true` that is located inside the `dxsf-proxy.php` file.

Wiki: https://devrixverse.com/knowledgebase/stability-reporting-how-to-have-your-site-monitored-by-the-framework/
