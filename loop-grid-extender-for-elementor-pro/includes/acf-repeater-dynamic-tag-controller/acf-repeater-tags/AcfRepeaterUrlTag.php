<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;
use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AcfRepeaterUrlTag')) {
    class AcfRepeaterUrlTag extends Data_Tag {
        public function get_name() {
            return 'acf-repeater-url';
        }

        public function get_title() {
            return 'ACF Repeater URL';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [
                \Elementor\Modules\DynamicTags\Module::URL_CATEGORY,
                \Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY,
            ];
        }

        public function get_supported_fields() {
            return ['url', 'link', 'page_link', 'file'];
        }

        public function render() {
            $value = $this->get_value();
            echo esc_url($value);
        }

        public function get_value(array $options = []) {
            $manager = new \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER();
            $field_key = esc_attr($this->get_settings('lgefep_acf_repeater_field_control'));
            $link_clickable = esc_attr($this->get_settings('lgefep_acf_repeater_link_clickable'));
            $link_text = esc_attr($this->get_settings('lgefep_acf_repeater_link_text'));
            $open_link_in_new_tab = esc_attr($this->get_settings('lgefep_acf_repeater_open_link_in_new_tab'));
            

            
            if (empty($field_key)) {
                return '';
            }
            $value = $manager->get_repeater_value($field_key);
            if ($value === null) {
                return '';
            }
            // Handle different URL field types
            if (is_array($value)) {
                // For link fields that return array with url key
                if (isset($value['url'])) {
                    $url = esc_url($value['url']);
                    $link_text = !empty($link_text) ? esc_html($link_text) : $url;
                    if ($link_clickable === 'no' || empty($link_clickable)) {
                        return $url;
                    }
                    // Check if the link should open in a new tab
                    $target_attr = ($open_link_in_new_tab === 'yes') ? ' target="_blank" rel="noopener noreferrer"' : '';
                
                    return '<a href="' . esc_attr($url) . '"' . $target_attr . '>' . esc_html($link_text) . '</a>';
                }
                // For other array formats, try to get the first value
                return esc_url(reset($value));
            } elseif (is_string($value)) {
                $url = esc_url($value);
                $link_text = !empty($link_text) ? esc_html($link_text) : $url;
                if ($link_clickable === 'no' || empty($link_clickable)) {
                    return $url;
                }
                // Check if the link should open in a new tab
               
                $target_attr = ($open_link_in_new_tab === 'yes') ? ' target="_blank" rel="noopener noreferrer"' : '';
            
                return '<a href="' . esc_attr($url) . '"' . $target_attr . '>' . esc_html($link_text) . '</a>';
            }
            
            return '';
        }
    }
}

new AcfRepeaterUrlTag();
