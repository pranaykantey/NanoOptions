<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NanoOptions Framework.
 */
class NanoOptions_Framework {

	/**
	 * Plugin configuration.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $config = array();

	/**
	 * Registered sections.
	 *
	 * @since 1.0.0
	 * @var array
	 */
	private static $sections = array();

	/**
	 * Initialize the framework.
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
		self::$config = wp_parse_args( $config, array(
			'menu_title' => 'NanoOptions',
			'menu_slug'  => 'nano-options',
			'option_name'=> 'nano_options',
		) );

		// Include field types.
		self::include_fields();

		// Register admin menu.
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );

		// Register settings.
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
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
		self::$sections[] = wp_parse_args( $args, array(
			'id'   => '',
			'title'=> '',
		) );
	}

	/**
	 * Include all field types.
	 */
	private static function include_fields() {
		$fields_dir = plugin_dir_path( __FILE__ ) . 'fields';
		if ( is_dir( $fields_dir ) ) {
			foreach ( glob( $fields_dir . '/*.php' ) as $field_file ) {
				require_once $field_file;
			}
		}
	}

	/**
	 * Register admin menu.
	 */
	public static function register_admin_menu() {
		add_options_page(
			self::$config['menu_title'],
			self::$config['menu_title'],
			'manage_options',
			self::$config['menu_slug'],
			array( __CLASS__, 'admin_page_html' )
		);
	}

	/**
	 * Register settings using the Settings API.
	 */
	public static function register_settings() {
		// Register a setting.
		register_setting(
			self::$config['option_name'], // Option group.
			self::$config['option_name'], // Option name.
			array( __CLASS__, 'sanitize_options' ) // Sanitize callback.
		);

		// Register each section.
		foreach ( self::$sections as $section ) {
			// Skip if ID or title is empty.
			if ( empty( $section['id'] ) || empty( $section['title'] ) ) {
				continue;
			}

			add_settings_section(
				$section['id'],
				$section['title'],
				'__return_false', // No section description.
				self::$config['menu_slug']
			);
		}
	}

	/**
	 * Sanitize options.
	 *
	 * @param array $input Option array.
	 * @return array Sanitized option array.
	 */
	public static function sanitize_options( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		return $input;
	}

	/**
	 * Admin page HTML.
	 */
	public static function admin_page_html() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Settings saved notice.
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( self::$config['option_name'], 'nano_options_message', __( 'Settings saved.', 'nano-options' ), 'updated' );
		}

		// Show settings errors.
		settings_errors( self::$config['option_name'] );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( self::$config['menu_title'] ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::$config['option_name'] );
				do_settings_sections( self::$config['menu_slug'] );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 */
	public static function enqueue_assets() {
		$plugin_url = plugins_url( '', __FILE__ );

		// Enqueue admin CSS.
		wp_enqueue_style(
			'nano-options-admin',
			$plugin_url . '/framework/assets/admin.css',
			array(),
			'1.0.0'
		);

		// Enqueue admin JS.
		wp_enqueue_script(
			'nano-options-admin',
			$plugin_url . '/framework/assets/admin.js',
			array( 'jquery' ),
			'1.0.0',
			true
		);
	}
}