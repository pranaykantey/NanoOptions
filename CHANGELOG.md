# Changelog

All notable changes to NanoOptions will be documented in this file.

## [1.0.0] - 2026-05-13

### Added
- Initial release
- Core framework with singleton architecture
- 6 field types: text, checkbox, select, color, media, textarea
- Tab system with automatic section grouping
- Conditional field visibility system
- Import/Export functionality (JSON)
- Conditional asset loading (CSS/JS only on settings page)
- WordPress coding standards compliance
- Comprehensive documentation

### Security
- Nonce verification on all form submissions
- Capability checks (manage_options)
- Admin-only framework loading
- Proper sanitization per field type
- Data escaping in all output
- ABSPATH direct access prevention

### Performance
- Single option array storage (one DB query)
- ~6KB total footprint including assets
- No external dependencies
- Vanilla JavaScript (no jQuery)

## [1.0.1] - Planned

### Planned Features
- Additional field types: number, radio, file, wysiwyg, repeater
- Section sorting/drag-drop ordering
- Undo/redo functionality
- Settings migration utilities
- WP-CLI commands for export/import
- Multisite support
- Developer mode with debugging
