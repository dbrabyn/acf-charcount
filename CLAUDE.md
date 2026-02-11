# ACF Character Count Plugin

WordPress plugin that adds live character counters to ACF text-based fields in the admin UI.

## Overview
Displays character counts (current/max) beneath ACF fields to help content editors stay within recommended lengths. Supports configurable max lengths per field via a settings page or per-field configuration.

## Supported Field Types
- `text` — standard text input
- `textarea` — multi-line text
- `wysiwyg` — TinyMCE/visual editor (count stripped HTML)
- All of the above inside **repeater** and **flexible content** sub-fields

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
