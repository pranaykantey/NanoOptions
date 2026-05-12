<?php
/**
 * Text field type.
 */
class NanoOptions_Field_Text {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'text';

	/**
	 * Render the field.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value.
	 * @return void
	 */
	public static function render( $field, $value ) {
		$attributes = array(
			'type'      => 'text',
			'name'      => esc_attr( $field['name'] ),
			'value'     => esc_attr( $value ),
			'class'     => 'regular-text',
			'id'        => esc_attr( $field['id'] ),
		);

		// Add custom attributes.
		if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			foreach ( $field['attributes'] as $attr => $value ) {
				$attributes[ $attr ] = esc_attr( $value );
			}
		}

		$attr_string = '';
		foreach ( $attributes as $attr => $value ) {
			$attr_string .= " {$attr}=\"{$value}\"";
		}

		echo '<input' . $attr_string . ' />';

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}
}