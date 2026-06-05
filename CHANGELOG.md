# Changelog

All notable changes to this project will be documented in this file.

## [1.3.1] - 2026-06-05

### Changed
- Counter now sits to the right of the field instruction (or the label, when there is no instruction) instead of on its own line, keeping the area below the input clear for other plugins.
- Counter wording shortened from "characters" to "chars" (English; the French translation keeps "caractères").

### Removed
- "Counter Position" (below-right / below-left) setting. The counter now always renders beside the label, so the option no longer had any effect; existing stored values are ignored.

## [1.3.0] - 2026-06-05

### Changed
- Character counter moved out from below the field input to above it, so the area beneath the field stays clear for other plugins. (Refined in 1.3.1 to sit beside the label/instruction.)

### Fixed
- Server- and client-side counts now agree for plain text/textarea values containing HTML-entity-like text — entity decoding is limited to WYSIWYG fields, matching the JS counter.
- WYSIWYG editor registrations are released when a repeater/flexible-content row is removed, preventing a small internal map from growing as rows are added and removed.

### Added
- `aria-live="polite"` on the counter so screen readers announce the live count.

## [1.2.0] - 2026-05-08

### Fixed
- `load_plugin_textdomain()` moved from `plugins_loaded` to `init` to silence `_doing_it_wrong` notice on WordPress 6.7+.
- Server- and client-side character counts now agree on emoji and HTML entities. PHP decodes entities before counting; JS counts Unicode codepoints (`Array.from`) instead of UTF-16 code units.
- `[maxchars:N]` tag is hidden from the visible field instructions. The tag is parsed once via `acf/prepare_field` and stripped before render, so editors no longer see it.
- TinyMCE `AddEditor` listener no longer accumulates per WYSIWYG field — replaced with a single global handler that looks up registered fields.

### Added
- `uninstall.php` — deletes the `acf_cc_settings` option on plugin deletion (multisite-aware). Deactivate still preserves settings.
- Localized printf-style format strings (`%1$s / %2$s characters`, `%s characters`) replace the bag-of-words `characters` translation, allowing word reordering for languages that need it.

### Changed
- Plugin URI now points to the canonical `dbrabyn/acf-charcount` repo.
- `ACF_CC_Settings::get_all()` now caches the parsed option for the request, avoiding repeated `wp_parse_args()` work on flexible-content-heavy screens.
- Counter position class is no longer emitted by both PHP and JS — JS owns positioning end-to-end so the class doesn't appear twice in the DOM.

## [1.1.1] - 2026-05-08

### Fixed
- `TypeError: acf.$ is not a function` thrown when adding repeater/flex rows. ACF Pro 6.8.0.1 no longer exposes `acf.$` as a jQuery alias, so the IIFE now takes `jQuery` as `$` directly (`( function( $ ) { ... } )( jQuery );`).

## [1.1.0] - 2026-03-01

### Added
- Automatic updates via GitHub Releases using plugin-update-checker v5.6
- French translation (.pot, .po, .mo files)
- `.gitignore` file
- `ACF_CC_SUPPORTED_FIELD_TYPES` constant as single source of truth for supported field types

### Changed
- Update checker only loads in admin (is_admin guard) to avoid frontend overhead
- Class instances stored in `$GLOBALS['acf_cc']` for debugging and extensibility
- Simplified CSS by merging redundant counter style rules

## [1.0.0] - 2026-02-28

### Added
- Live character counters on ACF text, textarea, and WYSIWYG fields
- Counters inside repeater, group, and flexible content sub-fields at any nesting depth
- Per-field max length via `[maxchars:N]` in field instructions
- Visual warning state (red text) when count exceeds max
- Settings page under Settings menu with default character limits and display options
- Counter display modes: always show or only on fields with limits
- Counter position options: below-right or below-left
- WYSIWYG support for both visual and text mode with tab switching
- ACF dependency check with admin notice
