<?php
/**
 * Textarea field type.
 */
class NanoOptions_Field_Textarea {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'textarea';

	/**
	 * Render the field.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value.
	 * @return void
	 */
	public static function render( $field, $value ) {
		$attributes = array(
			'name'  => esc_attr( $field['name'] ),
			'id'    => esc_attr( $field['id'] ),
			'class' => 'large-text',
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

		echo '<textarea' . $attr_string . '>' . esc_textarea( $value ) . '</textarea>';

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}
}