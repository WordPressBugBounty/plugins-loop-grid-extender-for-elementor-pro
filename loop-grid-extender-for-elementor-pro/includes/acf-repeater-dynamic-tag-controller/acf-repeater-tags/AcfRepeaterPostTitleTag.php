<?php
namespace LGEFEP\Includes\ACF_REPEATER_DYNAMIC_TAG;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('AcfRepeaterPostTitleTag')) {
    class AcfRepeaterPostTitleTag extends \Elementor\Core\DynamicTags\Tag {
        public function get_name() {
            return 'lgefep-acf-repeater-post-title';
        }

        public function get_title() {
            return 'Repeater Post Title';
        }

        public function get_group() {
            return 'acf';
        }

        public function get_categories() {
            return [\Elementor\Modules\DynamicTags\Module::TEXT_CATEGORY];
        }

        public function render() {
            $post_id = get_the_ID();
            if ( $post_id < 0 ) {
                // This is a virtual post
                $original_post_id = abs( $post_id );
                $original_post_id = explode( '999999', $original_post_id )[0];
                $post = get_post( $original_post_id );
            } else {
                $post = get_post( $post_id );
            }
            if ( !$post ) {
                return '';
            }
            $post_title = $post->post_title;
            echo wp_kses_post($post_title);
        }
    }
}

new AcfRepeaterPostTitleTag();