<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Text field – single-line text input.
 */
class NanoOptions_Field_Text {
    public static function render( $field, $value ) {
        $id       = esc_attr( $field['id'] );
        $name     = 'nano_options[' . $id . ']';
        $class = ! empty( $field['class'] ) ? ' ' . esc_attr( $field['class'] ) : '';
        $ph    = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
        echo '<input type="text" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ) . '" class="regular-text' . $class . '"' . $ph . ' />';
    }
}
