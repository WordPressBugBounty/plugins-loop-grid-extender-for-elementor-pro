<?php
/**
 * ACF Repeater Image tag for link (URL) controls.
 *
 * @package loop-grid-extender-for-elementor-pro
 */

namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

use ElementorPro\Modules\DynamicTags\Tags\Base\Data_Tag;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( __NAMESPACE__ . '\AcfRepeaterImageUrlTag' ) ) {
	/**
	 * "ACF Repeater Image" for link (URL) controls.
	 *
	 * Elementor turns the value of a tag used in a URL control into a string, so the image array that
	 * AcfRepeaterImageTag gives to image controls cannot be used there. This tag has the same title and
	 * reads the same image sub fields, but returns the image URL.
	 */
	class AcfRepeaterImageUrlTag extends Data_Tag {

		/**
		 * Tag name.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'acf-repeater-image-url';
		}

		/**
		 * Tag title, the same as the image tag so the link field lists "ACF Repeater Image".
		 *
		 * @return string
		 */
		public function get_title() {
			return 'ACF Repeater Image';
		}

		/**
		 * Tag group.
		 *
		 * @return string
		 */
		public function get_group() {
			return 'acf';
		}

		/**
		 * URL controls only; image controls use AcfRepeaterImageTag.
		 *
		 * @return string[]
		 */
		public function get_categories() {
			return array( \Elementor\Modules\DynamicTags\Module::URL_CATEGORY );
		}

		/**
		 * ACF sub field types listed for this tag.
		 *
		 * @return string[]
		 */
		public function get_supported_fields() {
			return array( 'image' );
		}

		/**
		 * Print the image URL.
		 *
		 * @return void
		 */
		public function render() {
			echo esc_url( $this->get_value() );
		}

		/**
		 * Image URL of the current repeater row, for the ACF return formats Array, Image URL and Image ID.
		 *
		 * @param array $options Unused.
		 * @return string
		 */
		public function get_value( array $options = array() ) {
			$field_key = $this->get_settings( 'lgefep_acf_repeater_field_control' );

			if ( empty( $field_key ) || ! is_string( $field_key ) ) {
				return '';
			}

			$manager = new LGEFEP_ACF_REPEATER_TAG_MANAGER();
			$value   = $manager->get_repeater_value( $field_key );
			$url     = '';

			if ( is_array( $value ) ) {
				if ( ! empty( $value['url'] ) && is_string( $value['url'] ) ) {
					$url = $value['url'];
				} elseif ( ! empty( $value['ID'] ) && is_numeric( $value['ID'] ) ) {
					$url = wp_get_attachment_url( (int) $value['ID'] );
				}
			} elseif ( is_numeric( $value ) ) {
				$url = wp_get_attachment_url( (int) $value );
			} elseif ( is_string( $value ) ) {
				$url = $value;
			}

			return is_string( $url ) ? esc_url_raw( $url ) : '';
		}
	}
}

new AcfRepeaterImageUrlTag();
