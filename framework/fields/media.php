<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Media field – WordPress Media Library uploader with preview.
 */
class NanoOptions_Field_Media {
    public static function render( $field, $value ) {
        $id        = esc_attr( $field['id'] );
        $name      = 'nano_options[' . $id . ']';
        $value     = esc_url( $value );
        $has_value = ! empty( $value );
        $preview_style = $has_value ? '' : ' style="display:none;"';
        $remove_style  = $has_value ? '' : ' style="display:none;"';

        // URL input
        echo '<input type="text" class="np-media-url regular-text" id="' . $id . '" name="' . $name . '" value="' . $value . '" readonly />';

        // Preview (if value exists)
        echo '<img class="np-media-preview" src="' . $value . '"' . $preview_style . ' style="max-width:100px; margin-top:10px; display:block;" />';

        // Upload button
        echo '<input type="button" class="button np-media-upload-button" value="' . esc_attr__( 'Select Media', 'nano-options' ) . '" />';

        // Remove button
        echo '<input type="button" class="button np-media-remove-button" value="' . esc_attr__( 'Remove Image', 'nano-options' ) . '"' . $remove_style . ' />';
    }
}
