=== MGD MCP Bridge ===
Contributors: michaelgahndesign
Tags: mcp, abilities
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 0.5.11
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Connect an explicitly authorized MCP client through controlled WordPress capabilities.

== Description ==

Connect an explicitly authorized MCP client through controlled WordPress capabilities.

Features:

* Select a permission profile before allowing a client to read or change content.
* Use WordPress capabilities as the upper limit for every exposed operation.
* Keep writing, media import and maintenance actions explicitly configurable.
* Use the local connection doctor and audit tools to inspect the integration.
* This plugin does not provide an AI model or store an AI-provider API key.

This edition uses native WordPress.org updates. There is no GitHub self-updater or external plugin installer. Updates become available after approval and publication in the directory.

Documentation and source: https://github.com/MichaelGahnDESIGN/MGD_WordPress-MCP

The site owner selects the MCP client and authorizes access. That client receives content available to its user account. Review its privacy policy before connecting. Optional media imports contact the URL supplied by the authorized client.

== Installation ==

1. Upload the plugin folder or install the approved directory release from Plugins > Add New.
2. Activate the plugin and review its settings.
3. Test the configured features before enabling them on a production website.

== Frequently Asked Questions ==

= How are updates delivered? =
WordPress checks the official directory. This edition has no configurable update repository.

= Does it send analytics? =
No analytics or telemetry service is included. WordPress communicates with WordPress.org for native updates. Support links open only when clicked.

== Changelog ==

= 0.5.11 =
* Prepared a WordPress.org candidate with native updates, local assets and reviewed public credits.

== Upgrade Notice ==

= 0.5.11 =
Submission candidate. Do not activate alongside the GitHub edition. Back up the database before changing distribution channels.
