<?php
/**
 * NanoOptions Framework Core
 *
 * @package NanoOptions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core framework class – static methods.
 */
class NanoOptions_Framework {

	/** @var array Configuration */
	private static $config = [];

	/** @var array Registered sections */
	private static $sections = [];

	/** @var array Registered fields */
	private static $fields = [];

	/** @var bool Asset flags */
	private static $needs_media = false;
	private static $needs_color = false;
	private static $needs_conditional = false;

	/** @var bool Debug mode */
	private static $debug = false;

	/** @var array Debug messages */
	private static $messages = [];

	/**
	 * Initialize the framework.
	 *
	 * @param array $config {
	 *     @type string $menu_title
	 *     @type string $menu_slug
	 *     @type string $option_name
	 *     @type string $capability Optional. Default 'manage_options'.
	 *     @type bool   $debug Optional. Default WP_DEBUG constant.
	 * }
	 */
	public static function init( array $config ) {
		self::$config = wp_parse_args( $config, [
			'menu_title' => 'NanoOptions',
			'menu_slug'  => 'nano-options',
			'option_name'=> 'nano_options',
			'capability' => 'manage_options',
			'debug'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
		] );

		self::$debug = self::$config['debug'];

		self::include_fields();

		add_action( 'admin_menu',            [ __CLASS__, 'register_admin_menu' ] );
		add_action( 'admin_init',            [ __CLASS__, 'register_settings' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Include all field type files.
	 */
	private static function include_fields() {
		$dir = plugin_dir_path( __FILE__ ) . 'fields/';
		if ( is_dir( $dir ) ) {
			foreach ( glob( $dir . '*.php' ) as $file ) {
				require_once $file;
			}
		}
	}

	/**
	 * Register a settings section.
	 *
	 * @param array $args {
	 *     @type string $id          Required.
	 *     @type string $title       Required.
	 *     @type string $tab         Tab name. Default 'Main'.
	 *     @type string $description Optional description.
	 *     @type callable $callback  Optional custom callback.
	 * }
	 */
	public static function section( array $args ) {
		$defaults = [
			'id'          => '',
			'title'       => '',
			'tab'         => 'Main',
			'description' => '',
			'callback'    => null,
		];
		$section = wp_parse_args( $args, $defaults );

		// Duplicate ID detection
		foreach ( self::$sections as $existing ) {
			if ( $existing['id'] === $section['id'] ) {
				$msg = sprintf( __( 'Duplicate section ID: %s', 'nano-options' ), $section['id'] );
				self::$messages[] = $msg;
				if ( self::$debug ) {
					trigger_error( "NanoOptions: {$msg}", E_USER_NOTICE );
				}
				return;
			}
		}

		self::$sections[] = $section;
		do_action( 'nanooptions_section_added', $section );
	}

	/**
	 * Register a field.
	 *
	 * @param array $args Field arguments.
	 */
	public static function field( array $args ) {
		// Support both 'section' (user-friendly) and 'section_id' (internal).
		if ( isset( $args['section'] ) && empty( $args['section_id'] ) ) {
			$args['section_id'] = $args['section'];
		}

		$defaults = [
			'id'          => '',
			'title'       => '',
			'section_id'  => '',
			'type'        => 'text',
			'default'     => null,
			'description' => '',
			'placeholder' => '',
			'options'     => [],
			'sanitize'    => null,
			'class'       => '',
			'condition'   => null,
			'args'        => [],
			'integer'     => false,
		];
		$field = wp_parse_args( $args, $defaults );

		// Required validation
		if ( empty( $field['id'] ) || empty( $field['title'] ) || empty( $field['section_id'] ) ) {
			if ( self::$debug ) {
				trigger_error( 'NanoOptions: Field missing required id, title, or section_id', E_USER_NOTICE );
			}
			return;
		}

		// Duplicate ID detection
		foreach ( self::$fields as $existing ) {
			if ( $existing['id'] === $field['id'] ) {
				$msg = sprintf( __( 'Duplicate field ID: %s', 'nano-options' ), $field['id'] );
				self::$messages[] = $msg;
				if ( self::$debug ) {
					trigger_error( "NanoOptions: {$msg}", E_USER_NOTICE );
				}
				return;
			}
		}

		// Asset flags based on field type and conditionals
		switch ( $field['type'] ) {
			case 'media':
				self::$needs_media = true;
				break;
			case 'color':
				self::$needs_color = true;
				break;
			default:
				if ( ! empty( $field['condition'] ) ) {
					self::$needs_conditional = true;
				}
				break;
		}
		if ( ! empty( $field['condition'] ) ) {
			self::$needs_conditional = true;
		}

		self::$fields[] = $field;
		do_action( 'nanooptions_field_added', $field );
	}

	/**
	 * Register admin menu.
	 */
	public static function register_admin_menu() {
		add_options_page(
			self::$config['menu_title'],
			self::$config['menu_title'],
			self::$config['capability'],
			self::$config['menu_slug'],
			[ __CLASS__, 'admin_page_html' ]
		);
	}

	/**
	 * Register setting only – fields rendered manually.
	 */
	public static function register_settings() {
		register_setting(
			self::$config['option_name'],
			self::$config['option_name'],
			[ __CLASS__, 'sanitize_options' ]
		);
	}

	/* ==== Developer Helper Methods ==== */

	public static function get_option_value( $id, $default = null ) {
		$options = get_option( self::$config['option_name'], [] );
		return isset( $options[ $id ] ) ? $options[ $id ] : $default;
	}

	public static function get_all_options() {
		return get_option( self::$config['option_name'], [] );
	}

	public static function get_sections() {
		return self::$sections;
	}

	public static function get_fields() {
		return self::$fields;
	}

	/* ==== Field Rendering ==== */

	/**
	 * Render a single field row inside the table.
	 *
	 * @param array $field
	 */
	private static function render_field_wrapper_for_display( $field ) {
		$field_class = 'NanoOptions_Field_' . ucfirst( strtolower( $field['type'] ) );
		if ( ! class_exists( $field_class ) ) {
			$field_class = 'NanoOptions_Field_Text';
		}

		$value = self::get_option_value( $field['id'], $field['default'] ?? '' );

		// Merge field args into top-level for renderer convenience.
		$render = $field;
		if ( ! empty( $field['args'] ) && is_array( $field['args'] ) ) {
			$render = array_merge( $field, $field['args'] );
			unset( $render['args'] );
		}

		// Condition attribute on row
		$condition_attr = '';
		if ( ! empty( $field['condition'] ) && is_array( $field['condition'] ) ) {
			$condition_attr = ' data-condition="' . esc_attr( wp_json_encode( $field['condition'] ) ) . '"';
		}

		?>
		<tr class="nanooptions-field-row <?php echo esc_attr( $field['class'] ?? '' ); ?>"<?php echo $condition_attr; ?>>
			<th scope="row">
				<label for="<?php echo esc_attr( $field['id'] ); ?>">
					<?php echo esc_html( $field['title'] ); ?>
				</label>
			</th>
			<td>
				<?php
				call_user_func( [ $field_class, 'render' ], $render, $value );
				if ( ! empty( $field['description'] ) ) {
					echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
				}
				?>
			</td>
		</tr>
		<?php
	}

	/* ==== Admin Page ==== */

	public static function admin_page_html() {
		if ( ! current_user_can( self::$config['capability'] ) ) {
			return;
		}

		// Notices
		if ( isset( $_GET['settings-updated'] ) ) {
			add_settings_error( self::$config['option_name'], 'nano_options_message', __( 'Settings saved.', 'nano-options' ), 'updated' );
		}
		if ( self::$debug && ! empty( self::$messages ) ) {
			foreach ( self::$messages as $msg ) {
				add_settings_error( self::$config['option_name'], 'nano_options_debug', $msg, 'error' );
			}
		}

		// Handlers
		if ( isset( $_POST['nano_options_export'] ) && check_admin_referer( 'nano_options_export', 'nano_options_export_nonce' ) ) {
			self::export_settings();
		}
		if ( isset( $_POST['nano_options_import'] ) && check_admin_referer( 'nano_options_import', 'nano_options_import_nonce' ) ) {
			self::import_settings();
		}

		settings_errors( self::$config['option_name'] );
		?>
		<div class="wrap nanooptions-admin">
			<h1><?php echo esc_html( self::$config['menu_title'] ); ?></h1>
			<form method="post" action="options.php" enctype="multipart/form-data">
				<?php
				settings_fields( self::$config['option_name'] );

				// Build tabs list from sections
				$tabs = [];
				foreach ( self::$sections as $section ) {
					$tab = $section['tab'] ?? 'Main';
					if ( ! in_array( $tab, $tabs, true ) ) {
						$tabs[] = $tab;
					}
				}
				if ( empty( $tabs ) ) {
					$tabs = [ 'Main' ];
				}

				$current_tab = $_GET['tab'] ?? $tabs[0];
				if ( ! in_array( $current_tab, $tabs, true ) ) {
					$current_tab = $tabs[0];
				}

				// Tab navigation
				if ( count( $tabs ) > 1 ) {
					echo '<h2 class="nav-tab-wrapper">';
					foreach ( $tabs as $tab ) {
						$active = ( $tab === $current_tab ) ? ' nav-tab-active' : '';
						echo '<a href="' . esc_url( add_query_arg( 'tab', $tab ) ) . '" class="nav-tab' . $active . '">' . esc_html( $tab ) . '</a>';
					}
					echo '</h2>';
				}

				// Import/Export box (always above tabs)
				?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Import/Export Settings', 'nano-options' ); ?></th>
						<td>
							<p>
								<input type="submit" name="nano_options_export" class="button button-secondary" value="<?php esc_attr_e( 'Export Settings', 'nano-options' ); ?>" />
								<?php wp_nonce_field( 'nano_options_export', 'nano_options_export_nonce' ); ?>
							</p>
							<p>
								<input type="file" name="nano_options_import_file" id="nano_options_import_file" accept=".json" />
								<input type="submit" name="nano_options_import" class="button button-secondary" value="<?php esc_attr_e( 'Import Settings', 'nano-options' ); ?>" />
								<?php wp_nonce_field( 'nano_options_import', 'nano_options_import_nonce' ); ?>
								<p class="description"><?php esc_html_e( 'Upload a previously exported JSON file.', 'nano-options' ); ?></p>
							</p>
						</td>
					</tr>
				</table>
				<?php

				// Tab panels (each div.tab-panel)
				foreach ( $tabs as $tab ) {
					$style = ( $tab === $current_tab ) ? '' : ' style="display:none;"';
					echo '<div id="tab-' . esc_attr( $tab ) . '" class="tab-panel"' . $style . '>';

					// Sections within this tab
					foreach ( self::$sections as $section ) {
						$section_tab = $section['tab'] ?? 'Main';
						if ( $section_tab !== $tab ) {
							continue;
						}
						?>
						<div id="section-<?php echo esc_attr( $section['id'] ); ?>" class="nanooptions-section">
							<?php if ( ! empty( $section['title'] ) ) : ?>
								<h2 class="nanooptions-section-title"><?php echo esc_html( $section['title'] ); ?></h2>
							<?php endif; ?>
							<?php if ( ! empty( $section['callback'] ) && is_callable( $section['callback'] ) ) : ?>
								<div class="nanooptions-section-desc"><?php call_user_func( $section['callback'], $section ); ?></div>
							<?php endif; ?>
							<table class="form-table" role="presentation">
								<?php
								foreach ( self::$fields as $field ) {
									if ( $field['section_id'] === $section['id'] ) {
										self::render_field_wrapper_for_display( $field );
									}
								}
								?>
							</table>
						</div>
						<?php
					}

					echo '</div>'; // .tab-panel
				}

				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/* ==== Sanitization ==== */

	public static function sanitize_options( $input ) {
		if ( ! is_array( $input ) ) {
			return [];
		}

		$sanitized = [];

		/**
		 * Action before sanitization loop.
		 */
		do_action( 'nanooptions_sanitize_before', $input, self::$config['option_name'] );

		foreach ( self::$fields as $field ) {
			$id = $field['id'];
			if ( ! array_key_exists( $id, $input ) ) {
				continue;
			}

			$value = $input[ $id ];
			$type  = $field['type'];

			// Custom sanitizer
			if ( ! empty( $field['sanitize'] ) ) {
				if ( is_callable( $field['sanitize'] ) ) {
					$clean = call_user_func( $field['sanitize'], $value );
				} elseif ( is_string( $field['sanitize'] ) && function_exists( $field['sanitize'] ) ) {
					$clean = call_user_func( $field['sanitize'], $value );
				} else {
					$clean = self::sanitize_by_type( $value, $type, $field );
				}
			} else {
				$clean = self::sanitize_by_type( $value, $type, $field );
			}

			$sanitized[ $id ] = apply_filters( "nanooptions_sanitize_{$type}", $clean, $field );
		}

		/**
		 * Action after sanitization.
		 */
		do_action( 'nanooptions_sanitize_after', $sanitized, self::$config['option_name'] );

		return $sanitized;
	}

	/**
	 * Type-based sanitization.
	 *
	 * @param mixed  $value
	 * @param string $type
	 * @param array  $field
	 * @return mixed
	 */
	private static function sanitize_by_type( $value, $type, $field ) {
		switch ( $type ) {
			case 'text':
				return sanitize_text_field( $value );

			case 'textarea':
				return function_exists( 'sanitize_textarea_field' ) ? sanitize_textarea_field( $value ) : wp_kses_post( $value );

			case 'checkbox':
				return ! empty( $value ) ? 1 : 0;

		case 'select':
		case 'radio':
			$allowed = $field['options'] ?? ($field['args']['options'] ?? []);
			return in_array( $value, $allowed, true ) ? $value : '';

			case 'number':
				if ( ! empty( $field['integer'] ) ) {
					return absint( $value );
				}
				return floatval( $value );

			case 'color':
				return sanitize_hex_color( $value );

			case 'media':
				return esc_url_raw( $value );

			case 'hidden':
				return sanitize_text_field( $value );

			default:
				return sanitize_text_field( $value );
		}
	}

	/* ==== Import / Export ==== */

	private static function export_settings() {
		$options = get_option( self::$config['option_name'], [] );
		$json    = wp_json_encode( $options );

		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename=nano-options-settings.json' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate' );
		header( 'Pragma: public' );
		echo $json;
		exit;
	}

	private static function import_settings() {
		if ( ! isset( $_FILES['nano_options_import_file'] ) || ! is_uploaded_file( $_FILES['nano_options_import_file']['tmp_name'] ) ) {
			add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'No file uploaded.', 'nano-options' ), 'error' );
			return;
		}

		$file = $_FILES['nano_options_import_file']['tmp_name'];
		$data = file_get_contents( $file );
		$json = json_decode( $data, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'Invalid JSON file.', 'nano-options' ), 'error' );
			return;
		}

		$imported = [];
		foreach ( self::$fields as $field ) {
			$id = $field['id'];
			if ( isset( $json[ $id ] ) ) {
				$value = $json[ $id ];
				$clean = self::sanitize_by_type( $value, $field['type'], $field );
				$imported[ $id ] = apply_filters( "nanooptions_sanitize_{$field['type']}", $clean, $field );
			}
		}

		if ( empty( $imported ) ) {
			add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'No valid fields found in import.', 'nano-options' ), 'error' );
			return;
		}

		$existing = get_option( self::$config['option_name'], [] );
		$updated  = array_merge( $existing, $imported );

		if ( update_option( self::$config['option_name'], $updated ) ) {
			add_settings_error( self::$config['option_name'], 'nano_options_import_success', __( 'Settings imported successfully.', 'nano-options' ), 'updated' );
		} else {
			add_settings_error( self::$config['option_name'], 'nano_options_import_error', __( 'Failed to import settings.', 'nano-options' ), 'error' );
		}
	}

	/* ==== Asset Loading ==== */

	public static function enqueue_assets() {
		$screen = get_current_screen();
		// Options pages use hook 'settings_page_{menu_slug}'
		if ( ! $screen || $screen->id !== 'settings_page_' . self::$config['menu_slug'] ) {
			return;
		}

		$url = plugin_dir_url( __FILE__ );

		wp_enqueue_style(
			'nano-options-admin',
			$url . 'assets/admin.css',
			[],
			'1.0.0'
		);

		// Conditionally enqueue WordPress core assets
		if ( self::$needs_media ) {
			wp_enqueue_media();
		}
		if ( self::$needs_color ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );
		}

		wp_enqueue_script(
			'nano-options-admin',
			$url . 'assets/admin.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);

		wp_localize_script( 'nano-options-admin', 'nanoOptions', [
			'needsConditional' => self::$needs_conditional,
			'needsMedia'       => self::$needs_media,
			'needsColor'       => self::$needs_color,
			'strings'          => [
				'selectMedia' => __( 'Select Media', 'nano-options' ),
			],
		] );
	}
}
