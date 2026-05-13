<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Radio field – set of radio buttons.
 */
class NanoOptions_Field_Radio {
    public static function render( $field, $value ) {
        $id      = esc_attr( $field['id'] );
        $name    = 'nano_options[' . $id . ']';
        $options = $field['options'] ?? [];

        foreach ( $options as $opt_val => $label ) {
            $opt_val   = (string) $opt_val;
            $checked   = checked( $value, $opt_val, false );
            $input_id  = $id . '_' . $opt_val;
            printf(
                '<p><label for="%s"><input type="radio" id="%s" name="%s" value="%s" %s> %s</label></p>',
                esc_attr( $input_id ),
                esc_attr( $input_id ),
                esc_attr( $name ),
                esc_attr( $opt_val ),
                $checked,
                esc_html( $label )
            );
        }
    }
}
