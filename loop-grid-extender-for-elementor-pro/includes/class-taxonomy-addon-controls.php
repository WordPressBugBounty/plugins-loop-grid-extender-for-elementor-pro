<?php

if(!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
use LGEFEP\Includes\LGEFEP_ADDON_RENDER;
use Elementor\Controls_Manager;
use ElementorPro\Plugin;

if(!class_exists('LGEFEP_Taxonomy_Addon_Controls')){
    class LGEFEP_Taxonomy_Addon_Controls{

        private $this_responsive_controls = array();

        public function __construct(){
            // Register custom controls for taxonomy filter widget
            add_action("elementor/element/taxonomy-filter/section_taxonomy_filter/before_section_end", [$this, 'register_controls'], 10);
            // Update style controls for taxonomy filter widget
            add_action("elementor/element/taxonomy-filter/section_design_layout/before_section_end", [$this, 'update_style_controls'], 10, 2);
            // Render the widget with custom content if enabled
            add_action('elementor/widget/render_content', [$this, 'render_widget'], 1, 2);

            // Enqueue scripts and styles
            add_action('elementor/frontend/after_enqueue_scripts', [$this, 'enqueue_scripts']);
            add_action('elementor/frontend/after_enqueue_styles', [$this, 'enqueue_styles']);
            add_action('elementor/editor/before_enqueue_styles', [$this, 'editor_assets']);
            add_action( 'wp_ajax_lgefep_elementor_review_notice', array( $this, 'lgefep_elementor_review_notice' ) );
            add_filter('elementor/files/css/selectors', [$this, 'update_selectors'], 10, 3);
        }

        public function update_selectors($control, $value, $base){
            if(isset($control['name']) && isset($this->this_responsive_controls[$control['name']])){
                foreach($this->this_responsive_controls[$control['name']] as $key => $value){
                    if(isset($control[$key]) && is_string($control[$key])){
                        $control[$key] = array($control[$key], $value);
                    }else if(isset($control[$key]) && is_array($control[$key])){
                        $control[$key] = array_merge($control[$key], $value);
                    }else if(!isset($control[$key])){
                        $control[$key] = $value;
                    }
                }
            }
            return $control;
        }

        public function register_controls($element){
            $element->add_control(
                'lgefep_taxonomy_dropdown',
                [
                    'label' => __('Smart Filters', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'no',
                    'label_on' => __('Yes', 'loop-grid-extender-for-elementor-pro'),
                    'label_off' => __('No', 'loop-grid-extender-for-elementor-pro'),
                    'return_value' => 'yes',
                    'condition' => [
                        'selected_element!' => '',
                    ],
                ]
            );

            $element->add_control(
                'lgefep_taxonomy_dropdown_style',
                [
                    'label' => __('Type', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'default' => __('Default', 'loop-grid-extender-for-elementor-pro'),
                        'dropdown' => __('Dropdown', 'loop-grid-extender-for-elementor-pro'),
                        'checkbox' => __('Checkbox (PRO)', 'loop-grid-extender-for-elementor-pro'),
                    ],
                    'default' => 'default',
                    'condition' => [
                        'lgefep_taxonomy_dropdown' => 'yes',
                        'selected_element!' => '',
                    ],
                ]
            );

            $element->add_control(
                'lgefep_taxonomy_show_count',
                [
                    'label' => __('Show Post Count', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::SWITCHER,
                    'default' => 'no',
                    'condition' => [
                        'lgefep_taxonomy_dropdown' => 'yes',
                        'selected_element!' => '',
                    ],
                ]
            );
            $element->add_control(
                'lgefep_taxonomy_sorting',
                [
                    'label' => __('Sorting Order', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::SELECT,
                    'options' => [
                        'alphabetical_asc' => __('Alphabetical (A-Z)', 'loop-grid-extender-for-elementor-pro'),
                        'alphabetical_desc' => __('Alphabetical (Z-A)', 'loop-grid-extender-for-elementor-pro'),
                        'count_asc' => __('Count (Low to High)', 'loop-grid-extender-for-elementor-pro'),
                        'count_desc' => __('Count (High to Low)', 'loop-grid-extender-for-elementor-pro'),
                    ],
                    'default' => 'alphabetical_asc',
                    'condition' => [
                        'lgefep_taxonomy_dropdown' => 'yes',
                        'selected_element!' => '',
                    ],
                ]
            );

            $element->add_control(
                'lgefep_taxonomy_exclude_terms',
                [
                    'label' => __('Exclude Terms', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'description' => __('Enter the terms to exclude from the dropdown. Separate terms with commas.', 'loop-grid-extender-for-elementor-pro'),
                    'condition' => [
                        'lgefep_taxonomy_dropdown' => 'yes',
                        'lgefep_taxonomy_dropdown_style!' => 'checkbox',
                        'selected_element!' => '',  
                    ],
                ]
            );

            $element->add_control(
                'lgefep_taxonomy_include_terms',
                [
                    'label' => __('Include Terms', 'loop-grid-extender-for-elementor-pro'),
                    'type' => \Elementor\Controls_Manager::TEXT,
                    'description' => __('Enter the terms to include in the dropdown. Separate terms with commas.', 'loop-grid-extender-for-elementor-pro'),
                    'condition' => [
                        'lgefep_taxonomy_dropdown' => 'yes',
                        'lgefep_taxonomy_dropdown_style!' => 'checkbox',
                        'selected_element!' => '',
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
							'lgefep_taxonomy_dropdown' => 'yes',
						],
					]
				);
			}

        }


        /**
         * Update style controls for the taxonomy filter widget.
         *
         * @param \Elementor\Widget_Base $widget
         */
        public function update_style_controls($widget){
            $elementor = \Elementor\Plugin::instance(); // Get Elementor instance

            // Update space between filter items
            $this->update_space_between_control($elementor, $widget);
            // Update typography controls
            $this->update_typography_control($elementor, $widget);

            // Update text styling for each state
            foreach(['normal', 'hover', 'active'] as $state){
                $this->update_text_styling_control($elementor, $widget, $state);
            }

            // Update border radius control
            $this->update_border_radius_control($elementor, $widget);

            // Update padding control
            $this->update_padding_control($elementor, $widget);
        }

        /**
         * Update the space between filter items.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         */
        public function update_space_between_control($elementor, $widget){
            $control_name='taxonomy_filter_items_space_between';

            // Set CSS variable for space between
            $value='--lgefep-taxonomy-filter-space-between: {{SIZE}}{{UNIT}};';

            $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
        }

        /**
         * Update typography controls for the filter bar.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         */
        public function update_typography_control($elementor, $widget){
            // Define typography controls and their CSS variables
            $controls=[
                'font_family' =>  '--lgefep-taxonomy-filter-font-family: "{{VALUE}}";',
                'font_size' =>  '--lgefep-taxonomy-filter-font-size: {{SIZE}}{{UNIT}};',
                'font_weight' =>  '--lgefep-taxonomy-filter-font-weight: {{VALUE}};',
                'text_transform' =>  '--lgefep-taxonomy-filter-text-transform: {{VALUE}};',
                'font_style' =>  '--lgefep-taxonomy-filter-font-style: {{VALUE}};',
                'text_decoration' =>  '--lgefep-taxonomy-filter-text-decoration: {{VALUE}};',
                'line_height' =>  '--lgefep-taxonomy-filter-line-height: {{SIZE}}{{UNIT}};',
                'letter_spacing' =>  '--lgefep-taxonomy-filter-letter-spacing: {{SIZE}}{{UNIT}};',
                'word_spacing' =>  '--lgefep-taxonomy-filter-word-spacing: {{SIZE}}{{UNIT}};',
            ];

            // Loop through each typography control and update
            foreach($controls as $key => $value){
                $control_name = 'taxonomy_filter_typography_'.$key;

                $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
            }
        }

        /**
         * Update text styling controls (color, shadow, background, border, box shadow) for a given state.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_text_styling_control($elementor, $widget, $state){
            // List of controls to update for each state
            $controls = array(
                'text_color',
                'text_shadow',
                'background',
                'border',
                'box_shadow'
            );

            // Loop through each control and update accordingly
            foreach($controls as $control){
                switch($control){
                    case 'text_color':
                        $this->update_text_color_control($elementor, $widget, $state);
                        break;
                    case 'text_shadow':
                        $this->update_text_shadow_control($elementor, $widget, $state);
                        break;
                    case 'background':
                        $this->update_background_control($elementor, $widget, $state);
                        break;
                    case 'border':
                        $this->update_border_control($elementor, $widget, $state);
                        break;
                    case 'box_shadow':
                        $this->update_box_shadow_control($elementor, $widget, $state);
                        break;
                }
            }
        }

        /**
         * Update border radius control for the filter bar.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         */
        public function update_border_radius_control($elementor, $widget){
            $control_name='taxonomy_filter_border_radius';
  
            $value='--lgefep-taxonomy-filter-border-radius-top: {{TOP}}{{UNIT}}; --lgefep-taxonomy-filter-border-radius-right: {{RIGHT}}{{UNIT}}; --lgefep-taxonomy-filter-border-radius-bottom: {{BOTTOM}}{{UNIT}}; --lgefep-taxonomy-filter-border-radius-left: {{LEFT}}{{UNIT}};';

            $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
        }   

        /**
         * Update padding control for the filter bar.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         */
        public function update_padding_control($elementor, $widget){
            $control_name='taxonomy_filter_padding';

            // Set CSS variable for padding
            $value='--lgefep-taxonomy-filter-padding-top: {{TOP}}{{UNIT}}; --lgefep-taxonomy-filter-padding-right: {{RIGHT}}{{UNIT}}; --lgefep-taxonomy-filter-padding-bottom: {{BOTTOM}}{{UNIT}}; --lgefep-taxonomy-filter-padding-left: {{LEFT}}{{UNIT}};';

            $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
        }

        public function update_responsive_controls($elementor, $widget, $control_name, $key_type, $value){
            $control_names=array(
                $control_name
            );

            foreach($control_names as $control_name){   
                // Get control data for responsive
                $control_data = $elementor->controls_manager->get_control_from_stack( $widget->get_unique_name(), $control_name );

                if ( is_wp_error( $control_data ) ) {
                    continue;
                }

                $control_data = $this->update_control_selector_data($control_name, $key_type, $control_data, $value, $widget);

                if(isset($control_data['is_responsive']) && $control_data['is_responsive']){
                    $updated_data=$control_data[$key_type];

                    $widget->update_responsive_control($control_name, [
                        $key_type => $updated_data
                    ]);

                    $responsive_screen=array('mobile', 'tablet');
                    $value='selectors' === $key_type ? array('.lgefep-taxonomy-filter-{{ID}}'=>$value) : array($value);

                    foreach($responsive_screen as $screen){
                        $this->this_responsive_controls[esc_attr($control_name.'_'.$screen)]=array(esc_attr($key_type)=>$value);
                    }
                }
            }
        }

        /**
         * Update the selector data for a given control.
         *
         * @param string $key
         * @param string $update_key
         * @param array $control_data
         * @param array $value
         * @param \Elementor\Widget_Base $widget
         */
        public function update_control_selector_data($key, $update_key, $control_data, $value, $widget){
            $value='selectors' === $update_key ? array('.lgefep-taxonomy-filter-{{ID}}'=>$value) : array($value);

            // Update the selector data for the control
            if(isset($control_data[$update_key]) && is_string($control_data[$update_key])){
                $control_data[$update_key] = array($control_data[$update_key], $value);
            }else if(isset($control_data[$update_key]) && is_array($control_data[$update_key])){
                $control_data[$update_key] = array_merge($control_data[$update_key], $value);
            }else if(!isset($control_data[$update_key]) || !array_key_exists($update_key, $control_data)){
                $control_data[$update_key] = $value;
            }

            // Update the control with new selector data
            $widget->update_control($key, $control_data);

            return $control_data;
        }

        /**
         * Render the widget content, replacing with custom output if Smart Filters are enabled.
         *
         * @param string $widget_content
         * @param \Elementor\Widget_Base $widget
         * @return string
         */
        public function render_widget($widget_content, $widget) {
            if ($widget->get_name() === 'taxonomy-filter') {
                $lgefep_addon_render = new \LGEFEP\Includes\LGEFEP_ADDON_RENDER($widget);
                $widget_content = $lgefep_addon_render->render_widget($widget_content);
            }
            return $widget_content;
        }


        /**
         * Update the text color control for a given state.
         *
         * @param array $control_data
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_text_color_control($elementor, $widget, $state){

            $control_name='taxonomy_filter_'. $state. '_text_color';

            // // Set CSS variable for text color
            $value='--lgefep-taxonomy-'.esc_attr($state).'-filter-text-color: {{VALUE}};';

            $this->update_responsive_controls($elementor,$widget,  $control_name, 'selectors', $value);
        }

        /**
         * Update the text shadow control for a given state.
         *
         * @param array $control_data
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_text_shadow_control($elementor, $widget, $state){
            $control_name = 'taxonomy_filter_'. esc_attr($state). '_text_shadow_text_shadow';

            // Set CSS variable for text shadow
            $value='--lgefep-taxonomy-'.esc_attr($state).'-filter-text-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{COLOR}};';

            $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
        }

        /**
         * Update background controls for a given state.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_background_control($elementor, $widget, $state){
            // Define background controls and their CSS variables
            $controls=array(
                'color'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-background-color: {{VALUE}};',
                'gradient_angle'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-background-gradient: linear-gradient({{SIZE}}{{UNIT}}, {{taxonomy_filter_'.esc_attr($state).'_background_color.VALUE}} {{taxonomy_filter_'.esc_attr($state).'_background_color_stop.SIZE}}{{taxonomy_filter_'.esc_attr($state).'_background_color_stop.UNIT}}, {{taxonomy_filter_'.esc_attr($state).'_background_color_b.VALUE}} {{taxonomy_filter_'.esc_attr($state).'_background_color_b_stop.SIZE}}{{taxonomy_filter_'.esc_attr($state).'_background_color_b_stop.UNIT}});',
                'gradient_position'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-background-gradient: radial-gradient(at {{VALUE}}, {{taxonomy_filter_'.esc_attr($state).'_background_color.VALUE}} {{taxonomy_filter_'.esc_attr($state).'_background_color_stop.SIZE}}{{taxonomy_filter_'.esc_attr($state).'_background_color_stop.UNIT}}, {{taxonomy_filter_'.esc_attr($state).'_background_color_b.VALUE}} {{taxonomy_filter_'.esc_attr($state).'_background_color_b_stop.SIZE}}{{taxonomy_filter_'.esc_attr($state).'_background_color_b_stop.UNIT}});',
            );

            // Loop through each background control and update
            foreach($controls as $key => $value){
                $control_name = 'taxonomy_filter_'.esc_attr($state).'_background_'.$key;
 
                $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
            }
        }

        /**
         * Update border controls for a given state.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_border_control($elementor, $widget, $state){
            // Define border controls and their CSS variables
            $controls=array(
                'color'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-border-color: {{VALUE}};',
                'width'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-border-width-top: {{TOP}}{{UNIT}}; --lgefep-taxonomy-'.esc_attr($state).'-filter-border-width-right: {{RIGHT}}{{UNIT}}; --lgefep-taxonomy-'.esc_attr($state).'-filter-border-width-bottom: {{BOTTOM}}{{UNIT}}; --lgefep-taxonomy-'.esc_attr($state).'-filter-border-width-left: {{LEFT}}{{UNIT}};',
                'border'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-border-style: {{VALUE}};',
            );
            
            // Loop through each border control and update
            foreach($controls as $key => $value){
                $control_name = 'taxonomy_filter_'.esc_attr($state).'_border_'.$key;

                $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
            }
        }

        /**
         * Update box shadow controls for a given state.
         *
         * @param \Elementor\Plugin $elementor
         * @param \Elementor\Widget_Base $widget
         * @param string $state
         */
        public function update_box_shadow_control($elementor, $widget, $state){
            // Define box shadow controls and their CSS variables
            $controls=array(
                'box_shadow'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-box-shadow: {{HORIZONTAL}}px {{VERTICAL}}px {{BLUR}}px {{SPREAD}}px {{COLOR}} {{box_shadow_position.VALUE}};',
                'box_shadow_position'=>'--lgefep-taxonomy-'.esc_attr($state).'-filter-box-shadow-position: {{VALUE}};',
            );
            
            // Loop through each box shadow control and update
            foreach($controls as $key => $value){
                $control_name = 'taxonomy_filter_'.esc_attr($state).'_box_shadow_'.esc_attr($key);

                $this->update_responsive_controls($elementor, $widget,  $control_name, 'selectors', $value);
            }
        }
        
        /**
         * Enqueue custom scripts for the filter bar.
         */
        public function enqueue_scripts(){
            // Only enqueue if widget-loop-filter style is enqueued
            if(function_exists('wp_style_is') && wp_style_is('widget-loop-filter', 'enqueued')){
                wp_enqueue_script('lgefep-filter-select2', LGEFEP_PLUGIN_URL . 'assets/js/select2.min.js', [], LGEFEP_VERSION, true);
                wp_enqueue_script('lgefep-filter-bar-script', LGEFEP_PLUGIN_URL . 'assets/js/index.min.js', ['jquery', 'lgefep-filter-select2'], LGEFEP_VERSION, true);
            }
        }

        public function editor_assets(){
            wp_enqueue_style('lgefep-filter-bar-style', LGEFEP_PLUGIN_URL . 'assets/css/editor.min.css', null, LGEFEP_VERSION);
            wp_enqueue_script(
                'lgefep-filter-bar-editor-script',
                LGEFEP_PLUGIN_URL . 'assets/js/editor.js',
                ['jquery'],
                LGEFEP_VERSION,
                true
            );
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


        /**
         * Enqueue custom styles for the filter bar.
         */
        public function enqueue_styles(){
            wp_enqueue_style('lgefep-filter-select2', LGEFEP_PLUGIN_URL . 'assets/css/select2.min.css', [], LGEFEP_VERSION);
            wp_enqueue_style('lgefep-filter-bar-style', LGEFEP_PLUGIN_URL . 'assets/css/index.min.css', ['widget-loop-filter', 'lgefep-filter-select2'], LGEFEP_VERSION);
        }
    }
}

// Instantiate the LGEFEP_Taxonomy_Addon_Controls class
new LGEFEP_Taxonomy_Addon_Controls();
