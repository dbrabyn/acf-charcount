# ACF Character Count Plugin

WordPress plugin that adds live character counters to ACF text-based fields in the admin UI.

## Overview
Displays character counts (current/max) beneath ACF fields to help content editors stay within recommended lengths. Supports configurable max lengths per field via a settings page or per-field configuration.

## Supported Field Types
- `text` — standard text input
- `textarea` — multi-line text
- `wysiwyg` — TinyMCE/visual editor (count stripped HTML)
- All of the above inside **repeater**, **group** and **flexible content** subfields

## Tech Stack
- PHP 8.1+
- WordPress 6.x
- ACF Pro or ACF Free (required dependency)
- Vanilla JS for admin UI (no jQuery dependency if possible, fallback to jQuery if needed for ACF compatibility)
- No build tools — keep it simple, single JS file enqueued in admin
- [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) v5.6 (bundled in `lib/`) for automatic updates via GitHub Releases

## Conventions
- Follow WordPress Coding Standards (PHP and JS) and design standards
- Plugin slug: `acf-charcount`
- Text domain: `acf-charcount`
- Function prefix: `acf_cc_`
- Supported field types defined once in `ACF_CC_SUPPORTED_FIELD_TYPES` constant — used by both PHP and JS
- All user-facing strings must be translatable via `__()` and `_e()`
- Escape all output: `esc_html()`, `esc_attr()`, etc.
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Use nonces for all form submissions
- PHPDoc blocks on all functions and classes
- Admin settings stored in `wp_options` with prefix `acf_cc_`

## File Structure
```
acf-charcount/
├── acf-charcount.php          # Main plugin file, hooks, activation, update checker
├── includes/
│   ├── class-settings.php     # Settings page registration & rendering
│   ├── class-counter.php      # Core counter logic, field detection
│   └── class-field-config.php # Per-field max length configuration
├── admin/
│   ├── js/
│   │   └── acf-charcount.js   # Live counter JS for admin
│   └── css/
│       └── acf-charcount.css  # Counter styling
├── lib/
│   └── plugin-update-checker/ # Bundled update checker library (v5.6)
├── languages/
│   ├── acf-charcount.pot      # Translation template
│   ├── acf-charcount-fr_FR.po # French translation (editable)
│   └── acf-charcount-fr_FR.mo # French translation (compiled)
└── CLAUDE.md                  # This file
```

## Architecture Notes
- JS attaches counters via ACF's JavaScript API (`acf.addAction('ready_field/type=text', ...)`)
- For WYSIWYG fields, listen to TinyMCE `keyup`/`change` events and strip HTML before counting
- For repeater/flexible content: use ACF's `append` action to attach counters to dynamically added rows
- Settings page under Settings menu
- Per-field config: use field instructions to specify max length (e.g., `[maxchars:280]` in instructions field)
- Plugin Update Checker loaded only in admin (`is_admin()` guard) to avoid frontend overhead
- Class instances stored in `$GLOBALS['acf_cc']` for debugging and extensibility

## Automatic Updates
- Uses YahnisElsts/plugin-update-checker, bundled in `lib/plugin-update-checker/`
- Checks GitHub Releases on `dbrabyn/acf-charcount` repo
- Compares `Version:` plugin header against the latest release tag

### Release Workflow
1. Bump `Version:` in plugin header and `ACF_CC_VERSION` constant
2. Commit and push to `main`
3. Create a GitHub Release with a matching tag (e.g., `v1.1.0`)
4. WordPress sites pick up the update on their next update check (twice daily)

## Testing
- No test framework initially — keep it simple
- Test manually against: single fields, repeaters with 3+ rows, flexible content with mixed layouts, WYSIWYG in both visual and text modes
- Agent tasks should be self-contained and not require manual browser testing

## Git Workflow
- Main branch: `main`
- Feature branches: `feature/description`
- Agent branches: `agent/description-MMDD`
- Conventional commits: `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`

## Goals
1. Help content editors stay within character limits without leaving the edit screen
2. Work seamlessly with existing ACF workflows — no extra clicks, no configuration required to get started but provide a settings page to configure the plugin
3. Character limit is set by the ACF field's settings, not by the plugin
4. Be lightweight — no build tools, minimal performance impact on admin pages
5. Counter should appear in the x / xx characters format and be displayed according to plugin settings page option: below-right or below-left
6. Plugin settings page should include an option to display character count for fields without a character limit set

## Deliverables
- [x] Working plugin installable via zip upload
- [x] Live character counters on text, textarea, and WYSIWYG fields
- [x] Counters work inside repeater and flexible content sub-fields at any nesting depth
- [x] Per-field max length via [maxchars:N] in field instructions
- [x] Visual warning state when count exceeds max
- [x] French translation (.po/.mo files)
- [x] Automatic updates via GitHub Releases

## Non-Goals
- No frontend output — this is admin-only
- No integration with Gutenberg/block editor (ACF fields only)
- No word count — characters only for v1
- No enforcement/blocking — counters are advisory, not restrictive
- No WordPress.org plugin directory submission
