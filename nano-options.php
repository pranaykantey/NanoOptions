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
}