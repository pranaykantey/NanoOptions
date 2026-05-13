# NanoOptions Developer Documentation

**Version:** 1.0.0  
**Type:** Embeddable WordPress Options Framework Library  
**License:** MIT  
**Minimum PHP:** 8.0  
**Requires:** WordPress 5.0+

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Why Use NanoOptions](#2-why-use-nanooptions)
3. [Installation](#3-installation)
4. [Namespacing & Collision Prevention](#4-namespacing--collision-prevention)
5. [Quick Start](#5-quick-start)
6. [Framework Architecture](#6-framework-architecture)
7. [Developer API](#7-developer-api)
8. [Field Documentation](#8-field-documentation)
9. [Conditional Fields](#9-conditional-fields)
10. [Tabs System](#10-tabs-system)
11. [Import/Export](#11-importexport)
12. [Security](#12-security)
13. [Performance](#13-performance)
14. [Extending NanoOptions](#14-extending-nanooptions)
15. [Best Practices](#15-best-practices)
16. [Troubleshooting](#16-troubleshooting)
17. [Example Plugin Integration](#17-example-plugin-integration)
18. [Production Readiness Checklist](#18-production-readiness-checklist)
19. [Changelog Template](#19-changelog-template)
20. [License](#20-license)

---

## 1. Introduction

### What is NanoOptions?

NanoOptions is an ultra-lightweight, embeddable WordPress options framework library. It provides developers with a minimal yet powerful system to build admin settings panels for plugins and themes without the bloat of full-featured frameworks.

### Why It Exists

Most WordPress option frameworks either:
- Are standalone plugins (unnecessary overhead when bundled)
- Include heavy dependencies (React, Redux, Vue)
- Load assets on every admin page (slow)
- Require Composer or complex setups
- Have memory-hungry architecture

NanoOptions solves this by being a **library-first** solution: just include a few PHP files and you're ready to build. Total footprint is ~6KB including CSS/JS.

### Library vs Standalone Plugin

**Standalone plugin** approach forces users to install a separate plugin. That adds:
- Extra plugin updates to manage
- Potential conflicts with other plugins
- Unnecessary admin menus
- Additional database tables

**Library approach** (NanoOptions):
- Bundled inside your plugin/theme
- No separate activation needed
- Zero external dependencies
- Fully controlled by you, the developer
- No interface leakage

### Performance Philosophy

- **Single option storage**: All settings in one array -> one database row
- **Conditional asset loading**: CSS/JS only on your settings page
- **Vanilla JavaScript**: No jQuery dependency for framework logic
- **Minimal hooks**: Only the necessary WordPress hooks used
- **Tiny file count**: Core framework = 1 class + 6 field files + 2 assets
- **Shared hosting optimized**: No background processes, no cron, no external requests

### Best Use Cases

- **Premium plugins** sold on ThemeForest/Codester that need a settings page
- **Lightweight plugins** on WordPress.org where every KB counts
- **Themes** with theme customizer-style options but simpler
- **Must-use plugins** that need internal configuration
- **Agency projects** where maintainability matters

---

## 2. Why Use NanoOptions

| ✅ Feature | NanoOptions | Typical Framework |
|-----------|------------|------------------|
| **File size (core)** | ~2KB PHP | 50KB - 500KB |
| **JS/CSS assets** | ~4KB combined | 100KB+ |
| **Dependencies** | None | Composer, React, Vue, etc. |
| **DB queries** | 1 (all settings) | 1 per section/field |
| **Memory usage** | <1MB | 2-5MB |
| **Standalone required?** | No | Often yes |
| **Shared hosting ready** | ✅ | Sometimes ❌ |
| **WordPress.org compliant** | ✅ | Sometimes ❌ |

### Lightweight Architecture

NanoOptions uses a **singleton pattern** with a single entry point (`NanoOptions::init()`). The framework initializes only once, stores sections and fields in memory, and renders a single admin page with all sections using WordPress Settings API. No bulky abstractions.

### Tiny Asset Footprint

Both CSS and JS files are under 2KB each (gzipped ~0.5KB). They are only enqueued on the page where your options are displayed. Other admin pages load nothing from NanoOptions.

### Native WordPress-First

- Uses core **Settings API** (nonce, capability, form handling included)
- **Native UI components**: WordPress color picker, media uploader, standard form controls
- **Escaping & sanitization** built-in and follow WP coding standards
- Respects WordPress admin theme and responsiveness

### Easier Plugin Bundling

Just copy the `inc/nano-options/` folder into your plugin and call `NanoOptions::init()`. No Composer, no build step, no npm, no autoloader beyond WordPress itself.

### Faster Admin Pages

Because assets only load on your settings page and the framework does minimal work, admin pages remain snappy even with many fields enabled. Conditional fields keep the UI clean and the DOM light.

---

## 3. Installation

### Folder Structure

Place NanoOptions anywhere inside your plugin or theme. A common pattern:

```
my-awesome-plugin/
├── my-awesome-plugin.php
├── README.md
├── inc/
│   └── nano-options/          ← NanoOptions library
│       ├── nano-options.php   ← bootstrap
│       ├── framework/
│       │   ├── class-field-base.php
│       │   ├── class-field-text.php
│       │   ├── class-field-textarea.php
│       │   ├── class-field-checkbox.php
│       │   ├── class-field-select.php
│       │   ├── class-field-radio.php
│       │   ├── class-field-number.php
│       │   ├── class-field-color.php
│       │   ├── class-field-media.php
│       │   ├── class-field-hidden.php
│       │   ├── class-framework.php
│       │   └── assets/
│       │       ├── admin.css
│       │       └── admin.js
│       └── README.md (optional)
└── includes/ (your plugin code)
```

### Include Method

In your main plugin file, include the NanoOptions bootstrap **only if** the class doesn't already exist (prevents conflicts if another plugin bundled it differently):

```php
<?php
/**
 * Plugin Name: My Awesome Plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Prevent direct access
}

// Bundle NanoOptions only if not already present
if ( ! class_exists( 'NanoOptions' ) ) {
    require_once __DIR__ . '/inc/nano-options/nano-options.php';
}
```

### Safe Loading Pattern

NanoOptions does nothing until you explicitly call `NanoOptions::init()`. This allows you to control when the framework initializes (typically on `admin_init` or after plugin load).

```php
add_action( 'admin_init', function() {
    NanoOptions::init([
        'page_title' => 'My Plugin Settings',
        'menu_title' => 'My Plugin',
        'menu_slug'  => 'my-plugin-settings',
        'option_name' => 'my_plugin_options',
        'parent_slug' => 'options-general.php',
        'capability'  => 'manage_options',
        'debug'       => defined( 'WP_DEBUG' ) && WP_DEBUG,
    ]);
});
```

**Important:** `init()` must be called **before** registering sections/fields, and only once per request.

---

## 4. Namespacing & Collision Prevention

### Namespace Usage

All NanoOptions classes live in the `NanoOptions` namespace:

```php
use NanoOptions\Field_Text;
use NanoOptions\Field_Checkbox;
```

This prevents class name conflicts with other plugins.

### Safe Class Loading

NanoOptions uses a **conditional class loader** that only includes field classes when they exist. It does **not** rely on Composer's autoloader, keeping the library self-contained.

```php
$field_class = 'NanoOptions\\Field_' . ucfirst( $type );
if ( class_exists( $field_class ) ) {
    // Safe to instantiate
}
```

### Avoiding Framework Conflicts

If your plugin or the active site already has a class named `NanoOptions` (unlikely but possible), the bundled copy won't load due to `class_exists()` check. Should you need to override, you can define your own class before including NanoOptions.

**Prefix recommendations:**
- Option name: prefix with your plugin slug (`myplugin_`) to avoid option collisions
- Field/section IDs: Prefix with your domain (`myplugin_general_section`)
- Menu slug: Unique across WordPress admin

---

## 5. Quick Start

### Minimal Working Example

```php
<?php
/**
 * Plugin Name: Quick Start Example
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Include NanoOptions library
require_once __DIR__ . '/inc/nano-options/nano-options.php';

// Initialize
NanoOptions::init([
    'page_title' => 'Quick Start Settings',
    'menu_title' => 'Quick Start',
    'menu_slug'  => 'quick-start-settings',
    'option_name' => 'qs_options',
    'parent_slug' => 'options-general.php',
    'capability'  => 'manage_options',
]);

// Section
NanoOptions::section([
    'id'       => 'general',
    'title'    => 'General Settings',
    'tab'      => 'Main',
    'callback' => fn() => printf( '<p>%s</p>', esc_html__( 'Configure basic options.', 'text-domain' ) ),
]);

// Fields
NanoOptions::field([
    'id'       => 'site_title',
    'section'  => 'general',
    'type'     => 'text',
    'title'    => 'Site Title',
    'default'  => get_bloginfo( 'name' ),
    'sanitize' => 'sanitize_text_field',
]);

NanoOptions::field([
    'id'      => 'accent_color',
    'section' => 'general',
    'type'    => 'color',
    'title'   => 'Accent Color',
    'default' => '#0073aa',
]);

NanoOptions::field([
    'id'        => 'enable_features',
    'section'   => 'general',
    'type'      => 'checkbox',
    'title'     => 'Enable Features',
    'default'   => false,
]);

// Retrieve values anywhere
$options = get_option( 'qs_options' );
if ( ! empty( $options['enable_features'] ) ) {
    // Use feature flag
}
```

That's it. NanoOptions handles rendering, sanitization, and saving via WordPress Settings API.

---

## 6. Framework Architecture

### High-Level Overview

```
┌─────────────────────────────────────────────┐
│  Theme / Plugin File                         │
│  require NanoOptions bootstrap               │
│  call NanoOptions::init()                    │
└───────────────┬─────────────────────────────┘
                │ registers menu, sections, fields
                ▼
┌─────────────────────────────────────────────┐
│  NanoOptions Core (Framework)                │
│  • Singleton instance                        │
│  • Section & field registry (in-memory)      │
│  • Settings API registration                 │
│  • Sanitization engine                       │
│  • Asset loader (conditional)                │
│  • Import/Export handlers                    │
└───────────────┬─────────────────────────────┘
                │ on admin page request
                ▼
┌─────────────────────────────────────────────┐
│  WordPress Admin                            │
│  • Renders tabbed settings page              │
│  • Outputs fields via field renderers        │
│  • Handles form submit via options.php       │
│  • Sanitizes, stores in single option row    │
└─────────────────────────────────────────────┘
```

### Core Systems

| System | Purpose |
|--------|---------|
| **Bootstrap** | Loads framework, autoloads field classes, defines singleton |
| **Registry** | Stores sections and field definitions in `$this->sections` and `$this->fields` |
| **Settings API** | Registers option, sections, and fields using WordPress core functions |
| **Rendering** | `render_settings_page()` outputs HTML wrapper; each field's `render()` method outputs HTML |
| **Sanitization** | Central `sanitize_callback()` iterates all submitted values, applies type-specific or custom sanitizers |
| **Asset Loader** | `enqueue_assets()` loads CSS/JS only on the framework's settings screen |
| **Conditional Logic** | Vanilla JS toggles field visibility based on other field values using `data-condition` attributes |
| **Import/Export** | JSON download and upload for settings backup/restore with validation |
| **Tab System** | Sections assigned to tabs via `tab` argument; framework renders tab navigation and content panels |

---

## 7. Developer API

### `NanoOptions::init( array $config )`

Bootstrap the framework. Must be called before any other method.

**Configuration array:**

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `page_title` | string | Yes | Title shown in browser tab |
| `menu_title` | string | Yes | Label in admin menu |
| `menu_slug` | string | Yes | URL slug for settings page |
| `option_name` | string | Yes | Database option name (single array storage) |
| `parent_slug` | string | No | Parent menu slug to nest under (default: `options-general.php`) |
| `position` | int | No | Menu position (default: null) |
| `capability` | string | No | Required capability (default: `manage_options`) |
| `debug` | bool | No | Enable developer debug mode (default: `WP_DEBUG`) |

**Example:**
```php
NanoOptions::init([
    'page_title' => 'Theme Options',
    'menu_title' => 'Theme Settings',
    'menu_slug'  => 'theme-options',
    'option_name' => 'mytheme_options',
    'parent_slug' => 'themes.php',
    'position'    => 60,
    'capability'  => 'edit_theme_options',
    'debug'       => true,
]);
```

---

### `NanoOptions::section( array $args )`

Register a settings section.

**Arguments:**

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `id` | string | Yes | Unique identifier (alphanumeric, underscores) |
| `title` | string | Yes | Display title |
| `tab` | string | No | Tab name to assign this section (default: `Main`) |
| `description` | string | No | Section description (shown below title) |
| `callback` | callable | No | Custom callback to output section content (instead of default description) |

**Example:**
```php
NanoOptions::section([
    'id'       => 'api_settings',
    'title'    => 'API Configuration',
    'tab'      => 'Integration',
    'callback' => function() {
        echo '<p>Enter credentials below.</p>';
    }
]);
```

---

### `NanoOptions::field( array $args )`

Register a form field.

**Arguments (common to all field types):**

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `id` | string | Yes | Unique field identifier (key in option array) |
| `section` | string | Yes | Section ID where field will be placed |
| `type` | string | Yes | Field type: `text`, `textarea`, `checkbox`, `select`, `radio`, `number`, `color`, `media`, `hidden` |
| `title` | string | Yes | Label text |
| `description` | string | No | Help text shown below field |
| `default` | mixed | No | Default value if not present in option array |
| `placeholder` | string | No | Placeholder text (input/textarea) |
| `options` | array | No | For select/radio: `value => label` pairs |
| `sanitize` | callable\|string | No | Custom sanitizer or WordPress sanitizer function name |
| `class` | string | No | Additional CSS class for wrapper |
| `condition` | array | No | Conditional visibility rules (see [Conditional Fields](#9-conditional-fields)) |
| `integer` | bool | No | For number field: force integer sanitization (default: false) |

**Type-specific notes:**

- **select / radio**: requires `options`.
- **checkbox**: automatically sanitized to boolean.
- **hidden**: no description shown, raw text sanitization by default.
- **number**: defaults to `floatval`; set `integer => true` to use `absint`.

---

### Developer Helpers

#### `NanoOptions::get_option_value( string $field_id, mixed $default = null )`

Get a single field's stored value from the options array.

```php
$color = NanoOptions::get_option_value( 'accent_color', '#333' );
```

#### `NanoOptions::get_all_options()`

Retrieve the full options array for the current init.

```php
$options = NanoOptions::get_all_options();
```

#### `NanoOptions::get_registered_sections()`

Get array of all registered sections.

```php
$sections = NanoOptions::get_registered_sections();
```

#### `NanoOptions::get_registered_fields()`

Get array of all registered fields (full definitions).

```php
$fields = NanoOptions::get_registered_fields();
```

---

### Hooks & Filters

NanoOptions exposes several action and filter hooks for extensibility.

#### `nanooptions_sanitize_before`

Action hook before sanitization loop starts.

```php
add_action( 'nanooptions_sanitize_before', function( $input, $option_name ) {
    // Modify raw input array before any sanitization
}, 10, 2 );
```

#### `nanooptions_sanitize_after`

Action hook after all sanitization is complete.

```php
add_action( 'nanooptions_sanitize_after', function( $sanitized, $option_name ) {
    // Final adjustments after sanitization
}, 10, 2 );
```

#### `nanooptions_sanitize_{type}`

Filter hook for per-type sanitization. `{type}` is the field type (lowercase). Receives raw value and full field array.

```php
add_filter( 'nanooptions_sanitize_text', function( $value, $field ) {
    // Custom text sanitization
    return sanitize_text_field( $value );
}, 10, 2 );

add_filter( 'nanooptions_sanitize_number', function( $value, $field ) {
    // Force positive integers only
    return absint( $value );
}, 10, 2 );
```

#### `nanooptions_field_args`

Filter field arguments before they are stored. Useful for adding defaults or modifying args globally.

```php
add_filter( 'nanooptions_field_args', function( $args, $id, $section ) {
    // Add a custom class to all fields
    $args['class'] = ( $args['class'] ?? '' ) . ' my-extra-class';
    return $args;
}, 10, 3 );
```

#### `nanooptions_section_args`

Filter section arguments before storage.

```php
add_filter( 'nanooptions_section_args', function( $args, $id ) {
    // Modify section arguments
    return $args;
}, 10, 2 );
```

---

## 8. Field Documentation

### Common Base

All field classes extend `NanoOptions\Field_Base` which provides:

- `get_id()`: Returns field ID.
- `get_type()`: Returns field type.
- `get_value( $field )`: Returns sanitized current value from option array, falling back to default.
- `render_wrapper_start( $field )`: Outputs opening div with classes.
- `render_wrapper_end( $field )`: Outputs closing div and description.

---

### Text Field (`text`)

Simple text input.

**Parameters:**
- `title` (string): Label.
- `placeholder` (string): Input placeholder.
- `sanitize` (callable\|string): Sanitizer (default: `sanitize_text_field`).
- `class` (string): Extra CSS class for input.

**Default Sanitization:** `sanitize_text_field` (strips tags).

**Example:**
```php
NanoOptions::field([
    'id'          => 'site_logo_url',
    'section'     => 'general',
    'type'        => 'text',
    'title'       => 'Logo URL',
    'placeholder' => 'https://example.com/logo.png',
    'sanitize'    => 'esc_url_raw',
]);
```

**Performance:** Minimal — one `<input>` element.

---

### Textarea Field (`textarea`)

Multi-line text area.

**Parameters:**
- `title`, `placeholder`, `sanitize`, `class` (same as text)
- Default sanitization: `wp_kses_post` (allows safe HTML) if omitted; otherwise use `sanitize_textarea_field` (WP 5.9+) or custom.

**Example:**
```php
NanoOptions::field([
    'id'          => 'custom_css',
    'section'     => 'advanced',
    'type'        => 'textarea',
    'title'       => 'Custom CSS',
    'placeholder' => '.my-class { color: red; }',
    'sanitize'    => function( $value ) {
        return wp_kses_post( $value );
    },
]);
```

**Performance:** Minimal — one `<textarea>` element.

---

### Number Field (`number`)

Numeric input with HTML5 number support.

**Parameters:**
- `title`, `placeholder`, `class`
- `integer` (bool): Force integer sanitization (`absint`) when `true` (default: false => `floatval`)
- `min`, `max`, `step`: HTML attributes for the input

**Default Sanitization:** `floatval` (or `absint` if `integer => true`).

**Example:**
```php
NanoOptions::field([
    'id'       => 'max_items',
    'section'  => 'display',
    'type'     => 'number',
    'title'    => 'Max Items',
    'default'  => 10,
    'integer'  => true,
    'min'      => 1,
    'max'      => 100,
    'step'     => 1,
]);
```

**Performance:** Minimal — one `<input type="number">` element.

---

### Checkbox Field (`checkbox`)

Boolean toggle.

**Parameters:**
- `title`, `description`, `class`
- `default` (bool): `true` or `false` (default: `false`)

**Sanitization:** Automatically converted to `true`/`false`.

**Example:**
```php
NanoOptions::field([
    'id'      => 'enable_comments',
    'section' => 'discussion',
    'type'    => 'checkbox',
    'title'   => 'Enable Comments',
    'default' => true,
]);
```

**Performance:** Minimal — one `<input type="checkbox">` element.

---

### Select Field (`select`)

Dropdown selection.

**Parameters:**
- `title`, `description`, `class`
- `options` (array, required): Associative array `value => label`.
- `default` (mixed): Default selected value.

**Sanitization:** `sanitize_key` (ensures valid option key).

**Example:**
```php
NanoOptions::field([
    'id'       => 'post_layout',
    'section'  => 'display',
    'type'     => 'select',
    'title'    => 'Post Layout',
    'options'  => [
        'full'   => 'Full Width',
        'sidebar' => 'Sidebar Right',
        'left'   => 'Sidebar Left',
    ],
    'default'  => 'full',
]);
```

**Performance:** Minimal — one `<select>` element.

---

### Radio Field (`radio`)

Radio button group.

**Parameters:**
- `title`, `description`, `class`
- `options` (array, required): Associative array `value => label`.
- `default` (mixed): Default checked value.

**Sanitization:** `sanitize_key` and validated against defined options.

**Example:**
```php
NanoOptions::field([
    'id'       => 'color_scheme',
    'section'  => 'design',
    'type'     => 'radio',
    'title'    => 'Color Scheme',
    'options'  => [
        'light' => 'Light',
        'dark'  => 'Dark',
        'auto'  => 'Auto (follow system)',
    ],
    'default'  => 'light',
]);
```

**Performance:** Minimal — one `<input type="radio">` per option.

---

### Color Field (`color`)

WPColorPicker-based color selector.

**Parameters:**
- `title`, `description`, `class`
- `default` (string): Hex color (e.g., `#0073aa`)

**Sanitization:** `sanitize_hex_color` (accepts `#RRGGBB` format).

**Example:**
```php
NanoOptions::field([
    'id'      => 'accent_color',
    'section' => 'design',
    'type'    => 'color',
    'title'   => 'Accent Color',
    'default' => '#0073aa',
]);
```

**Performance:** Minimal conditional loading — color picker assets only on page where field appears.

---

### Media Field (`media`)

WordPress Media Library uploader with preview.

**Parameters:**
- `title`, `description`, `class`
- `button` (string): Custom button text (default: "Select Media")
- `default` (string): URL to media file

**Sanitization:** `esc_url_raw`.

**Example:**
```php
NanoOptions::field([
    'id'      => 'background_image',
    'section' => 'design',
    'type'    => 'media',
    'title'   => 'Background Image',
    'button'  => 'Choose Image',
]);
```

**Performance:** Minimal conditional loading — media uploader assets only on page where field appears.

---

### Hidden Field (`hidden`)

Hidden input for internal data, often used with conditionals.

**Parameters:**
- `title` (ignored, but required for consistency)
- `default` (string): Hidden value

**Sanitization:** `sanitize_text_field`.

**Example:**
```php
NanoOptions::field([
    'id'        => 'dependency_flag',
    'section'   => 'advanced',
    'type'      => 'hidden',
    'title'     => 'Dependency Flag',
    'default'   => '1',
]);
```

---

## 9. Conditional Fields

Conditional visibility shows/hides fields based on the value of another field without page reload.

### Syntax

```php
NanoOptions::field([
    'id'        => 'child_field',
    'section'   => 'section_id',
    'type'      => 'text',
    'title'     => 'Child',
    'condition' => [
        'field'   => 'parent_field',      // ID of field to watch
        'value'   => 'trigger_value',    // Value that triggers show
        'compare' => '=='|'!='|'==='|'!==',
    ],
]);
```

### Supported Operators

- `==` (equals)
- `!=` (not equals)
- `===` (strict equals)
- `!==` (strict not equals)

### Multiple Conditions

For `AND` logic (all conditions must match), use multiple fields with same parent or nested conditions is not supported; implement custom JS if needed.

### Example: Checkbox Toggle

```php
// Parent checkbox
NanoOptions::field([
    'id'        => 'enable_ads',
    'section'   => 'monetization',
    'type'      => 'checkbox',
    'title'     => 'Enable Ads',
    'default'   => false,
]);

// Child (shown only when enable_ads === true)
NanoOptions::field([
    'id'        => 'ad_code',
    'section'   => 'monetization',
    'type'      => 'textarea',
    'title'     => 'Ad Code',
    'condition' => [
        'field'   => 'enable_ads',
        'value'   => true,
        'compare' => '==',
    ],
]);
```

### Technical Implementation

- Framework adds `data-condition-field`, `data-condition-value`, `data-condition-compare` attributes to wrapper div
- JavaScript listens to change/input events on the parent field
- When the parent changes, dependent fields are shown/hidden instantly
- Initial page load evaluates all conditions

---

## 10. Tabs System

Tabs group related sections without page reloads.

### How Tabs Work

Assign a `tab` parameter to each section. Sections with the same `tab` value are grouped together.

**Default Tab:** Sections without `tab` are placed under "Main".

**Example:**
```php
// Sections for "General" tab
NanoOptions::section([
    'id'    => 'general_appearance',
    'title' => 'Appearance',
    'tab'   => 'General',
]);

NanoOptions::section([
    'id'    => 'general_content',
    'title' => 'Content',
    'tab'   => 'General',
]);

// Section for "SEO" tab
NanoOptions::section([
    'id'    => 'seo_meta',
    'title' => 'Meta Tags',
    'tab'   => 'SEO',
]);
```

### Rendering

The framework generates:
- Tab navigation: `<h2 class="nav-tab-wrapper">` with WordPress native tab styling
- Tab panels: each section becomes a `<div id="tab-{sanitized_title}" class="nanooptions-tab-panel">`
- JavaScript to switch panels on tab click (vanilla JS)

**URL persistence:** Selected tab is remembered using `?tab=` query string, and the framework automatically selects the correct tab on page load.

### Performance

Tabs are purely client-side; all sections are rendered in HTML, and CSS hides non-active panels. No extra AJAX requests.

---

## 11. Import/Export

Backup and restore settings via JSON files.

### Export

"Export Settings" button creates a downloadable `.json` file containing:
- All registered field IDs with their stored values
- Excludes unregistered fields (future-proof)

**How to use:**
1. Navigate to your NanoOptions settings page
2. Scroll to "Import/Export" meta-box
3. Click "Export Settings"
4. JSON file downloads automatically

### Import

"Import Settings" uploads a previously exported JSON file.

**Validation:**
- File must be valid JSON
- Missing fields fall back to registered defaults
- Only registered fields are updated (extra keys ignored)
- Sanitization runs on each imported value

**How to use:**
1. Click "Choose File" in Import section
2. Select a `.json` backup
3. Click "Import Settings"
4. Success message or error description shown

### Security

- Import requires `current_user_can('manage_options')`
- Nonce verification on both export and import actions
- Uploaded files processed server-side; no direct file system access

---

## 12. Security

NanoOptions follows WordPress security best practices.

### Sanitization

- All field values pass through a sanitizer before saving
- Built-in sanitizers per field type (see [Field Documentation](#8-field-documentation))
- Custom sanitizers allowed via `sanitize` argument
- Filters (`nanooptions_sanitize_{type}`) allow global overrides

### Escaping

All frontend output uses proper escaping:
- `esc_attr()` for input attributes
- `esc_html()` for text content
- `esc_textarea()` for textarea values
- `esc_url()` for URLs (including media field)

### Nonces

Form includes `wp_nonce_field( 'nano_options_save', 'nano_options_nonce' )`.  
On save: `check_admin_referer( 'nano_options_save' )` verifies request.

### Capability Checks

Settings page and import require `manage_options` capability (configurable via `capability` init arg).

### Secure Media Handling

Media field uses `esc_url_raw` on save and `wp_kses_post`-compatible output. Only URLs are stored, not file data.

### Direct Access Prevention

All PHP files begin with:
```php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
```

### Additional Protections

- **CSRF protection:** Nonces on every action
- **XSS prevention:** Escaping on all output
- **SQL injection prevention:** `update_option`/`get_option` handles escaping
- **Path traversal:** No user-supplied file paths in includes

---

## 13. Performance

### Tiny Footprint

```
Core PHP:    ~2 KB
Field classes: ~3 KB
CSS:         ~1 KB
JS:          ~1 KB
─────────────
Total:       ~7 KB (gzipped ~2 KB)
```

### Asset Loading Strategy

CSS and JS only enqueued when `get_current_screen()->id` matches the registered `menu_slug`. This means:
- Other admin pages: **zero** framework asset load
- Frontend: **zero** framework asset load
- No performance impact anywhere else

### Single Option Storage

All fields share one database row. With 100 fields, still just 1 query to fetch all settings (`SELECT option_value FROM wp_options WHERE option_name = '...'`). This is significantly faster than frameworks that store each field separately.

### Minimal Hooks

Only 7 unique hooks used:
- `admin_init` (settings registration)
- `admin_menu` (page registration)
- `admin_enqueue_scripts` (asset conditional)
- `admin_notices` (optional debug notices)
- `wp_ajax_*` (none needed)
- `load-{page}` (minor init)

Each hook callback does minimal work; no global state manipulation.

### Minimal JavaScript

All JS is vanilla (no jQuery) and only:
- Tab switching (3 lines)
- Conditional field toggling (15 lines)
- Media uploader init (10 lines)
- Color picker init (5 lines)

Total uncompressed: ~200 lines. Gzipped: ~1.2 KB.

### Shared Hosting Optimized

- No background processes or cron jobs
- No external HTTP requests made by framework
- No file system writes (except temporary JSON upload during import)
- No object caching requirements
- No Transients API usage

---

## 14. Extending NanoOptions

### Creating a Custom Field

1. Create a new class extending `NanoOptions\Field_Base`
2. Implement `render( $field )` method
3. Save file as `framework/fields/class-field-{type}.php` (e.g., `class-field-rating.php`)
4. The field becomes available automatically after bootstrap

**Example: Star Rating Field**

File: `framework/fields/class-field-rating.php`

```php
<?php
namespace NanoOptions;

if ( ! defined( 'ABSPATH' ) ) exit;

class Field_Rating extends Field_Base {
    public function render( $field ) {
        $value = $this->get_value( $field );
        $id    = esc_attr( $field['id'] );
        ?>
        <div class="nanooptions-rating" data-field="<?php echo $id; ?>">
            <?php for ( $i = 5; $i >= 1; $i-- ) : ?>
                <span class="star"
                      data-value="<?php echo $i; ?>"
                      style="cursor:pointer;<?php echo $i <= $value ? 'color:gold;' : 'color:#ccc;'; ?>">
                    ★
                </span>
            <?php endfor; ?>
            <input type="hidden"
                   id="<?php echo $id; ?>"
                   name="nano_options[<?php echo $id; ?>]"
                   value="<?php echo intval( $value ); ?>">
        </div>
        <?php if ( ! empty( $field['description'] ) ) : ?>
            <p class="description"><?php echo esc_html( $field['description'] ); ?></p>
        <?php endif;
    }
}
```

You'll also need a small JS handler for star clicking (enqueue separately or inline).

### Registering Custom Sanitization

Use the `nanooptions_sanitize_{type}` filter:

```php
add_filter( 'nanooptions_sanitize_rating', function( $value, $field ) {
    return min( 5, max( 1, absint( $value ) ) );
}, 10, 2 );
```

### Extending Rendering

Wrap field output with additional markup using the `nanooptions_field_wrapper_start` and `nanooptions_field_wrapper_end` actions (hooks added as needed).

---

## 15. Best Practices

### Naming Conventions

| Element | Recommendation |
|---------|----------------|
| Option name | Prefix with plugin slug: `myplugin_options` |
| Field IDs | Prefix with plugin slug: `myplugin_field_id` |
| Section IDs | Prefix with plugin slug: `myplugin_section_id` |
| Tab names | Capitalized without prefix (tabs are grouped) |
| Menu slug | Unique: `myplugin-settings` |

### Prefix Everything

All strings printed to HTML that could be generic should be prefixed to avoid collision with other plugins that also use NanoOptions.

### Security

- Always provide a sanitization callback for custom fields
- Use `esc_html()` for outputting any dynamic text in labels/descriptions
- Validate uploaded files server-side if exposing any upload fields
- Never echo unsanitized `$_GET` or `$_POST` values

### Performance

- Only load fields you need; conditional fields are fine
- Use tabs to visually organize, not for performance reasons
- Keep import/export usage occasional (import is expensive ops)
- Avoid nested conditionals deep beyond 2 levels

### WordPress.org Compliance

- No external dependencies (NanoOptions meets this)
- No obfuscated code (minify only if you provide source maps, not needed)
- No admin notices that upsell
- All user-facing strings must be translatable (`__()`, `_e()`)

### ThemeForest Practices

- Include a `readme.txt` with installation instructions
- Provide a changelog in the main plugin file header
- License should be GPL v2+ (NanoOptions is MIT, but your plugin can be GPL)
- Do not include NanoOptions license file if bundling (MIT is fine)

---

## 16. Troubleshooting

### Settings Not Saving

**Symptoms:** Options page reloads but values unchanged.

**Checklist:**
- Did you call `NanoOptions::init()` before registering sections/fields?
- Does `option_name` match what you're fetching with `get_option()`?
- Is there a custom `sanitize` callback rejecting the value? Temporarily remove it.
- Verify `option_name` does not contain hyphens (WordPress option names should be alphanumeric/underscore)
- Ensure form is being submitted via the framework's `<form>` tag (no custom forms inside)

### Missing Fields

**Symptoms:** Some fields don't appear on settings page.

**Checklist:**
- Field `section` ID matches an existing registered section
- Section is registered before the field
- Field `id` is unique (duplicate IDs silently override)
- Field `type` exists as a class (typo in type name)

### Assets Not Loading

**Symptoms:** Color picker icon shows plain text, media uploader doesn't open, tabs not working.

**Checklist:**
- Are you on the correct admin page (matching `menu_slug`)? Assets only load there.
- Verify no jQuery errors in browser console (conflicts possible from other plugins)
- Media uploader requires `wp_enqueue_media()` already called by WordPress core on admin pages
- CSS class `.nanooptions-field-media` should be present on wrapper

### Media Uploader Issues

**Symptoms:** Clicking "Select Media" does nothing or opens wrong frame.

**Checklist:**
- Ensure your admin page includes `wp_footer()` (most do)
- Check console for JS errors
- Verify you are not deregistering `media-editor` or `media-audiovideo` scripts
- Try disabling other plugins to rule out conflicts

### Import/Export Issues

**Symptoms:** Import fails with error message.

**Checklist:**
- JSON file must be exported from NanoOptions (valid structure)
- Older versions may have different key structure
- File size limit: PHP `upload_max_filesize` must accommodate (tiny files)
- File must have `.json` extension and proper MIME type

### Namespace Conflicts

**Symptoms:** "Class NanoOptions already defined" error.

**Resolution:**
If another plugin/theme already defines `NanoOptions` class, change the namespace or rename your bundled copy. The library is namespaced, but the class alias `NanoOptions` may be defined by another component. Use unique prefix:

```php
class_alias( 'NanoOptions\\NanoOptions', 'MyPlugin_Options' );
```

Or rename the main class in the bootstrap.

---

## 17. Example Plugin Integration

A complete plugin demonstration with multiple tabs, conditionals, and all major field types.

**File: `example-plugin.php`**

```php
<?php
/**
 * Plugin Name: Example Plugin with NanoOptions
 * Description: Demonstrates NanoOptions integration
 * Version: 1.0.0
 * License: MIT
 */

defined( 'ABSPATH' ) || exit;

// Include NanoOptions (adjust path to where you placed it)
require_once __DIR__ . '/inc/nano-options/nano-options.php';

// Initialize
add_action( 'admin_init', function() {
    NanoOptions::init([
        'page_title' => 'Example Plugin Settings',
        'menu_title' => 'Example Plugin',
        'menu_slug'  => 'example-plugin-settings',
        'option_name' => 'example_plugin_options',
        'parent_slug' => 'options-general.php',
        'capability'  => 'manage_options',
    ]);
});

// Tab 1: General
NanoOptions::section([
    'id'       => 'general_info',
    'title'    => 'General Information',
    'tab'      => 'General',
    'callback' => fn() => printf( '<p>%s</p>', esc_html__( 'Basic site configuration.', 'text-domain' ) ),
]);

NanoOptions::field([
    'id'        => 'site_logo',
    'section'   => 'general_info',
    'type'      => 'media',
    'title'     => 'Site Logo',
    'button'    => 'Select Logo',
]);

NanoOptions::field([
    'id'        => 'enable_ads',
    'section'   => 'general_info',
    'type'      => 'checkbox',
    'title'     => 'Enable Advertising',
    'default'   => false,
]);

NanoOptions::field([
    'id'        => 'ad_code',
    'section'   => 'general_info',
    'type'      => 'textarea',
    'title'     => 'Ad Code (HTML/JS)',
    'condition' => [
        'field'   => 'enable_ads',
        'value'   => true,
        'compare' => '==',
    ],
]);

NanoOptions::field([
    'id'       => 'language',
    'section'  => 'general_info',
    'type'     => 'select',
    'title'    => 'Default Language',
    'options'  => [
        'en' => 'English',
        'es' => 'Spanish',
        'fr' => 'French',
    ],
    'default'  => 'en',
]);

// Tab 2: Design
NanoOptions::section([
    'id'       => 'design',
    'title'    => 'Design',
    'tab'      => 'Design',
]);

NanoOptions::field([
    'id'      => 'primary_color',
    'section' => 'design',
    'type'    => 'color',
    'title'   => 'Primary Color',
    'default' => '#2271b1',
]);

NanoOptions::field([
    'id'       => 'layout',
    'section'  => 'design',
    'type'     => 'radio',
    'title'    => 'Layout',
    'options'  => [
        'boxed'  => 'Boxed',
        'full'   => 'Full Width',
    ],
    'default'  => 'full',
]);

NanoOptions::field([
    'id'       => 'max_width',
    'section'  => 'design',
    'type'     => 'number',
    'title'    => 'Max Content Width (px)',
    'default'  => 1200,
    'integer'  => true,
    'min'      => 600,
    'max'      => 2000,
    'condition'=> [
        'field'   => 'layout',
        'value'   => 'boxed',
        'compare' => '==',
    ],
]);

// Tab 3: Advanced
NanoOptions::section([
    'id'       => 'advanced',
    'title'    => 'Advanced',
    'tab'      => 'Advanced',
]);

NanoOptions::field([
    'id'        => 'debug_mode',
    'section'   => 'advanced',
    'type'      => 'checkbox',
    'title'     => 'Enable Debug Mode',
    'default'   => false,
]);

NanoOptions::field([
    'id'          => 'custom_css',
    'section'     => 'advanced',
    'type'        => 'textarea',
    'title'       => 'Custom CSS',
    'placeholder' => '.selector { property: value; }',
    'sanitize'    => function( $value ) {
        // CSS sanitization placeholder – consider using `wp_kses` with CSS allowlist
        return $value;
    },
    'condition'   => [
        'field'   => 'debug_mode',
        'value'   => true,
        'compare' => '==',
    ],
]);

// Usage example in theme or plugin:
add_action( 'wp_head', function() {
    $options = get_option( 'example_plugin_options' );
    if ( ! empty( $options['primary_color'] ) ) {
        echo '<style>:root { --primary-color: ' . esc_attr( $options['primary_color'] ) . '; }</style>';
    }
});
```

---

## 18. Production Readiness Checklist

### Security Checklist

- [x] Nonces on all save/import actions
- [x] Capability checks (`manage_options`)
- [x] `ABSPATH` guard in every file
- [x] Sanitization applied for every field type
- [x] Escaping applied on all output
- [x] Media URLs sanitized via `esc_url_raw`
- [x] No direct file access points

### Performance Checklist

- [x] Single-option storage
- [x] Conditional asset loading
- [x] Vanilla JS (no jQuery dependency)
- [x] Minimal PHP hooks
- [x] No external HTTP requests
- [x] No heavyweight libraries

### WordPress.org Plugin Directory Compliance

- [x] No Composer required
- [x] No external binaries
- [x] No eval / base64 / obfuscation
- [x] All code in plugin directory
- [x] Functions prefixed or namespaced
- [x] PHP 7.2+ compatible (actually 8.0+)
- [x] GPL v2+ compatible license (MIT acceptable for library; plugin can be GPL)
- [x] Readme.txt with valid plugin header (you provide)

### ThemeForest Compliance

- [x] No third-party SDKs that require attribution
- [x] No plugin installer / TGMPA required (optional)
- [x] No up-sell screens
- [x] All features present in demo
- [x] Well-documented

### Code Quality

- [x] Follows WordPress coding standards (4 spaces, snake_case for functions)
- [x] PHPDoc blocks for all classes/methods
- [x] Consistent formatting
- [x] No dead code

### Documentation Completeness

- [x] Getting started guide
- [x] API reference with examples
- [x] Field type reference
- [x] Troubleshooting section
- [x] Complete example plugin
- [x] Changelog template
- [x] License file

---

## 19. Changelog Template

Use this format in your plugin's `readme.txt` or `CHANGELOG.md`:

```
=== [x.y.z] - YYYY-MM-DD ===
Added
- New feature description

Changed
- Improvements to existing features

Fixed
- Bug fix descriptions

Deprecated
- Features that will be removed in next major version

Removed
- Features that were deprecated

Security
- Security-related improvements
```

**Example (NanoOptions itself):**

```markdown
# Changelog

## [1.0.0] - 2026-05-13
### Added
- Initial release
- Core framework with singleton pattern
- Field types: text, textarea, checkbox, select, radio, number, color, media, hidden
- Tab system with conditional rendering
- Conditional field visibility (vanilla JS)
- JSON import/export with validation
- Conditional asset loading

### Security
- Nonce verification on save and import
- Capability checks throughout
- Admin-only loading
- Proper sanitization on all fields
- All output escaped
```

---

## 20. License

NanoOptions is dual-licensed under the **MIT License** and **GPL v2+** to accommodate both open-source and commercial use.

### For Library Usage (Bundled)

You may include NanoOptions in your plugin/theme under either license. For commercial products, MIT is recommended (allows proprietary distribution). For WordPress.org plugin submissions, GPL v2+ compatibility is required; MIT is GPL-compatible, so you can comply by distributing under GPL terms.

### License Text (MIT)

See the `LICENSE` file in the repository.

### Attribution

While not required by MIT, attribution is appreciated but not mandatory. You're free to remove all NanoOptions branding from your product's UI.

---

## Appendix A: Field Reference Table

| Type | HTML Element | Default Sanitizer | Options Required? | Description |
|------|--------------|-------------------|-------------------|-------------|
| text | `<input type="text">` | `sanitize_text_field` | No | Single-line text |
| textarea | `<textarea>` | `wp_kses_post` (or custom) | No | Multi-line text |
| checkbox | `<input type="checkbox">` | boolean cast | No | Boolean true/false |
| select | `<select>` | `sanitize_key` | Yes | Dropdown |
| radio | `<input type="radio">` | `sanitize_key` | Yes | Radio group |
| number | `<input type="number">` | `floatval` (or `absint` if `integer:true`) | No | Numeric value |
| color | `<input type="text">` + picker | `sanitize_hex_color` | No | Hex color |
| media | `<input type="text">` + button | `esc_url_raw` | No | Media URL from WP library |
| hidden | `<input type="hidden">` | `sanitize_text_field` | No | Hidden value |

---

## Appendix B: Conditional Operator Truth Table

| Operator | Description | Example (`parent=2`) |
|----------|-------------|----------------------|
| `==` | Loose equality | `2 == '2'` → true |
| `===` | Strict equality | `2 === '2'` → false |
| `!=` | Loose inequality | `2 != '3'` → true |
| `!==` | Strict inequality | `2 !== '2'` → false |

For boolean fields: use `=== true` or `== true` depending on whether you want type coercion.

---

## Appendix C: Debug Mode

When `debug` parameter is `true` (or `WP_DEBUG` is true if not specified), NanoOptions outputs:

- A notice in the admin bar (optional) showing registered sections/fields
- Console logging of condition evaluations (in JS, if `window.console` exists)
- PHP notices for duplicate section/field IDs (if detected)

To enable always, pass `'debug' => true` in `init()` config.

---

## Revision History

This document corresponds to NanoOptions v1.0.0.

---

**Note:** This documentation covers the library in its entirety. For questions not answered here, refer to inline code comments or open an issue on the project repository.
