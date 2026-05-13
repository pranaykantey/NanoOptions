<?php
/**
 * Plugin Name: NanoOptions
 * Description: A lightweight WordPress option framework.
 * Version: 1.0.0
 * Author: Your Name
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: nano-options
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main NanoOptions class.
 */
class NanoOptions {

	/**
	 * Holds the singleton instance.
	 *
	 * @since 1.0.0
	 * @var NanoOptions
	 */
	private static $instance = null;

	/**
	 * Plugin configuration.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private $config = array();

	/**
	 * Constructor.
	 *
	 * @since 1.0.0
	 * @access private
	 */
	private function __construct() {}

	/**
	 * Initialize the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param array $config {
	 *     Array of configuration options.
	 *
	 *     @type string $menu_title  Menu title.
	 *     @type string $menu_slug   Menu slug.
	 *     @type string $option_name Option name.
	 * }
	 */
	public static function init( array $config = array() ) {
		if ( null === self::$instance ) {
			self::$instance = new self();
			self::$instance->config = wp_parse_args( $config, array(
				'menu_title' => 'NanoOptions',
				'menu_slug'  => 'nano-options',
				'option_name'=> 'nano_options',
			) );

			// Only load framework in admin to save memory.
			if ( is_admin() ) {
				require_once plugin_dir_path( __FILE__ ) . 'framework/framework.php';
				NanoOptions_Framework::init( self::$instance->config );
			}
		}

		return self::$instance;
	}

	/**
	 * Get the singleton instance.
	 *
	 * @since 1.0.0
	 * @return NanoOptions
	 */
	public static function get_instance() {
		return self::$instance;
	}

	/**
	 * Get the configuration.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_config() {
		return $this->config;
	}

	/**
	 * Register a section.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Array of section arguments.
	 *
	 *     @type string $id    Section ID.
	 *     @type string $title Section title.
	 * }
	 */
	public static function section( array $args ) {
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'framework/framework.php';
			NanoOptions_Framework::section( $args );
		}
	}

	/**
	 * Register a field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args {
	 *     Array of field arguments.
	 *
	 *     @type string $id          Field ID.
	 *     @type string $title       Field title.
	 *     @type string $section_id  Section ID to add field to.
 	 *     @type string $type        Field type (default: text).
	 *     @type mixed  $default     Default value.
 	 *     @type string $description Field description.
 	 *     @type array  $attributes  HTML attributes.
 	 * }
	 */
	public static function field( array $args ) {
		if ( is_admin() ) {
			require_once plugin_dir_path( __FILE__ ) . 'framework/framework.php';
			NanoOptions_Framework::field( $args );
		}
	}
}

// Initialize the plugin with sample configuration.
function nano_options_init() {
	$instance = NanoOptions::init([
		'menu_title' => 'Settings',
		'menu_slug'  => 'nano-options',
		'option_name'=> 'nano_options',
	]);
	
	// Register sample sections with tabs.
	NanoOptions::section([
		'id'    => 'general',
		'title' => 'General Settings',
		'tab'   => 'general',
	]);
	
	NanoOptions::section([
		'id'    => 'advanced',
		'title' => 'Advanced Settings',
		'tab'   => 'advanced',
	]);
	
	// Register sample fields.
	NanoOptions::field([
		'id'          => 'site_title',
		'title'       => 'Site Title',
		'section_id'  => 'general',
		'type'        => 'text',
		'default'     => '',
		'description' => 'Enter the title of your site.',
	]);
	
	NanoOptions::field([
		'id'          => 'tagline',
		'title'       => 'Tagline',
		'section_id'  => 'general',
		'type'        => 'text',
		'default'     => '',
		'description' => "In a few words, explain what this site is about.",
	]);
	
	NanoOptions::field([
		'id'          => 'site_mode',
		'title'       => 'Site Mode',
		'section_id'  => 'general',
		'type'        => 'select',
		'default'     => 'production',
		'description' => 'Select the site mode.',
		'args'        => [
			'options' => [
				'development' => 'Development',
				'staging'     => 'Staging',
				'production'  => 'Production',
			]
		]
	]);
	
	NanoOptions::field([
		'id'          => 'accent_color',
		'title'       => 'Accent Color',
		'section_id'  => 'general',
		'type'        => 'color',
		'default'     => '#0073aa',
		'description' => 'Choose an accent color for your site.',
	]);
	
	NanoOptions::field([
		'id'          => 'logo_image',
		'title'       => 'Logo Image',
		'section_id'  => 'general',
		'type'        => 'media',
		'default'     => '',
		'description' => 'Upload a logo image for your site.',
	]);
	
	NanoOptions::field([
		'id'          => 'advanced_text',
		'title'       => 'Advanced Text Setting',
		'section_id'  => 'advanced',
		'type'        => 'text',
		'default'     => '',
		'description' => 'This is an advanced setting in the second tab.',
	]);
	
	// Example of conditional field - only show when checkbox is checked.
	NanoOptions::field([
		'id'          => 'enable_feature',
		'title'       => 'Enable Feature',
		'section_id'  => 'advanced',
		'type'        => 'checkbox',
		'default'     => '0',
		'description' => 'Check to enable the feature.',
	]);
	
	NanoOptions::field([
		'id'          => 'feature_options',
		'title'       => 'Feature Options',
		'section_id'  => 'advanced',
		'type'        => 'text',
		'default'     => '',
		'description' => 'Options for the feature (only visible when enabled).',
		'condition'   => [
			'field' => 'enable_feature',
			'value' => '1',
		]
	]);
}
add_action( 'plugins_loaded', 'nano_options_init' );