<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Number field – numeric input with HTML5 stepper.
 */
class NanoOptions_Field_Number {
    public static function render( $field, $value ) {
        $id    = esc_attr( $field['id'] );
        $name  = 'nano_options[' . $id . ']';
        $attrs = [
            'type'  => 'number',
            'id'    => $id,
            'name'  => $name,
            'value' => esc_attr( $value ),
            'class' => 'small-text',
        ];

        if ( ! empty( $field['placeholder'] ) ) {
            $attrs['placeholder'] = $field['placeholder'];
        }
        if ( isset( $field['min'] ) ) {
            $attrs['min'] = $field['min'];
        }
        if ( isset( $field['max'] ) ) {
            $attrs['max'] = $field['max'];
        }
        $attrs['step'] = ! empty( $field['integer'] ) ? '1' : 'any';

        $html = '<input';
        foreach ( $attrs as $k => $v ) {
            $html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
        }
        $html .= ' />';
        echo $html;
    }
}
