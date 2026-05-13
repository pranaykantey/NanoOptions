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
 * Registered fields.
 *
 * @since 1.0.0
 * @var array
 */
private static $fields = array();

/**
 * Flag to indicate if media uploader is needed.
 *
 * @since 1.0.0
 * @var bool
 */
private static $needs_media_uploader = false;

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
 		// Extract special parameters that go into field args.
 		$field_args = array();
 		if ( isset( $args['default'] ) ) {
 			$field_args['default'] = $args['default'];
 			unset( $args['default'] );
 		}
 		if ( isset( $args['description'] ) ) {
 			$field_args['description'] = $args['description'];
 			unset( $args['description'] );
 		}
 		if ( isset( $args['attributes'] ) ) {
 			$field_args['attributes'] = $args['attributes'];
 			unset( $args['attributes'] );
 		}
 		
 		self::$fields[] = wp_parse_args( $args, array(
 			'id'          => '',
 			'title'       => '',
 			'section_id'  => '',
 			'type'        => 'text',
 			'args'        => $field_args,
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
	 * Render a field.
	 *
	 * @since 1.0.0
	 *
	 * @param array $field Field data.
	 * @return void
	 */
	private static function render_field( $field ) {
		$field_id   = $field['id'];
		$field_type = $field['type'];
		$field_args = $field['args'];
		
		// Check if we need media uploader or color picker.
		if ( $field_type === 'media' ) {
			self::$needs_media_uploader = true;
		}
		if ( $field_type === 'color' ) {
			self::$needs_color_picker = true;
		}
		
		// Get current value.
		$options = get_option( self::$config['option_name'] );
		if ( isset( $options[ $field_id ] ) ) {
			$value = $options[ $field_id ];
		} else {
			// Use default value if provided, otherwise empty string.
			$value = isset( $field_args['default'] ) ? $field_args['default'] : '';
		}
		
		// Prepare field arguments for renderer.
		$renderer_args = array(
			'id'    => $field_id,
			'name'  => self::$config['option_name'] . '[' . $field_id . ']',
			'title' => $field['title'],
		);
		
		if ( ! empty( $field_args ) && is_array( $field_args ) ) {
			$renderer_args = array_merge( $renderer_args, $field_args );
		}
		
		// Call the field type renderer.
		$renderer_class = 'NanoOptions_Field_' . ucfirst( strtolower( $field_type ) );
		if ( class_exists( $renderer_class ) && method_exists( $renderer_class, 'render' ) ) {
			call_user_func( array( $renderer_class, 'render' ), $renderer_args, $value );
		} else {
			// Fallback to text field.
			NanoOptions_Field_Text::render( $renderer_args, $value );
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

		// Register each field.
		foreach ( self::$fields as $field ) {
			// Skip if required data is missing.
			if ( empty( $field['id'] ) || empty( $field['title'] ) || empty( $field['section_id'] ) ) {
				continue;
			}

			add_settings_field(
				$field['id'],
				$field['title'],
				array( __CLASS__, 'render_field_callback' ),
				self::$config['menu_slug'],
				$field['section_id']
			);
		}
	}

	/**
	 * Field rendering callback for Settings API.
	 */
	public static function render_field_callback( $args ) {
		// Find the field by ID.
		$field_id = $args['args'][0]; // The field ID is passed as first argument in $args['args']
		
		foreach ( self::$fields as $field ) {
			if ( $field['id'] === $field_id ) {
				self::render_field( $field );
				break;
			}
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
		
		$sanitized = array();
		foreach ( $input as $key => $value ) {
			// For color fields, sanitize as hex color.
			if ( preg_match('/^color$/', $key) ) {
				$sanitized[ $key ] = sanitize_hex_color( $value );
				// If sanitize_hex_color returns empty string, keep original or use default.
				if ( '' === $sanitized[ $key ] ) {
					$sanitized[ $key ] = $value;
				}
			} 
			// For media fields, sanitize as URL.
			elseif ( preg_match('/^media$/', $key) ) {
				$sanitized[ $key ] = esc_url_raw( $value );
			}
			// For all other fields, allow any value (could be improved with field-specific sanitization).
			else {
				$sanitized[ $key ] = $value;
			}
		}
		
		return $sanitized;
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

		// Check if we are on the NanoOptions settings page to load assets.
		$screen = get_current_screen();
		if ( $screen && isset( $screen->id ) && $screen->id === self::$config['menu_slug'] ) {
			// Enqueue WordPress color picker script and style if needed.
			if ( self::$needs_color_picker ) {
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );

				// Add inline script to initialize color picker for inputs with class 'np-color-picker'.
				wp_add_inline_script( 'wp-color-picker', '
					jQuery(document).ready(function($){
						$(".np-color-picker").wpColorPicker();
					});
				' );
			}
			
			// Enqueue media uploader if needed.
			if ( self::$needs_media_uploader ) {
				wp_enqueue_media();
				
				// Add inline script to handle media uploads.
				wp_add_inline_script( 'nano-options-admin', '
					jQuery(document).ready(function($){
						// Handle media upload button clicks.
						$(document).on(\'click\', \'.np-media-upload-button\', function(e){
							e.preventDefault();
							var button = $(this);
							var custom_uploader = wp.media({
								title: \'Choose Image\',
								button: {
									text: \'Choose Image\'
								},
								multiple: false
							}).on(\'select\', function() {
								var attachment = custom_uploader.state().get(\'selection\').first().toJSON();
								button.prev(\'.np-media-url\').val(attachment.url);
								button.prev(\'.np-media-preview\').attr(\'src\', attachment.url).show();
							}).open();
						});
						
						// Handle remove button clicks.
						$(document).on(\'click\', \'.np-media-remove-button\', function(e){
							e.preventDefault();
							var button = $(this);
							button.prevAll(\'.np-media-url\').val(\'\');
							button.prevAll(\'.np-media-preview\').attr(\'src\', \'\').hide();
						});
					});
				' );
			}
		}
	}
}