<?php

namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

if (!defined('ABSPATH')) {
    exit;
}

use Elementor\Controls_Manager;

class LGEFEP_ACF_LOOP_GRID_CONTROLLER {

    public function __construct() {
        add_action('elementor/element/loop-grid/section_query/before_section_end', [$this, 'add_acf_repeater_query_controls']);
        add_filter('elementor/query/query_args', [$this, 'add_acf_repeater_query_args'], 10, 2);
        add_filter('the_posts', [$this, 'lgefep_modify_the_posts'], 10, 2);
        add_action('elementor/editor/before_enqueue_styles', [$this, 'editor_assets']);
        add_action( 'wp_ajax_lgefep_elementor_review_notice', array( $this, 'lgefep_elementor_review_notice' ) );
    }

    public function add_acf_repeater_query_args( $query_args, $widget ) {
        $settings = $widget->get_settings();
        if ( isset( $settings['lgefep_acf_repeater_tag'] ) && $settings['lgefep_acf_repeater_tag'] === 'yes' ) {
            $query_args['lgefep_virtual_posts'] = 1;
            $query_args['lgefep_acf_repeater_field'] = esc_attr($settings['lgefep_acf_repeater_field']);
            $query_args['lgefep_acf_repeater_current_post_only'] = esc_attr($settings['lgefep_acf_repeater_current_post_only']);
            if ( $settings['lgefep_acf_repeater_current_post_only'] === 'yes' ) {
                $query_args['post__in'] = [get_the_ID()];
            }
        }
        return $query_args;
    }

    public function lgefep_modify_the_posts($posts, $query){
        if ( !isset( $query->query_vars['lgefep_virtual_posts'] ) || !$query->query_vars['lgefep_virtual_posts'] ) {
            return $posts;
        }
        $repeater_field = $query->get( 'lgefep_acf_repeater_field' );
        if ( !$repeater_field ) {
            return $posts;
        }
        $virtual_posts = [];
        foreach ( $posts as $post ) {
            $repeater_data = get_field( $repeater_field, $post->ID );
            if ( !$repeater_data || !is_array( $repeater_data ) ) {
                continue;
            }
            foreach ( $repeater_data as $index => $row ) {
                $virtual_post = new \stdClass();
                $virtual_post->ID = -1 * ($post->ID . '999999' . str_pad($index, 2, '0', STR_PAD_LEFT));
                $virtual_post->post_parent = $post->ID;
                $virtual_post->post_title = $post->post_title . ' - ' . $repeater_field . ' ' . ($index + 1);
                $virtual_post->post_status = 'publish';
                $virtual_post->post_type = $post->post_type;
                $virtual_post->filter = 'raw';
                // Add our custom data
                $virtual_post->acf_repeater_data = $row;
                $virtual_post->earluna_loop_index = $index;
                $virtual_posts[] = $virtual_post;
            }
        }
        // var_dump($virtual_posts);
        return $virtual_posts;
    }

    public function add_acf_repeater_query_controls($element) {
        $element->add_control(
            'lgefep_acf_repeater_tag',
            [
                'label' => __('Use ACF Repeater', 'loop-grid-extender-for-elementor-pro'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'no',
                'return_value' => 'yes',
                'frontend_available' => true,
            ]
        );

        $acf_repeater_fields = $this->get_acf_repeater_fields();
       
        $element->add_control(
            'lgefep_acf_repeater_field',
            [
                'label' => __('ACF Repeater Field', 'loop-grid-extender-for-elementor-pro'),
                'type' => Controls_Manager::SELECT,
                'options' => $acf_repeater_fields,
                'default' => '',
                'condition' => [
                    'lgefep_acf_repeater_tag' => 'yes',
                ],
            ]
        );

        $element->add_control(
            'lgefep_acf_repeater_current_post_only',
            [
                'label' => __('Current Post Repeater Only', 'loop-grid-extender-for-elementor-pro'),
                'type' => Controls_Manager::SWITCHER,
                'default' => 'yes',
                'label_on' => __('Yes', 'loop-grid-extender-for-elementor-pro'),
                'label_off' => __('No', 'loop-grid-extender-for-elementor-pro'),
                'condition' => [
                    'lgefep_acf_repeater_tag' => 'yes',
                ],
            ]
        );
        if ( ! get_option( 'lgefep_review_notice_dismiss' ) ) {
            $review_nonce = wp_create_nonce( 'lgefep_elementor_review' );
            $url          = admin_url( 'admin-ajax.php' );
            $html         = '<div class="lgefep_elementor_review_wrapper">';
            $html        .= '<div id="lgefep_elementor_review_dismiss" data-url="' . esc_url( $url ) . '" data-nonce="' . esc_attr( $review_nonce ) . '">Close Notice X</div>
                            <div class="lgefep_elementor_review_msg">' . __( 'Hope this addon solved your problem!', 'loop-grid-extender-for-elementor-pro' ) . '<br><a href="https://wordpress.org/support/plugin/loop-grid-extender-for-elementor-pro/reviews/#new-post" target="_blank"">Share the love with a ⭐⭐⭐⭐⭐ rating.</a><br><br></div>
                            <div class="lgefep_elementor_demo_btn"><a href="https://wordpress.org/support/plugin/loop-grid-extender-for-elementor-pro/reviews/#new-post" target="_blank">Submit Review</a></div>
                            </div>';

            $element->add_control(
                'lgefep_pro_image',
                [
                    'name'            => 'lgefep_pro_image',
                    'type'            =>  \Elementor\Controls_Manager::RAW_HTML,
                    'raw'             => $html,
                    'content_classes' => 'lgefep_elementor_review_notice',
                    'condition'       => [
                        'lgefep_acf_repeater_tag' => 'yes',
                    ],
                ]
            );
        }
    }

    public function lgefep_elementor_review_notice() {
        if ( ! check_ajax_referer( 'lgefep_elementor_review', 'nonce', false ) ) {
            wp_send_json_error( __( 'Invalid security token sent.', 'loop-grid-extender-for-elementor-pro' ) );
            wp_die( '0', 400 );
        }

        if ( isset( $_POST['lgefep_notice_dismiss'] ) && 'true' === sanitize_text_field(wp_unslash($_POST['lgefep_notice_dismiss'])) ) {
            update_option( 'lgefep_review_notice_dismiss', 'yes' );
        }
        exit;
    }

    public function get_acf_repeater_fields() {
        $repeater_fields = [];
    
        if ( function_exists( 'acf_get_field_groups' ) && function_exists( 'acf_get_fields' ) ) {
            $field_groups = acf_get_field_groups();
    
            foreach ( $field_groups as $group ) {
                $fields = acf_get_fields( $group );
                $this->lgefep_collect_repeater_fields( $fields, $repeater_fields );
            }
        }
        return $repeater_fields;
    }

    public function editor_assets(){
        wp_enqueue_style('lgefep-acf-loop-grid-style', LGEFEP_PLUGIN_URL . 'assets/css/editor.min.css', null, LGEFEP_VERSION);
        wp_enqueue_script(
            'lgefep-acf-loop-grid-editor-script',
            LGEFEP_PLUGIN_URL . 'assets/js/editor.js',
            ['jquery'],
            LGEFEP_VERSION,
            true
        );
    }
    
    private function lgefep_collect_repeater_fields( array $fields, array &$result, string $prefix = '' ) {
        foreach ( $fields as $field ) {
            if ( ! isset( $field['type'], $field['name'], $field['label'] ) ) {
                continue;
            }
    
            $field_name = $prefix ? "{$prefix}_{$field['name']}" : $field['name'];
    
            if ( $field['type'] === 'repeater' ) {
                $result[ $field_name ] = $field['label'];
            }
    
            if ( $field['type'] === 'group' && ! empty( $field['sub_fields'] ) ) {
                $this->lgefep_collect_repeater_fields( $field['sub_fields'], $result, $field_name );
            }
        }
    }
    
    
    
}

new LGEFEP_ACF_LOOP_GRID_CONTROLLER();
