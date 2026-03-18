<?php
namespace LGEFEP\Includes;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('LGEFEP_REGISTER_DYNAMIC_ACF_REPEATER_TAG')) {
    class LGEFEP_REGISTER_DYNAMIC_ACF_REPEATER_TAG{

        public function __construct(){
            add_action( 'elementor/dynamic_tags/register', [$this, 'lgefep_register_acf_repeater_dynamic_tags'] );
            
        }

        public function lgefep_register_acf_repeater_dynamic_tags($dynamic_tags){
         
            $tag_classes = \LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\LGEFEP_ACF_REPEATER_TAG_MANAGER::get_tag_classes_names();
            foreach ($tag_classes as $class) {
                $full_class_name = 'LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG\\' . $class;
                if (class_exists($full_class_name)) {
                    $tag = new $full_class_name();
                    if ($tag->get_name() !== 'lgefep-acf-repeater-post-title') {
                        $this->register_controls($tag);
                    }
                    $dynamic_tags->register($tag);
                }
            }
            
        }

        public function register_controls($tag) { 
            $supported_fields = method_exists($tag, 'get_supported_fields') ? $tag->get_supported_fields() : [];
            $group_options = $this->get_group_control_options($supported_fields, $tag);
            if (empty($group_options)) {
                return;
            }
    
            $tag->start_controls_section(
                'lgefep_acf_repeater_section',
                [
                    'label' => __('ACF Repeater', 'loop-grid-extender-for-elementor-pro'),
                ]
            );
    
            $tag->add_control(
                'lgefep_acf_repeater_field_control',
                [
                    'label'   => esc_html__('ACF Repeater Field', 'loop-grid-extender-for-elementor-pro'),
                    'type'    => \Elementor\Controls_Manager::SELECT,
                    'groups'  => $group_options,
                    'classes' => 'lgefep-acf-repeater-groups',
                    'frontend_available' => true,
                    'description' => __('Important: To see the changes in the preview, please set the Preview Settings to a post of the correct post type.', 'loop-grid-extender-for-elementor-pro'),

                ]
            );

            $link_types = ['url', 'link', 'page_link', 'file'];

            if (array_intersect($link_types, $supported_fields)) {
                $tag->add_control(
                    'lgefep_acf_repeater_link_clickable',
                    [
                        'label' => esc_html__('Using Link outside button?', 'loop-grid-extender-for-elementor-pro'),
                        'type' => \Elementor\Controls_Manager::SWITCHER,
                        'default' => 'no',
                        'description' => __('<div style="color: #818a96; font-size: 12px; font-style: italic;">If you are using the link outside the button, then you can use this option to make the link clickable.<br><strong style="color: #ff0000;">Only use this if you\'re not using a link for a button.</strong></div>', 'loop-grid-extender-for-elementor-pro'),
                    ]
                );
            }

            $tag->add_control(
                'lgefep_acf_repeater_link_text',
                [
                    'label'   => esc_html__('Link Text', 'loop-grid-extender-for-elementor-pro'),
                    'type'    => \Elementor\Controls_Manager::TEXT,
                    'placeholder' => esc_attr__('Enter Text.', 'loop-grid-extender-for-elementor-pro'),
                    'classes' => 'lgefep-acf-repeater-type',
                    'condition' => [
                        'lgefep_acf_repeater_link_clickable' => 'yes',
                    ],
                    'frontend_available' => true,
                    'description' => __(
            '<div style="color: #818a96; font-size: 12px; font-style: italic;">
                Type the text for the link.
            </div>',
            'loop-grid-extender-for-elementor-pro'
        ),
        
                ]
            );
        
            $tag->add_control(
                'lgefep_acf_repeater_open_link_in_new_tab',
                [
                    'label'   => esc_html__('Open Link in New Tab', 'loop-grid-extender-for-elementor-pro'),
                    'type'    => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'no',
                    'condition' => [
                        'lgefep_acf_repeater_link_clickable' => 'yes',
                    ],
                ]
            );
        
            $tag->end_controls_section();
        }

        public function get_group_control_options($supported_fields, $tag = null) {
            $grouped_options = ['' => __('Select Repeater Group', 'loop-grid-extender-for-elementor-pro')];
        
            $acf_groups = acf_get_field_groups();
        
            foreach ($acf_groups as $group) {
                $fields = acf_get_fields($group['key']);
                if (!$fields) {
                    continue;
                }
        
                foreach ($fields as $field) {
                    if ($field['type'] === 'repeater' && !empty($field['sub_fields'])) {
                        $sub_field_options = [];
        
                        foreach ($field['sub_fields'] as $sub_field) {
                            if (!empty($supported_fields) && !in_array($sub_field['type'], $supported_fields)) {
                                continue;
                            }
        
                            $option_key = $sub_field['key'];
                            $option_label = $sub_field['label'];
                            $sub_field_options[$option_key] = $option_label;
                        }
        
                        if (!empty($sub_field_options)) {
                            $grouped_options[] = [
                                'label' => $field['label'],
                                'options' => $sub_field_options,
                            ];
                        }
                    }
                }
            }
        
            return $grouped_options;
        }
        
        
        
    }
}

new LGEFEP_REGISTER_DYNAMIC_ACF_REPEATER_TAG();