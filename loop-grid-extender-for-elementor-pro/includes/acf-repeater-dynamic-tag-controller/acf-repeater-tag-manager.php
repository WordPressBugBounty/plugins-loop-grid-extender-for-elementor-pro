<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

if (!defined('ABSPATH')) {
    exit;
}


if (!class_exists('LGEFEP_ACF_REPEATER_TAG_MANAGER')) {
    class LGEFEP_ACF_REPEATER_TAG_MANAGER {
        public function __construct() {
            $this->lgefep_load_tag_classes();
        }

        public function lgefep_load_tag_classes(){
            $tag_classes = self::get_tag_classes_names();
            
            foreach ( $tag_classes as $class ) {
                $file_path = plugin_dir_path( __FILE__ ) . 'acf-repeater-tags/' . $class . '.php';
               
                if ( file_exists( $file_path ) ) {
                    require_once $file_path;
                    $full_class_name = 'LGEFEP\\Includes\\ACF_REPEATER_DYNAMIC_TAG\\' . $class;
                    class_exists( $full_class_name );
                }
            }
        }

        public static function get_tag_classes_names() {
            $available_tags = ['AcfRepeaterImageTag', 'AcfRepeaterPostTitleTag', 'AcfRepeaterTextTag', 'AcfRepeaterUrlTag', 'AcfRepeaterEmailTag', 'AcfRepeaterNumberTag', 'AcfRepeaterWysiwygTag'];
            return $available_tags;
        }
        
        public function get_repeater_value($sub_field_key) {
            $sub_field_key = esc_attr($sub_field_key);
            $post_id = get_the_ID();
            // Handle virtual posts from Elementor Loop Grid
            if ($post_id < 0) {
                $abs_id = abs($post_id);
                $id_str = (string) $abs_id;
                $current_index = (int) substr($id_str, -2);         // get last 2 digits
                $parent_post_id = (int) substr($id_str, 0, -8);      // remove 6+2 digits
                $post_id = $parent_post_id;
            }
             else {
                $document = \Elementor\Plugin::$instance->documents->get_current();
                $current_index = ($document instanceof \ElementorPro\Modules\LoopBuilder\Documents\Loop)
                    ? ($document->get_settings('loop')['index'] ?? 0)
                    : 0;
            }
        
            // Get all ACF fields on this post
            $fields = get_field_objects($post_id);
            if (!$fields || empty($sub_field_key)) {
                return null;
            }
        
            foreach ($fields as $field) {
                if ($field['type'] === 'repeater' && !empty($field['value']) && is_array($field['value'])) {
                    foreach ($field['sub_fields'] as $sub_field) {
                        if ($sub_field['key'] === $sub_field_key) {
                            // Match found — get this repeater's data
                            $repeater_rows = $field['value'];
                            if (isset($repeater_rows[$current_index][$sub_field['name']])) {
                                return $repeater_rows[$current_index][$sub_field['name']];
                            }
                        }
                    }
                }
            }
        
            return null;
        }
        
    }
}

new LGEFEP_ACF_REPEATER_TAG_MANAGER();
