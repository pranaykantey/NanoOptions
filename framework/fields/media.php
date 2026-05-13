<?php
/**
 * Media field type.
 */
class NanoOptions_Field_Media {

	/**
	 * Field type.
	 *
	 * @var string
	 */
	public $type = 'media';

	/**
	 * Render the field.
	 *
	 * @param array $field Field arguments.
	 * @param mixed $value Current value (attachment URL).
	 * @return void
	 */
	public static function render( $field, $value ) {
		// Ensure value is a string (URL).
		$value = esc_url( $value );
		
		// Determine if we have a value to show preview.
		$has_value = ! empty( $value );
		$preview_style = $has_value ? '' : 'style="display:none;"';

		$attributes = array(
			'type'  => 'text',
			'class' => 'np-media-url regular-text',
			'name'  => esc_attr( $field['name'] ),
			'value' => esc_attr( $value ),
			'id'    => esc_attr( $field['id'] ),
		);

		// Add custom attributes.
		if ( ! empty( $field['attributes'] ) && is_array( $field['attributes'] ) ) {
			foreach ( $field['attributes'] as $attr => $value_attr ) {
				$attributes[ $attr ] = esc_attr( $value_attr );
			}
		}

		$attr_string = '';
		foreach ( $attributes as $attr => $value ) {
			$attr_string .= " {$attr}=\"{$value}\"";
		}

		echo '<input' . $attr_string . ' />';
		
		// Preview image.
		echo '<img src="' . esc_url( $value ) . '" class="np-media-preview" style="max-width:100px; margin-top:10px; ' . esc_attr( $preview_style ) . '" />';
		
		// Upload button.
		echo '<input type="button" class="button np-media-upload-button" value="' . esc_attr__( 'Upload Image', 'nano-options' ) . '" />';
		
		// Remove button (only show if we have a value).
		if ( $has_value ) {
			echo '<input type="button" class="button np-media-remove-button" value="' . esc_attr__( 'Remove Image', 'nano-options' ) . '" />';
		}

		if ( ! empty( $field['description'] ) ) {
			echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
		}
	}
}