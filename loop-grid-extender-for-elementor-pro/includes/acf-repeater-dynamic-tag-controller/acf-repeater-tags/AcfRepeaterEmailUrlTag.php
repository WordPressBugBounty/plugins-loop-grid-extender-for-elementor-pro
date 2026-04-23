<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AcfRepeaterEmailUrlTag')) {

    class AcfRepeaterEmailUrlTag extends Data_Tag {
        public function get_name() {
            return 'lgefep-acf-repeater-email-url';
        }

        public function get_title() {
            return 'ACF Repeater Email URL';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [
                \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            ];
        }

        public function get_supported_fields() {
            return ['email'];
        }

        public function render() {
            $value = $this->get_value();
            echo esc_url($value);
        }

        public function get_value( array $options = [] ) {
            $manager = new \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER();
            $field_key = esc_attr($this->get_settings( 'lgefep_acf_repeater_field_control' ));
            if ( empty( $field_key ) ) {
                return '';
            }
            $value = $manager->get_repeater_value( $field_key );
            if ( $value === null ) {
                return '';
            }
            $field_type = sanitize_text_field($this->get_field_type( $field_key ));
            if ( $field_type === null ) {
                return '';
            }
            if ( !in_array( $field_type, ['email'] ) ) {
                return '';
            }
            // Defensive array guard: ACF email fields store a single string, but
            // Elementor Pro's own acf-url.php applies the same pattern to handle
            // unexpected data. See: elementor-pro/modules/dynamic-tags/acf/tags/acf-url.php
            $email = sanitize_email( is_array( $value ) ? reset( $value ) : (string) $value );
            if ( empty( $email ) ) {
                return '';
            }
            return 'mailto:' . $email;
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

new AcfRepeaterEmailUrlTag();