<?php
/**
 * Checkbox field type.
 */
class NanoOptions_Field_Checkbox {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'checkbox';

	/**
	 * Render the field.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value.
	 * @return void
	 */
	public static function render( $field, $value ) {
		// Normalize value to boolean.
		$checked = ! empty( $value );
		
		$attributes = array(
			'type'  => 'checkbox',
			'name'  => esc_attr( $field['name'] ),
			'id'    => esc_attr( $field['id'] ),
		);

		// Add checked attribute if needed.
		if ( $checked ) {
			$attributes['checked'] = 'checked';
		}

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