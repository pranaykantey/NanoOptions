<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Checkbox field – boolean toggle.
 * Always outputs a hidden field to ensure a value of 0 when unchecked.
 */
class NanoOptions_Field_Checkbox {
    public static function render( $field, $value ) {
        $id   = esc_attr( $field['id'] );
        $name = 'nano_options[' . $id . ']';
        $checked = ! empty( $value ) ? 'checked' : '';
        // Hidden field ensures 0 is sent when unchecked
        echo '<input type="hidden" name="' . $name . '" value="0" />';
        echo '<input type="checkbox" id="' . $id . '" name="' . $name . '" value="1" ' . $checked . ' />';
    }
}
