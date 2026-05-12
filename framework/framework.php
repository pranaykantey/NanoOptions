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

		// Enqueue admin assets.
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
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
	 * Admin page HTML.
	 */
	public static function admin_page_html() {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission.
		if ( isset( $_POST['nano_options_nonce'] ) && wp_verify_nonce( $_POST['nano_options_nonce'], 'nano_options_update' ) ) {
			if ( isset( $_POST['nano_options'] ) ) {
				$options = sanitize_text_field( wp_unslash( $_POST['nano_options'] ) );
				update_option( self::$config['option_name'], $options );
			}
		}

		$option_value = get_option( self::$config['option_name'], '' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html( self::$config['menu_title'] ); ?></h1>
			<form method="post">
				<?php wp_nonce_field( 'nano_options_update', 'nano_options_nonce' ); ?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Sample Option</th>
						<td>
							<input type="text" name="nano_options" value="<?php echo esc_attr( $option_value ); ?>" class="regular-text" />
							<p class="description">Enter a sample option value.</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
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