<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

if (!defined('ABSPATH')) {
    exit;
}

use \Elementor\Plugin;
if (!class_exists('AcfRepeaterNumberTag')) {
    
  
    class AcfRepeaterNumberTag extends \Elementor\Core\DynamicTags\Tag {
        public function get_name() {
            return 'lgefep-acf-repeater-number';
        }

        public function get_title() {
            return 'ACF Repeater Number';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
        }

        public function get_supported_fields() {
            return ['number'];
        }

        public function render() {
           $value = $this->get_value();
           echo wp_kses_post($value);
        }

        public function get_value( array $options = [] ) {
            $manager = new \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER();
            $field_key = esc_attr($this->get_settings( 'lgefep_acf_repeater_field_control' ));
            if ( empty( $field_key ) ) {
                return '';
            }
            $value = esc_html($manager->get_repeater_value( $field_key ));
            if ( $value === null ) {
                return '';
            }
            $field_type = $this->get_field_type( $field_key );
            if ( $field_type === null ) {
                return '';
            }
            if ( !in_array( $field_type, ['number'] ) ) {
                return '';
            }
            $result = '';
            if ( is_array( $value ) ) {
                // Return the first numeric value or sum them up
                $numeric_values = array_filter( $value, function($val) {
                    return is_numeric($val);
                });
                $result = !empty($numeric_values) ? (float) reset($numeric_values) : '';
            } elseif ( is_numeric( $value ) ) {
                $result = (float) esc_html($value);
            } else {
                $result = '';
            }
            return $result;
        }
    
        private function get_field_type( $field_key ) {
            if ( function_exists( 'get_field_object' ) ) {
                $field_object = get_field_object( $field_key );
                if ( $field_object && isset( $field_object['type'] ) ) {
                    return $field_object['type'];
                }
            }
            return null;
        }
    }
}

new AcfRepeaterNumberTag();
