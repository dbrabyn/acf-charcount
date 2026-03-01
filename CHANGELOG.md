# Changelog

All notable changes to this project will be documented in this file.

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
