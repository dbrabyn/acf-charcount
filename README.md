# ACF Character Count

[![Latest Release](https://img.shields.io/github/v/release/dbrabyn/acf-charcount)](https://github.com/dbrabyn/acf-charcount/releases/latest)
[![License: GPL v2+](https://img.shields.io/badge/License-GPLv2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A lightweight WordPress plugin that adds live character counters to Advanced Custom Fields (ACF) text-based fields in the admin dashboard. Helps content editors stay within recommended character limits without leaving the edit screen.

## Features

- **Live character counting** on text, textarea, and WYSIWYG (visual editor) fields
- **Works inside repeaters, groups, and flexible content** at any nesting depth — counters appear automatically on dynamically added rows
- **Per-field character limits** using ACF's built-in maxlength setting or a simple `[maxchars:N]` tag in field instructions
- **Visual warning** when the character count exceeds the limit (counter turns red)
- **Configurable display** — choose to show counters on all fields or only on fields with a limit set
- **Counter position options** — below the field, aligned left or right
- **Automatic updates** — your WordPress sites are notified when a new version is released
- **French translation** included, with support for additional languages

## Requirements

- WordPress 6.0 or higher
- PHP 8.1 or higher
- [Advanced Custom Fields](https://www.advancedcustomfields.com/) (Pro or Free)

## Installation

1. Download the latest release zip from the [Releases page](https://github.com/dbrabyn/acf-charcount/releases/latest)
2. In your WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Choose the downloaded zip file and click **Install Now**
4. Activate the plugin

That's it — character counters will appear on your ACF fields immediately with no additional setup required.

## Configuration

### Settings Page

Go to **Settings > ACF Character Count** to configure the plugin:

- **Default Character Limits** — Set default maximum character counts for each field type (text, textarea, WYSIWYG). These apply when no per-field limit is set. Use `0` for no limit.
- **Counter Display** — Choose between:
  - *Always* — show counters on all supported fields
  - *Configured only* — show counters only on fields that have a character limit set
- **Counter Position** — Display counters below the field, aligned to the right (default) or left.

### Per-Field Limits

You can set character limits on individual fields in two ways:

1. **ACF's maxlength setting** — In your field group editor, set the "Character Limit" option on a text or textarea field. The counter picks this up automatically.

2. **Instructions tag** — Add `[maxchars:N]` anywhere in a field's instruction text (where `N` is the limit). This works for all field types including WYSIWYG, which doesn't have a built-in maxlength setting.

   Example: Setting a field's instructions to `Keep this under 280 characters [maxchars:280]` will display a counter with a 280-character limit.

Per-field limits take priority over the defaults set in the settings page.

## Automatic Updates

This plugin checks for new releases automatically. When a new version is published, you'll see the standard "update available" notice on the **Plugins** page in your WordPress admin — just click **Update Now** like any other plugin.

## Translation

The plugin is fully translatable. A French (fr_FR) translation is included.

To add your own translation:

1. Use a tool like [Poedit](https://poedit.net/) to open `languages/acf-charcount.pot`
2. Translate the strings and save as `acf-charcount-{locale}.po` (e.g., `acf-charcount-de_DE.po`)
3. Compile to `.mo` format and place both files in the `languages/` directory

## License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
