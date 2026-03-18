<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AcfRepeaterWysiwygTag')) {
    class AcfRepeaterWysiwygTag extends \Elementor\Core\DynamicTags\Tag {
        public function get_name() {
            return 'lgefep-acf-repeater-wysiwyg';
        }

        public function get_title() {
            return 'ACF Repeater WYSIWYG';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
        }

        public function get_supported_fields() {
            return ['wysiwyg'];
        }

        public function render() {
            $value = $this->get_value();
            echo wp_kses_post($value);
        }

        public function get_value(array $options = []) {
            $manager = new \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER();
            $field_key = esc_attr($this->get_settings('lgefep_acf_repeater_field_control'));
            if (empty($field_key)) {
                return '';
            }

            $value = $manager->get_repeater_value($field_key);
            if ($value === null) {
                return '';
            }

            $field_type = $this->get_field_type($field_key);
            if ($field_type === null || !in_array($field_type, ['wysiwyg'], true)) {
                return '';
            }

            if (is_array($value)) {
                return wp_json_encode($value);
            }
            if (is_object($value)) {
                return wp_json_encode($value);
            }
            return (string) $value;
        }

        private function get_field_type($field_key) {
            if (function_exists('get_field_object')) {
                $field_object = get_field_object($field_key);
                if ($field_object && isset($field_object['type'])) {
                    return $field_object['type'];
                }
            }
            return null;
        }
    }
}

new AcfRepeaterWysiwygTag();


