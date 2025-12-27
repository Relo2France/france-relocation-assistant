=== France Relocation GitHub Sync ===
Contributors: relo2france
Tags: github, sync, auto-update
Requires at least: 5.8
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later

Automatically syncs France Relocation plugins from GitHub by polling for changes.

== Description ==

This plugin works around WordPress.com's webhook limitations by polling GitHub every 15 minutes for new commits. When changes are detected, it automatically updates the France Relocation plugins.

**How it works:**

1. Every 15 minutes, checks GitHub API for latest commit on `main` branch
2. Compares to last known commit
3. If different, triggers plugin update via WP Pusher
4. Automatically reactivates plugins after update

**Requirements:**

* WP Pusher plugin installed
* France Relocation Assistant and Member Tools installed via WP Pusher

== Installation ==

1. Install via WP Pusher from the same repository
2. Activate the plugin
3. Check status at Tools → GitHub Sync

== Changelog ==

= 1.0.0 =
* Initial release
* Polls GitHub every 15 minutes
* Auto-updates via WP Pusher integration
* Manual sync button in admin
* Auto-reactivates plugins after update
