# My Article Listener

A free "Listen to this article" player for WordPress. Uses the browser's built-in speech engine (`speechSynthesis`) — no fees, no accounts, no monthly limits. Includes a settings page, voice picker, speed control, progress bar, and per-post on/off control.

## Installation

1. Download the latest release zip from the [Releases](https://github.com/ammar458/article-listener/releases) page.
2. In WordPress, go to **Plugins → Add New → Upload Plugin** and upload the zip.
3. Activate the plugin, then visit **Settings → Article Listener** to configure it.

## Updates

This plugin checks this GitHub repository for new releases and can be updated directly from the WordPress admin, just like a plugin hosted on WordPress.org.

To ship an update:
1. Bump the `Version` header in `my-article-listener.php`.
2. Commit and push to `main`.
3. Create a new [GitHub Release](https://github.com/ammar458/article-listener/releases/new) with a tag matching the version (e.g. `v1.3`).

Sites running the plugin will see the update in **Dashboard → Updates** shortly after.
