# NanoOptions

A lightweight, high-performance WordPress options framework for theme and plugin developers. Build complex admin panels with minimal code and maximum flexibility.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

## Features

- **Minimal Architecture**: ~2KB core, no dependencies, PHP 8+ native
- **WordPress-Native**: Uses Settings API, native UI components, proper security
- **Singleton Pattern**: Single initialization, memory efficient
- **Single Option Storage**: All settings in one database row for performance
- **Field Registry**: 6 field types included, extensible architecture
- **Tab System**: Automatic tab generation from section definitions
- **Conditional Fields**: Show/hide fields based on other field values
- **Import/Export**: JSON-based settings backup and restore
- **Conditional Asset Loading**: CSS/JS only loads on framework's settings page
- **Development-Friendly**: Clean code, well-commented, follows WordPress standards

## Installation

1. Upload the plugin files to `/wp-content/plugins/nano-options/`, or install via WordPress plugin installer
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use `NanoOptions::init()` in your theme or plugin to create a settings panel

## Quick Start

```php
// Initialize NanoOptions
NanoOptions::init([
    'page_title' => 'Theme Options',
    'menu_title' => 'Theme Settings',
    'menu_slug'  => 'theme-options',
    'option_name' => 'my_theme_options',
]);

// Register sections
NanoOptions::section([
    'id'       => 'general',
    'title'    => 'General Settings',
    'tab'      => 'Main',
    'callback' => function() { echo '<p>General options for your site.</p>'; }
]);

// Register fields
NanoOptions::field([
    'id'       => 'site_title',
    'section'  => 'general',
    'type'     => 'text',
    'title'    => 'Site Title',
    'default'  => get_bloginfo('name'),
    'sanitize' => 'sanitize_text_field'
]);

NanoOptions::field([
    'id'       => 'enable_features',
    'section'  => 'general',
    'type'     => 'checkbox',
    'title'    => 'Enable Features',
    'default'  => false
]);

NanoOptions::field([
    'id'       => 'color_scheme',
    'section'  => 'general',
    'type'     => 'select',
    'title'    => 'Color Scheme',
    'options'  => [
        'light' => 'Light',
        'dark'  => 'Dark',
        'auto'  => 'Auto'
    ],
    'default'  => 'light'
]);
```

## Architecture

```
nano-options/
├── nano-options.php          # Main bootstrap, singleton, API
├── framework/
│   ├── framework.php         # Core: registry, render, sanitize, assets
│   ├── fields/               # Field implementations
│   │   ├── text.php
│   │   ├── checkbox.php
│   │   ├── select.php
│   │   ├── color.php
│   │   ├── media.php
│   │   └── textarea.php
│   └── assets/
│       ├── admin.css
│       └── admin.js
├── README.md                 # This file
├── CHANGELOG.md              # Version history
├── LICENSE                   # MIT license
└── FINAL_SUMMARY.md          # Implementation reference

Total size: ~6KB
```

## API Reference

### `NanoOptions::init( array $config )`

Initializes the framework. Must be called before any other methods.

**Parameters:**
- `page_title` (string) - Title shown in browser tab
- `menu_title` (string) - Label shown in admin menu
- `menu_slug` (string) - URL slug for settings page
- `option_name` (string) - Database option name (key for single array)
- `parent_slug` (string, optional) - Parent menu slug to nest under
- `position` (int, optional) - Menu position
- `capability` (string, optional) - Required capability, default `manage_options`

**Example:**
```php
NanoOptions::init([
    'page_title' => 'Plugin Settings',
    'menu_title' => 'Plugin Options',
    'menu_slug'  => 'my-plugin-settings',
    'option_name' => 'my_plugin_options',
    'parent_slug' => 'options-general.php',
    'position'    => 100,
    'capability'  => 'manage_options'
]);
```

### `NanoOptions::section( array $args )`

Registers a settings section.

**Parameters:**
- `id` (string) - Unique section identifier
- `title` (string) - Section title displayed in settings page
- `tab` (string, optional) - Tab name to group sections
- `description` (string, optional) - Section description
- `callback` (callable, optional) - Custom callback for section output

**Example:**
```php
NanoOptions::section([
    'id'       => 'api_settings',
    'title'    => 'API Configuration',
    'tab'      => 'Integration',
    'callback' => function() {
        echo '<p>Enter your API credentials below.</p>';
    }
]);
```

### `NanoOptions::field( array $args )`

Registers a form field.

**Parameters:**
- `id` (string) - Unique field identifier (key in option array)
- `section` (string) - Section ID to attach field to
- `type` (string) - Field type: `text`, `checkbox`, `select`, `color`, `media`, `textarea`
- `title` (string) - Label displayed above field
- `description` (string, optional) - Help text shown below field
- `default` (mixed, optional) - Default value if not set
- `placeholder` (string, optional) - Placeholder text (text, textarea)
- `options` (array, optional) - Select options (key => label)
- `sanitize` (callable|string, optional) - Custom sanitization callback or WordPress sanitizer name
- `class` (string, optional) - Additional CSS class for wrapper
- `condition` (array, optional) - Conditional visibility rules (see below)

**Example:**
```php
NanoOptions::field([
    'id'          => 'api_key',
    'section'     => 'api_settings',
    'type'        => 'text',
    'title'       => 'API Key',
    'placeholder' => 'Enter your API key',
    'sanitize'    => 'sanitize_text_field',
    'condition'   => [
        'field'   => 'enable_api',
        'value'   => true,
        'compare' => '=='
    ]
]);
```

## Field Types

### Text (`text`)
Simple text input. Supports `placeholder` and `sanitize` args.

```php
NanoOptions::field([
    'id'          => 'site_logo',
    'section'     => 'general',
    'type'        => 'text',
    'title'       => 'Site Logo URL',
    'placeholder' => 'https://example.com/logo.png',
    'sanitize'    => 'esc_url_raw'
]);
```

### Checkbox (`checkbox`)
Boolean toggle. Stored as `true`/`false`. No sanitize arg needed (auto-handled).

```php
NanoOptions::field([
    'id'        => 'enable_comments',
    'section'   => 'discussion',
    'type'      => 'checkbox',
    'title'     => 'Enable Comments',
    'default'   => false
]);
```

### Select (`select`)
Dropdown selection. Requires `options` array (value => label).

```php
NanoOptions::field([
    'id'       => 'post_layout',
    'section'  => 'display',
    'type'     => 'select',
    'title'    => 'Post Layout',
    'options'  => [
        'full'  => 'Full Width',
        'sidebar' => 'Sidebar Right',
        'left' => 'Sidebar Left'
    ],
    'default'  => 'full'
]);
```

### Color (`color`)
Color picker using WordPress's native `wpColorPicker`.

```php
NanoOptions::field([
    'id'      => 'accent_color',
    'section' => 'design',
    'type'    => 'color',
    'title'   => 'Accent Color',
    'default' => '#0073aa'
]);
```

### Media (`media`)
Media uploader with preview. Uses WordPress Media Library.

```php
NanoOptions::field([
    'id'      => 'background_image',
    'section' => 'design',
    'type'    => 'media',
    'title'   => 'Background Image',
    'button'  => 'Select Image'
]);
```

### Textarea (`textarea`)
Multi-line text area. Supports `placeholder` and `sanitize`.

```php
NanoOptions::field([
    'id'          => 'custom_css',
    'section'     => 'advanced',
    'type'        => 'textarea',
    'title'       => 'Custom CSS',
    'placeholder' => '/* Enter your custom CSS */',
    'sanitize'    => function($value) {
        return wp_kses_post($value);
    }
]);
```

## Conditional Fields

Show/hide fields dynamically based on other field values. Conditionals use vanilla JS, no jQuery dependency.

**Syntax:**
```php
'condition' => [
    'field'   => 'parent_field_id',     // Field ID to watch
    'value'   => 'trigger_value',       // Value when field should show
    'compare' => '=='|'!='|'==='|'!==' // Comparison operator
]
```

**Example:**
```php
// This field only shows when enable_api equals true
NanoOptions::field([
    'id'        => 'api_endpoint',
    'section'   => 'api_settings',
    'type'      => 'text',
    'title'     => 'API Endpoint',
    'condition' => [
        'field'   => 'enable_api',
        'value'   => true,
        'compare' => '=='
    ]
]);
```

**Supported operators:** `==`, `!=`, `===`, `!==`

## Tabs

Tabbed interface groups sections automatically. Assign a tab name in the section definition:

```php
// Tab 1: General
NanoOptions::section([
    'id'       => 'general',
    'title'    => 'General Settings',
    'tab'      => 'General'
]);

NanoOptions::section([
    'id'       => 'social',
    'title'    => 'Social Media',
    'tab'      => 'General'
]);

// Tab 2: Advanced
NanoOptions::section([
    'id'       => 'advanced',
    'title'    => 'Advanced',
    'tab'      => 'Advanced'
]);
```

Tabs are rendered automatically as native WordPress nav-tabs. Sections without a `tab` parameter are grouped under "Main" tab by default.

## Import / Export

Backup and restore settings via JSON import/export. Built into the settings page as a meta-box.

**File Format:** JSON
**Validation:** Only fields registered with the framework are processed

**Export:**
- Click "Export Settings" button
- Downloads `.json` file with current option array
- Safe: only registered fields are exported

**Import:**
- Choose JSON file with valid NanoOptions backup
- Click "Import Settings"
- Settings are validated against registered field definitions
- Missing fields use registered defaults

## Security

- **Nonce verification** on all form submissions (import included)
- **Capability checks** using `current_user_can( 'manage_options' )`
- **ABSPATH checks** prevent direct access to framework files
- **Admin-only loading**: framework loads only on admin pages for memory efficiency
- **Sanitization**: All data sanitized before save via field-specific or custom callbacks
- **Escaping**: All output escaped using WordPress functions (`esc_attr()`, `esc_html()`, `esc_textarea()`)

## Performance

- **Single option array**: One database query to fetch all settings
- **Conditional asset loading**: CSS/JS loaded only on NanoOptions settings page
- **Minimal footprint**: ~6KB total size including CSS and JS
- **No external dependencies**: Pure PHP, WordPress core API only
- **Singleton pattern**: Only one instance initialized

## Extending

Add custom field types by creating a new file in `/framework/fields/`:

```php
<?php
// framework/fields/yourtype.php

class NanoOptions_Field_YOURTYPE extends NanoOptions_Field_Base {
    public function render( $field ) {
        ?>
        <input type="text"
               id="<?php echo esc_attr( $field['id'] ); ?>"
               name="nano_options[<?php echo esc_attr( $field['id'] ); ?>]"
               value="<?php echo esc_attr( $this->get_value( $field ) ); ?>"
               class="regular-text" />
        <?php
    }
}
```

The framework auto-discovers field classes in `/framework/fields/*.php` on initialization.

## Best Practices

1. **Prefix option names**: Use unique prefixes to avoid conflicts
   ```php
   'option_name' => 'mytheme_options' // ✓ Good
   'option_name' => 'options'         // ✗ Risky
   ```

2. **Provide defaults**: Always set default values for predictable behavior

3. **Sanitize appropriately**: Match sanitizer to field purpose
   - Text: `sanitize_text_field`
   - URL: `esc_url_raw`
   - HTML: `wp_kses_post`
   - Integer: `absint` or custom validation

4. **Use conditional fields**: Hide advanced options until needed to keep UI clean

5. **Group with tabs**: Use `tab` parameter to organize related sections

6. **Descriptive labels**: Clear titles and descriptions reduce support overhead

## Troubleshooting

**Fields not showing?**
- Check field `id` matches section reference in `section` parameter
- Verify section is registered before fields

**Data not saving?**
- Confirm `option_name` is consistent across init calls
- Check sanitization callbacks aren't rejecting valid data
- Verify nonce is generated on settings page (framework handles this)

**Conditionals not working?**
- Check field `id` referenced in `condition.field` exists
- Ensure compared value type matches stored type (boolean vs string)
- Compare operator matches expected logic (`==` vs `===`)

**Assets not loading?**
- Asset loading is conditional; only loads on framework's settings page
- Ensure you're viewing the correct admin page (matching `menu_slug`)
- No external dependencies; check browser console for conflicts

**Import failing?**
- JSON file must contain valid option array with correct structure
- Only fields registered with framework are imported
- Missing fields fall back to registered defaults

## Example Plugin

A complete working plugin demonstrating all NanoOptions features:

```php
<?php
/**
 * Plugin Name: My Plugin with NanoOptions
 * Description: Example integration
 * Version: 1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure NanoOptions is loaded (if not as standalone plugin)
// require_once plugin_dir_path(__FILE__) . '../nano-options/nano-options.php';

// Initialize
NanoOptions::init([
    'page_title' => 'My Plugin Settings',
    'menu_title' => 'My Plugin',
    'menu_slug'  => 'my-plugin-settings',
    'option_name' => 'my_plugin_options',
    'parent_slug' => 'options-general.php'
]);

// Sections
NanoOptions::section([
    'id'       => 'general',
    'title'    => 'General',
    'tab'      => 'Settings',
    'callback' => function() {
        echo '<p>Configure your plugin settings.</p>';
    }
]);

// Fields
NanoOptions::field([
    'id'        => 'enable_plugin',
    'section'   => 'general',
    'type'      => 'checkbox',
    'title'     => 'Enable Plugin',
    'default'   => true
]);

NanoOptions::field([
    'id'         => 'api_key',
    'section'    => 'general',
    'type'       => 'text',
    'title'      => 'API Key',
    'placeholder'=> 'Enter your API key',
    'sanitize'   => 'sanitize_text_field',
    'condition'  => [
        'field'   => 'enable_plugin',
        'value'   => true,
        'compare' => '=='
    ]
]);

// Retrieve values anywhere
$options = get_option('my_plugin_options');
if ( isset($options['enable_plugin']) && $options['enable_plugin'] ) {
    $api_key = $options['api_key'] ?? '';
    // Use $api_key...
}
```

## Version History

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## License

NanoOptions is open-source software licensed under the [MIT license](LICENSE).

## Support

For issues, feature requests, and contributions, please use the GitHub repository:
https://github.com/your-repo/nano-options
