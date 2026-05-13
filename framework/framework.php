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
 * Flag to indicate if color picker is needed.
 *
 * @since 1.0.0
 * @var bool
 */
private static $needs_color_picker = false;

/**
 * Flag to indicate if conditional JS is needed.
 *
 * @since 1.0.0
 * @var bool
 */
private static $needs_conditional_js = false;

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
	 *     @type array  $condition   Conditional visibility rules.
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
		if ( isset( $args['condition'] ) ) {
			$field_args['condition'] = $args['condition'];
			unset( $args['condition'] );
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
		
		// Check if we need media uploader, color picker, or conditional JS.
		if ( $field_type === 'media' ) {
			self::$needs_media_uploader = true;
		}
		if ( $field_type === 'color' ) {
			self::$needs_color_picker = true;
		}
		if ( ! empty( $field['condition'] ) && is_array( $field['condition'] ) ) {
			self::$needs_conditional_js = true;
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
		
		// Start conditional wrapper if needed.
		if ( ! empty( $field['condition'] ) && is_array( $field['condition'] ) ) {
			echo '<div class="np-condition-field" data-condition="' . esc_attr( wp_json_encode( $field['condition'] ) ) . '" style="display:none;">';
		}
		
		// Call the field type renderer.
		$renderer_class = 'NanoOptions_Field_' . ucfirst( strtolower( $field_type ) );
		if ( class_exists( $renderer_class ) && method_exists( $renderer_class, 'render' ) ) {
			call_user_func( array( $renderer_class, 'render' ), $renderer_args, $value );
		} else {
			// Fallback to text field.
			NanoOptions_Field_Text::render( $renderer_args, $value );
		}
		
		// End conditional wrapper if needed.
		if ( ! empty( $field['condition'] ) && is_array( $field['condition'] ) ) {
			echo '</div>';
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
		
		// Only sanitize fields that we know about.
		foreach ( self::$fields as $field ) {
			$id = $field['id'];
			
			// If the field is not in the input, skip (the existing value will be preserved by Settings API).
			if ( ! isset( $input[ $id ] ) ) {
				continue;
			}
			
			$value = $input[ $id ];
			$type  = $field['type'];
			
			switch ( $type ) {
				case 'text':
					$sanitized[ $id ] = sanitize_text_field( $value );
					break;
				case 'checkbox':
					$sanitized[ $id ] = ! empty( $value ) ? 1 : 0;
					break;
				case 'color':
					$sanitized[ $id ] = sanitize_hex_color( $value );
					break;
				case 'textarea':
					$sanitized[ $id ] = sanitize_textarea_field( $value );
					break;
				case 'select':
					// Whitelist allowed values.
					$options = isset( $field['args']['options'] ) && is_array( $field['args']['options'] ) 
						? $field['args']['options'] 
						: array();
					$sanitized[ $id ] = in_array( $value, $options, true ) ? $value : '';
					break;
				case 'media':
					$sanitized[ $id ] = esc_url_raw( $value );
					break;
				default:
					// Fallback for unknown types: treat as text.
					$sanitized[ $id ] = sanitize_text_field( $value );
					break;
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

		// Handle export action.
		if ( isset( $_POST['nano_options_export'] ) && isset( $_POST['nano_options_export_nonce'] ) && wp_verify_nonce( $_POST['nano_options_export_nonce'], 'nano_options_export' ) ) {
			$options = get_option( self::$config['option_name'] );
			$json = wp_json_encode( $options );

			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename=nano-options-settings.json' );
			header( 'Expires: 0' );
			header( 'Cache-Control: must-revalidate' );
			header( 'Pragma: public' );
			echo $json;
			die;
		}

		// Handle import action.
		if ( isset( $_POST['nano_options_import'] ) && isset( $_POST['nano_options_import_nonce'] ) && wp_verify_nonce( $_POST['nano_options_import_nonce'], 'nano_options_import' ) ) {
			if ( ! isset( $_FILES['nano_options_import_file'] ) || ! is_uploaded_file( $_FILES['nano_options_import_file']['tmp_name'] ) ) {
				add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'No file uploaded.', 'nano-options' ), 'error' );
			} else {
				$file = $_FILES['nano_options_import_file']['tmp_name'];
				$data = file_get_contents( $file );
				$json = json_decode( $data, true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'Invalid JSON file.', 'nano-options' ), 'error' );
				} else {
					// Validate and sanitize the imported data.
					$imported = array();
					foreach ( self::$fields as $field ) {
						$id = $field['id'];
						if ( isset( $json[ $id ] ) ) {
							$value = $json[ $id ];
							$type  = $field['type'];
							switch ( $type ) {
								case 'text':
									$imported[ $id ] = sanitize_text_field( $value );
									break;
								case 'checkbox':
									$imported[ $id ] = ! empty( $value ) ? 1 : 0;
									break;
								case 'color':
									$imported[ $id ] = sanitize_hex_color( $value );
									break;
								case 'textarea':
									$imported[ $id ] = sanitize_textarea_field( $value );
									break;
								case 'select':
									$options = isset( $field['args']['options'] ) && is_array( $field['args']['options'] ) 
										? $field['args']['options'] 
										: array();
									$imported[ $id ] = in_array( $value, $options, true ) ? $value : '';
									break;
								case 'media':
									$imported[ $id ] = esc_url_raw( $value );
									break;
								default:
									$imported[ $id ] = sanitize_text_field( $value );
									break;
							}
						}
					}

					if ( update_option( self::$config['option_name'], $imported ) ) {
						add_settings_error( self::$config['option_name'], 'nano_options_import_success', __( 'Settings imported successfully.', 'nano-options' ), 'updated' );
					} else {
						add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'Failed to import settings.', 'nano-options' ), 'error' );
					}
				}
			}
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
				
				// Import/Export section.
				echo '<h2>' . esc_html__( 'Import/Export Settings', 'nano-options' ) . '</h2>';
				echo '<table class="form-table">';
				echo '<tr><th scope="row">' . esc_html__( 'Export Settings', 'nano-options' ) . '</th><td>';
				echo '<input type="submit" name="nano_options_export" value="' . esc_attr__( 'Export Settings', 'nano-options' ) . '" class="button" />';
				echo wp_nonce_field( 'nano_options_export', 'nano_options_export_nonce', false, false );
				echo '</td></tr>';
				echo '<tr><th scope="row">' . esc_html__( 'Import Settings', 'nano-options' ) . '</th><td>';
				echo '<input type="file" name="nano_options_import_file" id="nano_options_import_file" />';
				echo '<input type="submit" name="nano_options_import" value="' . esc_attr__( 'Import Settings', 'nano-options' ) . '" class="button" />';
				echo '<p class="description">' . esc_html__( 'Upload a JSON file exported from NanoOptions to import settings.', 'nano-options' ) . '</p>';
				echo wp_nonce_field( 'nano_options_import', 'nano_options_import_nonce', false, false );
				echo '</td></tr>';
				echo '</table>';

				do_settings_sections( self::$config['menu_slug'] );
				submit_button();
				?>
			</form>
		</div>
		<?php
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

				// Get tabs from sections.
				$tabs = array();
				foreach ( self::$sections as $section ) {
					if ( ! empty( $section['tab'] ) && ! in_array( $section['tab'], $tabs ) ) {
						$tabs[] = $section['tab'];
					}
				}
				if ( empty( $tabs ) ) {
					$tabs = array( 'default' );
				}

				$current_tab = isset( $_GET['tab'] ) && in_array( $_GET['tab'], $tabs ) ? $_GET['tab'] : $tabs[0];

				// Tab navigation.
				echo '<h2 class="nav-tab-wrapper">';
				foreach ( $tabs as $tab ) {
					$current = ( $current_tab === $tab ) ? ' nav-tab-active' : '';
					echo '<a href="' . esc_url( add_query_arg( 'tab', $tab ) ) . '" class="nav-tab' . esc_attr( $current ) . '">' . esc_html( ucfirst( $tab ) ) . '</a>';
				}
				echo '</h2>';

				// Tab panels.
				foreach ( $tabs as $tab ) {
					$style = ( $current_tab === $tab ) ? '' : ' style="display:none;"';
					echo '<div class="tab-panel" id="tab-' . esc_attr( $tab ) . '"' . $style . '>';

					// Output sections for this tab.
					foreach ( self::$sections as $section ) {
						if ( $section['tab'] === $tab ) {
							if ( ! empty( $section['title'] ) ) {
								echo '<h2 class="tab-section-title">' . esc_html( $section['title'] ) . '</h2>';
							}
							// Output fields for this section.
							foreach ( self::$fields as $field ) {
								if ( $field['section_id'] === $section['id'] ) {
									self::render_field( $field );
								}
							}
						}
					}

					echo '</div>'; // end tab-panel
				}

				submit_button();
				?>
			</form>
			<?php
			// Tab switching JS.
			if ( count( $tabs ) > 1 ) {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($){
						$(".nav-tab-wrapper a").on("click", function(e){
							e.preventDefault();
							var tab = $(this).attr("href").split("tab=")[1];
							$(".tab-panel").hide();
							$("#tab-" + tab).show();
							$(".nav-tab").removeClass("nav-tab-active");
							$(this).addClass("nav-tab-active");
						});
					});
				</script>
				<?php
			}
			?>
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
			
			// Enqueue conditional JS if needed.
			if ( self::$needs_conditional_js ) {
				// Add inline script for conditional field visibility.
				wp_add_inline_script( 'nano-options-admin', '
					jQuery(document).ready(function($){
						// Conditional field visibility.
						function updateConditionals() {
							$("[data-condition]").each(function() {
								var $field = $(this);
								var condition = $field.data("condition");
								if (typeof condition === "string") {
									try {
										condition = JSON.parse(condition);
									} catch(e) {
										return; // Invalid JSON
									}
								}
								
								if (condition && condition.field && condition.value !== undefined) {
									var $controller = $("#" + condition.field);
									var controllerValue = $controller.val();
									
									// For checkboxes, check if checked
									if ($controller.is(":checkbox")) {
										controllerValue = $controller.is(":checked") ? "1" : "0";
									}
									
									// Show/hide based on condition
									if (controllerValue == condition.value) {
										$field.show();
									} else {
										$field.hide();
									}
								}
							});
						}
						
						// Run on load and when controllers change
						updateConditionals();
						$("[data-condition]").each(function() {
							var condition = $(this).data("condition");
							if (typeof condition === "string") {
								try {
									condition = JSON.parse(condition);
								} catch(e) {
									return;
								}
							}
							
							if (condition && condition.field) {
								var $controller = $("#" + condition.field);
								$controller.on("change keyup", function(){
									updateConditionals();
								});
							}
						});
					});
				' );
			}
		}
	}
}