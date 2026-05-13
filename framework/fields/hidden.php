<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Hidden field – for internal data, often used with conditionals.
 */
class NanoOptions_Field_Hidden {
    public static function render( $field, $value ) {
        $id   = esc_attr( $field['id'] );
        $name = 'nano_options[' . $id . ']';
        echo '<input type="hidden" id="' . $id . '" name="' . $name . '" value="' . esc_attr( $value ) . '" />';
    }
}
