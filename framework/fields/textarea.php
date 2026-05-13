<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Textarea field – multi-line text.
 */
class NanoOptions_Field_Textarea {
    public static function render( $field, $value ) {
        $id    = esc_attr( $field['id'] );
        $name  = 'nano_options[' . $id . ']';
        $class = ! empty( $field['class'] ) ? esc_attr( $field['class'] ) : '';
        $ph    = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
        echo '<textarea id="' . $id . '" name="' . $name . '" class="' . $class . '" rows="5"' . $ph . '>' . esc_textarea( $value ) . '</textarea>';
    }
}
