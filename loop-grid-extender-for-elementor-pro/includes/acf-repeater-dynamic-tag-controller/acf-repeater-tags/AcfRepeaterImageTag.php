<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;
use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AcfRepeaterImageTag')) {
    class AcfRepeaterImageTag extends Data_Tag {
        public function get_name() {
            return 'acf-repeater-image';
        }

        public function get_title() {
            return 'ACF Repeater Image';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [
                \Elementor\Modules\DynamicTags\Module::IMAGE_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
            ];
        }

        public function get_supported_fields() {
            return ['image'];
        }

        public function render() {
            $value = $this->get_value();
            echo esc_url($value['url']);
        }

        public function get_value(array $options = []) {
            $manager = new \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER();
            $field_key = esc_attr($this->get_settings('lgefep_acf_repeater_field_control'));
            $image_data = ['id' => null, 'url' => ''];
                if (empty($field_key)) {
                    return $image_data;
                }
            
                $value = $manager->get_repeater_value($field_key);
                if ($value === null) {
                    return $image_data;
                }
                if (is_array($value) && isset($value['ID']) && isset($value['url'])) {
                    $image_data['id'] = esc_attr($value['ID']);
                    $image_data['url'] = esc_url($value['url']);
                } elseif (is_numeric($value)) {
                    $image_data['id'] = esc_attr($value);
                    $image_data['url'] = esc_url(wp_get_attachment_url($value));
                } elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    $image_data['id'] = esc_attr(attachment_url_to_postid($value));
                    $image_data['url'] = esc_url($value);
                }
                return $image_data;
           
        }
    }
}

new AcfRepeaterImageTag();