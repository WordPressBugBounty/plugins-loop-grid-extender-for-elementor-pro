<?php
namespace LGEFEP\Includes;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('LGEFEP_REGISTER_DYNAMIC_ACF_REPEATER_TAG')) {
    class LGEFEP_REGISTER_DYNAMIC_ACF_REPEATER_TAG{

        public function __construct(){
            add_action( 'elementor/dynamic_tags/register', [$this, 'lgefep_register_acf_repeater_dynamic_tags'] );
			add_filter( 'elementor/document/load/data', array( $this, 'lgefep_migrate_legacy_image_link_tags_in_data' ), 10, 2 );
			// Priority 1: run before other before_render callbacks (such as Elementor Pro display conditions) read and cache the settings.
			add_action( 'elementor/frontend/before_render', array( $this, 'lgefep_migrate_legacy_image_link_tags' ), 1, 1 );
			add_action( 'elementor/widget/before_render_content', array( $this, 'lgefep_migrate_legacy_image_link_tags' ), 1, 1 );
			// The CSS pass parses dynamic tags of all controls, so a legacy tag left in a link control would raise an "Array to string conversion" warning.
			add_action( 'elementor/element/before_parse_css', array( $this, 'lgefep_migrate_legacy_image_link_tags_for_css' ), 10, 2 );
        }

		/**
		 * Link (URL) controls saved with "ACF Repeater Image" (acf-repeater-image) got the image array, which Elementor
		 * prints as "Array". Link controls now use acf-repeater-image-url, listed with the same title, so switch those
		 * saved links when a document is loaded (editor, so the editor shows and saves the new tag).
		 *
		 * @param array                         $data     Elements data.
		 * @param \Elementor\Core\Base\Document $document Document being loaded.
		 * @return array
		 */
		public function lgefep_migrate_legacy_image_link_tags_in_data( $data, $document = null ) {
			unset( $document );

			if ( ! is_array( $data ) ) {
				return $data;
			}

			return $this->lgefep_migrate_elements_data( $data );
		}

		/**
		 * Same switch for each element before it is rendered (front end, cached elements and editor preview),
		 * because front end rendering reads the saved data without the load filter.
		 *
		 * @param \Elementor\Element_Base $element The element about to be rendered.
		 * @return void
		 */
		public function lgefep_migrate_legacy_image_link_tags( $element ) {
			if ( ! $element instanceof \Elementor\Controls_Stack ) {
				return;
			}

			$settings = $element->get_settings();
			$controls = $element->get_controls();

			if ( ! is_array( $settings ) || ! is_array( $controls ) ) {
				return;
			}

			$migrated = $this->lgefep_migrate_settings( $settings, $controls );

			foreach ( $migrated as $key => $value ) {
				if ( ! array_key_exists( $key, $settings ) || $settings[ $key ] !== $value ) {
					$element->set_settings( $key, $value );
				}
			}
		}

		/**
		 * Same switch for the CSS pass, which parses the element settings before it is rendered.
		 *
		 * @param \Elementor\Core\Files\CSS\Post $css_file The CSS file being generated.
		 * @param \Elementor\Element_Base        $element  The element whose CSS is generated.
		 * @return void
		 */
		public function lgefep_migrate_legacy_image_link_tags_for_css( $css_file, $element ) {
			unset( $css_file );
			$this->lgefep_migrate_legacy_image_link_tags( $element );
		}

		/**
		 * Walk elements data (and their child elements) and switch the legacy link tags.
		 *
		 * @param array $elements Elements data.
		 * @return array
		 */
		private function lgefep_migrate_elements_data( array $elements ) {
			foreach ( $elements as $index => $element ) {
				if ( ! is_array( $element ) ) {
					continue;
				}

				if ( ! empty( $element['settings'] ) && is_array( $element['settings'] ) ) {
					$controls = $this->lgefep_get_element_controls( $element );

					if ( $controls ) {
						$elements[ $index ]['settings'] = $this->lgefep_migrate_settings( $element['settings'], $controls );
					}
				}

				if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
					$elements[ $index ]['elements'] = $this->lgefep_migrate_elements_data( $element['elements'] );
				}
			}

			return $elements;
		}

		/**
		 * Controls of an element or widget type from its data.
		 *
		 * @param array $element Element data.
		 * @return array
		 */
		private function lgefep_get_element_controls( array $element ) {
			$plugin = \Elementor\Plugin::$instance;
			$type   = null;

			if ( isset( $element['elType'] ) && 'widget' === $element['elType'] ) {
				if ( ! empty( $element['widgetType'] ) && is_string( $element['widgetType'] ) ) {
					$type = $plugin->widgets_manager->get_widget_types( $element['widgetType'] );
				}
			} elseif ( ! empty( $element['elType'] ) && is_string( $element['elType'] ) ) {
				$type = $plugin->elements_manager->get_element_types( $element['elType'] );
			}

			if ( ! $type instanceof \Elementor\Controls_Stack ) {
				return array();
			}

			$controls = $type->get_controls();

			return is_array( $controls ) ? $controls : array();
		}

		/**
		 * Switch the legacy link tags in element settings: the element dynamic tags and the dynamic tags
		 * of each repeater item (for example Icon List items).
		 *
		 * @param array $settings Element settings.
		 * @param array $controls Element controls.
		 * @return array
		 */
		private function lgefep_migrate_settings( array $settings, array $controls ) {
			if ( ! empty( $settings['__dynamic__'] ) && is_array( $settings['__dynamic__'] ) ) {
				$settings['__dynamic__'] = $this->lgefep_switch_image_link_tags( $settings['__dynamic__'], $controls );
			}

			foreach ( $controls as $control_name => $control ) {
				if ( empty( $control['type'] ) || \Elementor\Controls_Manager::REPEATER !== $control['type'] || empty( $control['fields'] ) || empty( $settings[ $control_name ] ) || ! is_array( $settings[ $control_name ] ) ) {
					continue;
				}

				foreach ( $settings[ $control_name ] as $index => $item ) {
					if ( is_array( $item ) && ! empty( $item['__dynamic__'] ) && is_array( $item['__dynamic__'] ) ) {
						$settings[ $control_name ][ $index ]['__dynamic__'] = $this->lgefep_switch_image_link_tags( $item['__dynamic__'], $control['fields'] );
					}
				}
			}

			return $settings;
		}

		/**
		 * Switch acf-repeater-image tags used in URL controls to acf-repeater-image-url.
		 *
		 * @param array $dynamic  Dynamic tags keyed by control name.
		 * @param array $controls Controls (or repeater fields).
		 * @return array
		 */
		private function lgefep_switch_image_link_tags( array $dynamic, array $controls ) {
			foreach ( $dynamic as $control_name => $tag_text ) {
				if ( ! is_string( $tag_text ) || false === strpos( $tag_text, 'name="acf-repeater-image"' ) ) {
					continue;
				}

				$type = '';

				foreach ( $controls as $key => $control ) {
					$name = isset( $control['name'] ) ? $control['name'] : $key;

					if ( $name === $control_name ) {
						$type = isset( $control['type'] ) ? $control['type'] : '';
						break;
					}
				}

				if ( \Elementor\Controls_Manager::URL === $type ) {
					$dynamic[ $control_name ] = str_replace( 'name="acf-repeater-image"', 'name="acf-repeater-image-url"', $tag_text );
				}
			}

			return $dynamic;
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