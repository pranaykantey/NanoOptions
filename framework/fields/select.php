<?php
/**
 * Select field type.
 */
class NanoOptions_Field_Select {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'select';

	/**
	 * Render the field.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value.
	 * @return void
	 */
	public static function render( $field, $value ) {
		$attributes = array(
			'type'  => 'select',
			'name'  => esc_attr( $field['name'] ),
			'id'    => esc_attr( $field['id'] ),
			'class' => 'regular-text', // Use WordPress admin style for select.
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

		echo '<select' . $attr_string . '>';

		// Options should be in $field['args']['options'].
		$options = isset( $field['args']['options'] ) && is_array( $field['args']['options'] ) 
			? $field['args']['options'] 
			: array();

		foreach ( $options as $option_value => $option_label ) {
			// Determine if this option is selected.
			$selected = selected( $value, $option_value, false );

			echo '<option value="' . esc_attr( $option_value ) . '" ' . $selected . '>';
			echo esc_html( $option_label ); // Option labels should be escaped for HTML.
			echo '</option>';
		}

		echo '</select>';

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}
}