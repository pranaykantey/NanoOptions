<?php
/**
 * Color field – uses WordPress native color picker.
 */
class NanoOptions_Field_Color {

	/**
	 * Render the color picker.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value.
	 */
	public static function render( $field, $value ) {
		// Ensure value is a hex color with leading #
		$value = esc_attr( $value );
		?>
		<input type="text"
			id="<?php echo esc_attr( $field['id'] ); ?>"
			name="nano_options[<?php echo esc_attr( $field['id'] ); ?>]"
			value="<?php echo $value; // already escaped ?>"
			class="np-color-picker regular-text"
			data-default-color="<?php echo esc_attr( $field['default'] ?? '#0073aa' ); ?>" />
		<?php
	}
}
