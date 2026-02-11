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
- ACF Pro (required dependency)
- Vanilla JS for admin UI (no jQuery dependency if possible, fallback to jQuery if needed for ACF compatibility)
- No build tools — keep it simple, single JS file enqueued in admin

## Conventions
- Follow WordPress Coding Standards (PHP and JS)
- Plugin slug: `acf-charcount`
- Text domain: `acf-charcount`
- Function prefix: `acf_cc_`
- All user-facing strings must be translatable via `__()` and `_e()`
- Escape all output: `esc_html()`, `esc_attr()`, etc.
- Sanitize all input: `sanitize_text_field()`, `absint()`, etc.
- Use nonces for all form submissions
- PHPDoc blocks on all functions and classes
- Admin settings stored in `wp_options` with prefix `acf_cc_`

## File Structure
```
acf-charcount/
├── acf-charcount.php          # Main plugin file, hooks, activation
├── includes/
│   ├── class-settings.php     # Settings page registration & rendering
│   ├── class-counter.php      # Core counter logic, field detection
│   └── class-field-config.php # Per-field max length configuration
├── admin/
│   ├── js/
│   │   └── acf-charcount.js   # Live counter JS for admin
│   └── css/
│       └── acf-charcount.css  # Counter styling
├── languages/                 # .pot file and translations
├── README.md                  # Plugin readme
├── readme.txt                 # WordPress.org readme format
└── CLAUDE.md                  # This file
```

## Architecture Notes
- JS attaches counters via ACF's JavaScript API (`acf.addAction('ready_field/type=text', ...)`)
- For WYSIWYG fields, listen to TinyMCE `keyup`/`change` events and strip HTML before counting
- For repeater/flexible content: use ACF's `append` action to attach counters to dynamically added rows
- Settings page under ACF menu (`acf-options-` subpage or standalone under Settings)
- Per-field config: add a custom field setting tab or use field instructions to specify max length (e.g., `[maxchars:280]` in instructions field)

## Testing
- No test framework initially — keep it simple
- Test manually against: single fields, repeaters with 3+ rows, flexible content with mixed layouts, WYSIWYG in both visual and text modes
- Agent tasks should be self-contained and not require manual browser testing

## Git Workflow
- Main branch: `main`
- Feature branches: `feature/description`
- Agent branches: `agent/description-MMDD`
- Conventional commits: `feat:`, `fix:`, `chore:`, `refactor:`, `docs:`

## Reference Code
See the `reference/` directory for code from a previous implementation. Use it as inspiration but rewrite from scratch following the conventions in this file. Do NOT copy-paste — the architecture should match our file structure above.


## Goals
1. Help content editors stay within character limits without leaving the edit screen
2. Work seamlessly with existing ACF workflows — no extra clicks, no configuration required to get started but provide a settings page to configure the plugin.
3. Character limit is set by the ACF field's settings, not by the plugin.
4. Be lightweight — no build tools, no external dependencies, minimal performance impact on admin pages
5. Be ready for WordPress.org plugin directory submission (proper readme.txt, i18n, coding standards)
6. Counter should appear in the x / xx characters format and be displayed according to plugin settings page option: alongside the field label or below the field value.
7. Plugin settings page should include an option to fidplay character count for fields without a character limit set.

## Deliverables
- [ ] Working plugin installable via zip upload
- [ ] Live character counters on text, textarea, and WYSIWYG fields
- [ ] Counters work inside repeater and flexible content sub-fields at any nesting depth
- [ ] Per-field max length via [maxchars:N] in field instructions
- [ ] Visual warning state when count exceeds max
- [ ] French translation (.po/.mo files)
- [ ] readme.txt in WordPress.org format
- [ ] Plugin passes WordPress coding standards (PHPCS with WordPress ruleset)

## Non-Goals
- No frontend output — this is admin-only
- No integration with Gutenberg/block editor (ACF fields only)
- No word count — characters only for v1
- No enforcement/blocking — counters are advisory, not restrictive
- No global default max lengths via settings page

## TBD
- Can the plugin be compatible with ACF Pro and ACF Free?
