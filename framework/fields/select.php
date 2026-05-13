<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Select field – dropdown list.
 */
class NanoOptions_Field_Select {
    public static function render( $field, $value ) {
        $id      = esc_attr( $field['id'] );
        $name    = 'nano_options[' . $id . ']';
        $options = $field['options'] ?? [];

        echo '<select id="' . $id . '" name="' . $name . '">';
        foreach ( $options as $opt_val => $label ) {
            $selected = selected( $value, $opt_val, false );
            echo '<option value="' . esc_attr( $opt_val ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
        }
        echo '</select>';
    }
}
