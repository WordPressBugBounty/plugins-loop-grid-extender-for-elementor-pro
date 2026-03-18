<?php
namespace LGEFEP\Includes;
use ElementorPro\Modules\LoopFilter\Widgets\Taxonomy_Filter;
use ElementorPro\Modules\LoopFilter\Traits\Taxonomy_Filter_Trait;
use ElementorPro\Modules\ThemeBuilder\Module as ThemeBuilderModule;
use ElementorPro\Plugin;
use Elementor\Utils;

if(!defined('ABSPATH')){
    exit;
}

if(!class_exists('LGEFEP_ADDON_RENDER')){
    class LGEFEP_ADDON_RENDER extends Taxonomy_Filter{

        use Taxonomy_Filter_Trait;
        private $widget_data;

        public function __construct($obj){
            $this->widget_data = $obj;
        }

        private function has_empty_results($selected_element, $user_selected_taxonomy, $terms){
            if ( empty( $selected_element ) ) {
                $this->print_empty_results_if_editor( 'select_loop_widget' );
    
                return true;
            }
    
            if ( empty( $user_selected_taxonomy ) ) {
                $this->print_empty_results_if_editor( 'no_taxonomy_selected' );
    
                return true;
            }
    
            if ( empty( $terms ) ) {
                $this->print_empty_results_if_editor( 'no_terms_found' );
    
                return true;
            }
    
            return false;
        }

        /**
         * @return int
         */
        private function get_current_ID() {
            $post_id = 0;
            $theme_builder = ThemeBuilderModule::instance();
            $location = $theme_builder->get_locations_manager()->get_current_location();
            $documents = $theme_builder->get_conditions_manager()->get_documents_for_location( $location );

            if ( empty( $documents ) ) {
                return get_the_ID();
            }

            foreach ( $documents as $document ) {
                $post_id = $document->get_post()->ID;
            }

            return $post_id;
        }

        /**
	     * @return array
	     */
	    private function get_loop_widget_settings() {
		    $document = Plugin::elementor()->documents->get_doc_for_frontend( $this->get_current_ID() );

		    if ( ! $document ) {
		    	return [];
		    }

		    $widget_data = Utils::find_element_recursive( $document->get_elements_data(), $this->widget_data->get_settings_for_display( 'selected_element' ) );

		    return ! empty( $widget_data['settings'] ) ? $widget_data['settings'] : [];
	    }

        /**
         * @return boolean
         */
        private function is_term_excluded_by_query_control( $term, $loop_filter_module ) {
            $loop_widget_settings = $this->get_loop_widget_settings();
            $skin = ! empty( $loop_widget_settings['_skin'] ) ? $loop_widget_settings['_skin'] : 'post';
            
            return $loop_filter_module->is_term_not_selected_for_inclusion( $loop_widget_settings, $term, $skin )
                || $loop_filter_module->is_term_selected_for_exclusion( $loop_widget_settings, $term, $skin )
                || $loop_filter_module->should_exclude_term_by_manual_selection( $loop_widget_settings, $term, $this->widget_data->get_settings_for_display( 'taxonomy' ), $skin );
        }

        private function get_list_tag($type, $style){
            $list_tag = [
                'wrapper' => [
                    'dropdown' => 'select',
                    'checkbox' => 'div',
                ],
                'item' => [
                    'dropdown' => 'option',
                    'checkbox' => 'label',
                ],
            ];

            return $list_tag[$type][$style];
        }

        private function print_list_tag($tag){
            if(in_array($tag, ['select', 'option', 'label'])){
                return esc_html($tag);
            }

            return 'div';
        }

        /**
         * Calculate term depth in hierarchy
         */
        private function get_term_depth($term_id, $taxonomy) {
            $depth = 0;
            $current_term = get_term($term_id, $taxonomy);
            
            while ($current_term && !is_wp_error($current_term) && $current_term->parent > 0) {
                $depth++;
                $current_term = get_term($current_term->parent, $taxonomy);
                
                // Safety check to prevent infinite loops in case of circular references
                if ($depth > 10) {
                    break;
                }
            }
            
            return $depth;
        }

        /**
         * Sort terms based on the specified sorting option
         * Maintains hierarchical structure while sorting within each level
         */
        private function sort_terms($terms, $sorting_option) {
            if (empty($terms) || !is_array($terms)) {
                return $terms;
            }

            // Group terms by their parent
            $terms_by_parent = [];
            $parent_terms = [];
            
            foreach ($terms as $term) {
                if ($term->parent > 0) {
                    if (!isset($terms_by_parent[$term->parent])) {
                        $terms_by_parent[$term->parent] = [];
                    }
                    $terms_by_parent[$term->parent][] = $term;
                } else {
                    $parent_terms[] = $term;
                }
            }

            // Sort parent terms
            $parent_terms = $this->sort_term_array($parent_terms, $sorting_option);
            
            // Sort child terms within each parent
            foreach ($terms_by_parent as $parent_id => $children) {
                $terms_by_parent[$parent_id] = $this->sort_term_array($children, $sorting_option);
            }

            // Reconstruct the array maintaining hierarchy
            $sorted_terms = [];
            
            foreach ($parent_terms as $parent) {
                $sorted_terms[] = $parent;
                
                // Add children of this parent if they exist
                if (isset($terms_by_parent[$parent->term_id])) {
                    foreach ($terms_by_parent[$parent->term_id] as $child) {
                        $sorted_terms[] = $child;
                    }
                }
            }
            
            return $sorted_terms;
        }

        /**
         * Helper method to sort an array of terms
         */
        private function sort_term_array($term_array, $sorting_option) {
            if (empty($term_array) || !is_array($term_array)) {
                return $term_array;
            }

            switch ($sorting_option) {
                case 'alphabetical_asc':
                    usort($term_array, function($a, $b) {
                        return strcasecmp($a->name, $b->name);
                    });
                    break;
                    
                case 'alphabetical_desc':
                    usort($term_array, function($a, $b) {
                        return strcasecmp($b->name, $a->name);
                    });
                    break;
                    
                case 'count_asc':
                    usort($term_array, function($a, $b) {
                        return $a->count - $b->count;
                    });
                    break;
                    
                case 'count_desc':
                    usort($term_array, function($a, $b) {
                        return $b->count - $a->count;
                    });
                    break;
                    
                case 'default':
                default:
                    // Keep original order (usually by term ID or hierarchy)
                    break;
            }
            
            return $term_array;
        }

        public function render_widget($original_content = ''){
            $widget_settings = $this->widget_data->get_settings_for_display();
            $lgefep_taxonomy_dropdown = isset($widget_settings['lgefep_taxonomy_dropdown']) ? $widget_settings['lgefep_taxonomy_dropdown'] : 'no';
            $lgefep_taxonomy_dropdown_style = isset($widget_settings['lgefep_taxonomy_dropdown_style']) ? $widget_settings['lgefep_taxonomy_dropdown_style'] : 'default';
            $lgefep_taxonomy_show_count = isset($widget_settings['lgefep_taxonomy_show_count']) ? $widget_settings['lgefep_taxonomy_show_count'] : 'no';
            $lgefep_taxonomy_exclude_terms = isset($widget_settings['lgefep_taxonomy_exclude_terms']) ? $widget_settings['lgefep_taxonomy_exclude_terms'] : '';
            $lgefep_taxonomy_include_terms = isset($widget_settings['lgefep_taxonomy_include_terms']) ? $widget_settings['lgefep_taxonomy_include_terms'] : '';
            $lgefep_taxonomy_sorting = isset($widget_settings['lgefep_taxonomy_sorting']) ? $widget_settings['lgefep_taxonomy_sorting'] : 'default';
            $exclude_terms = !empty($lgefep_taxonomy_exclude_terms) ? explode(',', $lgefep_taxonomy_exclude_terms) : [];
            $include_terms = !empty($lgefep_taxonomy_include_terms) ? explode(',', $lgefep_taxonomy_include_terms) : [];
            $multi_select = isset($widget_settings['multiple_selection']) ? $widget_settings['multiple_selection'] : 'no';

            $selected_element = $widget_settings['selected_element'];
            $user_selected_taxonomy = $widget_settings['taxonomy'];
            
            $terms=$this->get_filtered_taxonomies($widget_settings, $widget_settings);
            
            // Apply sorting to terms
            $terms = $this->sort_terms($terms, $lgefep_taxonomy_sorting);
            
            // If smart filters is disabled or style is default, return original content with data span
            if ($lgefep_taxonomy_dropdown !== 'yes' || in_array($lgefep_taxonomy_dropdown_style, ['default'], true)) {
                // Only render span when style is default
                if ($lgefep_taxonomy_dropdown === 'yes' && $lgefep_taxonomy_dropdown_style === 'default') {
                    // Use existing filtering logic to get hierarchical excludes
                    $filtered_terms_data = $this->filterd_terms_by_id($terms, $user_selected_taxonomy, $exclude_terms);
                    $hierarchical_excludes = $this->get_hierarchical_excludes($terms, $user_selected_taxonomy, $exclude_terms);

                    // Prepare data for frontend
                    $frontend_data = [
                        'settings' => [
                            'taxonomy_dropdown' => $lgefep_taxonomy_dropdown,
                            'taxonomy_dropdown_style' => $lgefep_taxonomy_dropdown_style,
                            'show_count' => $lgefep_taxonomy_show_count,
                            'sorting' => $lgefep_taxonomy_sorting,
                            'include_terms' => $include_terms,
                            'hierarchical_exclude_terms' => $hierarchical_excludes,
                        ],
                        'terms' => [],
                        'total_count' => 0,
                    ];

                    if ($lgefep_taxonomy_show_count === 'yes') {
                        $term_data = [];
                        $total_count = 0;
                        if (!empty($terms) && is_array($terms)) {
                            foreach ($terms as $term) {
                                // Skip hierarchically excluded terms
                                if (in_array($term->slug, $hierarchical_excludes)) {
                                    continue;
                                }
                                
                                // Skip if include terms are specified and this term is not included
                                if (count($include_terms) > 0 && !in_array($term->slug, $include_terms)) {
                                    continue;
                                }
                                
                                $term_data[] = [
                                    'name' => $term->name,
                                    'slug' => $term->slug,
                                    'count' => (int) $term->count,
                                    'parent' => (int) $term->parent,
                                ];
                                
                                // Add to total count only if it's a parent category (no parent)
                                if ($term->parent === 0) {
                                    $total_count += (int) $term->count;
                                }
                            }
                        }
                        $frontend_data['terms'] = $term_data;
                        $frontend_data['total_count'] = $total_count;
                    }
                    
                    return '
                    <span class="lgefep-data-container" 
                          style="display: none;" 
                          data-lgefep-config="' . esc_attr(json_encode($frontend_data)) . '"
                          data-widget-id="' . esc_attr($this->widget_data->get_id()) . '">
                    </span>' . $original_content;
                }
                return $original_content;
            }

            // For dropdown and other custom styles, render custom widget
            if ( $this->has_empty_results( $selected_element, $user_selected_taxonomy, $terms ) ) {
                return $original_content;
            }

            $active_filters = [];
		    $loop_filter_module = Plugin::instance()->modules_manager->get_modules( 'loop-filter' );
		    $query_string_filters = $loop_filter_module->get_query_string_filters();

		    if ( array_key_exists( $selected_element, $query_string_filters ) ) {
		    	$active_filters = $query_string_filters[ $selected_element ]['taxonomy'];
		    }

            $active_terms = 0;
            $total_taxonomies = 0;
            $number_of_taxonomies = $widget_settings['number_of_taxonomies'];


            $this->widget_data->add_render_attribute( 'filter-bar', [
                'class' => 'lgefep-'.esc_attr($lgefep_taxonomy_dropdown_style) . ' lgefep-taxonomy-filter-'.esc_attr($this->widget_data->get_id()),
            ] );
            
            $this->widget_data->add_render_attribute( 'filter-bar-wrapper', [
                'class' => 'lgefep-filter-bar-wrapper',
                'data-lgefep-style' => esc_attr($lgefep_taxonomy_dropdown_style),
                'data-multi-select' => esc_attr($multi_select),
                'data-lgefep-id' => esc_attr($this->widget_data->get_id())
            ] );

            $this->widget_data->add_render_attribute( 'filter-list', [
                'class' => 'lgefep-filter-bar-list',
            ]);

            if($multi_select === 'yes'){
                $this->widget_data->add_render_attribute( 'filter-list', [
                    'multiple' => 'multiple',
                ] );
            }

            $tems_filtered=$this->filterd_terms_by_id($terms, $user_selected_taxonomy, $exclude_terms);
            ob_start();
            ?>
		<div <?php $this->widget_data->print_render_attribute_string( 'filter-bar' ); ?>>
            <div <?php $this->widget_data->print_render_attribute_string( 'filter-bar-wrapper' ); ?>>
                <?php ob_start(); ?>
                <?php foreach ( $terms as $term ) {
                    $total_taxonomies++;
                    $aria_pressed_value = 'false';

                    if(!isset($tems_filtered[$term->term_id])){
                        continue;
                    }

                    if(count($exclude_terms) > 0 && in_array($term->slug, $exclude_terms)){
                        continue;
                    }
                    
                    if(count($include_terms) > 0 && !in_array($term->slug, $include_terms)){
                        continue;
                    }
                    
                    if ( ! isset( $term->taxonomy ) || $this->is_term_excluded_by_query_control( $term, $loop_filter_module ) ) {
                        continue;
                    }

                    $term_taxonomy = $term->taxonomy;

                    if ( array_key_exists( $term_taxonomy, $active_filters ) && in_array( urldecode( $term->slug ), $active_filters[ $term_taxonomy ]['terms'] ) ) {
                        $aria_pressed_value = 'true';
                        $active_term=$term->slug;
                        $active_terms++;
                    }

                    if ( ! empty( $number_of_taxonomies ) && $total_taxonomies > $number_of_taxonomies ) {
                        continue;
                    }

                    $count='yes' === $lgefep_taxonomy_show_count ? ' ('.$term->count.')' : '';
                    $child_terms=get_term_children($term->term_id, $term->taxonomy);

                    // Get the depth level of the current term
                    $term_depth = $this->get_term_depth($term->term_id, $term->taxonomy);
                    
                    // This filter allows us to write the slug with non-latin characters as well, such as Hebrew.
                    $slug = apply_filters( 'lgefep_editable_slug', $term->slug, $term );

                    $list_attributs=$this->widget_data->add_render_attribute('list-item'.$term->term_id, [
                        'class' => 'lgefep-filter-bar-list-item e-filter-item',
                        'data-filter' => esc_attr($slug),
                        'aria-pressed' => esc_attr($aria_pressed_value),
                        'data-term-depth' => esc_attr($term_depth), // Add depth as data attribute
                    ]);

                    
                    if('true' === $aria_pressed_value){
                        $list_attributs->add_render_attribute('list-item'.$term->term_id, [
                            'selected' => 'selected',
                        ]);
                    }

                    // Enhanced hierarchy classification
                    if($term->parent > 0){
                        // Add depth-specific classes
                        $depth_class = 'lgefep-filter-bar-list-item-child-level-' . $term_depth;
                        $list_attributs->add_render_attribute('list-item'.$term->term_id, [
                            'class' => 'lgefep-filter-bar-list-item-child ' . $depth_class,
                        ]);
                    }else if(count($child_terms) > 0 && isset($tems_filtered[$term->term_id]['child_terms']) && count($tems_filtered[$term->term_id]['child_terms']) > 0){
                        $list_attributs->add_render_attribute('list-item'.$term->term_id, [
                            'class' => 'lgefep-filter-bar-list-item-parent',
                        ]);
                    }
                    ?>
                    <<?php echo esc_html($this->print_list_tag($this->get_list_tag('item', $lgefep_taxonomy_dropdown_style))); ?> <?php $this->widget_data->print_render_attribute_string('list-item'.$term->term_id); ?>><?php echo esc_html( $term->name ); ?><?php echo esc_html( $count ); ?></<?php echo esc_html($this->print_list_tag($this->get_list_tag('item', $lgefep_taxonomy_dropdown_style))); ?>>
                <?php } 
                $list_items = ob_get_clean();
                $aria_pressed_value = ( 0 === $active_terms ) ? 'true' : 'false';

                $list_attributs=$this->widget_data->add_render_attribute('list-item-all', [
                    'class' => 'lgefep-filter-bar-list-item e-filter-item',
                    'data-filter' => esc_attr('__all'),
                    'aria-pressed' => esc_attr($aria_pressed_value),
                ]);

                if('true' === $aria_pressed_value){
                    $list_attributs->add_render_attribute('list-item-all', [
                        'selected' => 'selected',
                    ]);
                }
                
                $first_item_title='yes' === $widget_settings['show_first_item'] && !empty($widget_settings['first_item_title']) ? $widget_settings['first_item_title'] : '';
               ?>

                <<?php echo esc_html($this->print_list_tag($this->get_list_tag('wrapper', $lgefep_taxonomy_dropdown_style))); ?> <?php $this->widget_data->print_render_attribute_string('filter-list'); ?>>
                <?php if ( !empty($first_item_title) ) : ?>
                <<?php echo esc_html($this->print_list_tag($this->get_list_tag('item', $lgefep_taxonomy_dropdown_style))); ?> <?php $this->widget_data->print_render_attribute_string('list-item-all'); ?>><?php echo esc_html($first_item_title); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></<?php echo esc_html($this->print_list_tag($this->get_list_tag('item', $lgefep_taxonomy_dropdown_style))); ?>>
                <?php endif; ?><?php echo $list_items; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                </<?php echo esc_html($this->print_list_tag($this->get_list_tag('wrapper', $lgefep_taxonomy_dropdown_style))); ?>>
            </div>
        </div>
		<?php
        $widget_content = ob_get_clean();
        
        return $widget_content;
        }

        private function filterd_terms_by_id($terms, $selected_taxonomy, $exclude_terms){
            $terms_filtered = [];

            foreach($terms as $term){
                if(isset($terms_filtered[$term->term_id])){
                    continue;
                }

                $this->update_filtered_terms($terms_filtered, $term, $selected_taxonomy, $exclude_terms);
            }

            return $terms_filtered;
        }

        private function update_filtered_terms(&$filtered_terms, $term, $selected_taxonomy, $exclude_terms){

            if(isset($filtered_terms[$term->term_id]) || in_array($term->slug, $exclude_terms)){
                return;
            }
        
            
            $get_terms_by_id = get_term_by('id', $term->term_id, $selected_taxonomy);
            
            $parent_term=get_term_by('id', $get_terms_by_id->parent, $selected_taxonomy);
            $parent_term_slug=isset($parent_term->slug) ? $parent_term->slug : false;
            $parent_term_id=isset($parent_term->term_id) ? $parent_term->term_id : false;
        
            // Check if any ancestor is excluded - if so, don't add this term
            if($this->is_ancestor_excluded($term, $selected_taxonomy, $exclude_terms)){
                return;
            }
        
            if(!$parent_term_id){
                $filtered_terms[$term->term_id]=[];
            }
        
            if($parent_term_id > 0 && !in_array($parent_term_slug, $exclude_terms)){
                $filtered_terms[$term->term_id]=[];
                $filtered_terms[$term->term_id]['parent_terms']=$parent_term_slug;
            }
        
            if(!isset($filtered_terms[$term->term_id])){
                return;
            }
        
            if(!in_array($parent_term_slug, $exclude_terms) && $parent_term_id > 0){
                $filtered_terms[$term->term_id]['parent_terms']=$parent_term_slug;
            }
        
            if(!isset($filtered_terms[$parent_term_id]) && $parent_term_id > 0 && !in_array($parent_term_slug, $exclude_terms)){
                $this->update_filtered_terms($filtered_terms, $parent_term, $selected_taxonomy, $exclude_terms);
            }
        
            $child_terms = get_term_children($term->term_id, $selected_taxonomy);
            foreach($child_terms as $child_term){
                $child_term_data=get_term_by('id', $child_term, $selected_taxonomy);
        
                // Skip if child term is excluded or if any of its ancestors are excluded
                if(in_array($child_term_data->slug, $exclude_terms) || $this->is_ancestor_excluded($child_term_data, $selected_taxonomy, $exclude_terms)){
                    continue;
                }
        
                if(isset($filtered_terms[$term->term_id]['child_terms']) && !in_array($child_term, $filtered_terms[$term->term_id]['child_terms'])){
                    array_push($filtered_terms[$term->term_id]['child_terms'], $child_term);
                }else{
                    $filtered_terms[$term->term_id]['child_terms'] = [$child_term];
                }
        
                // Always recursively process child terms to handle nested hierarchies
                if(!isset($filtered_terms[$child_term_data->term_id])){
                    $this->update_filtered_terms($filtered_terms, $child_term_data, $selected_taxonomy, $exclude_terms);
                }
            }
        }
        
        /**
         * Get all terms that should be excluded due to hierarchical relationships
         */
        private function get_hierarchical_excludes($terms, $selected_taxonomy, $exclude_terms) {
            $hierarchical_excludes = [];
            
            if (empty($terms) || empty($exclude_terms)) {
                return $hierarchical_excludes;
            }
            
            foreach ($terms as $term) {
                // If this term itself is excluded, add it
                if (in_array($term->slug, $exclude_terms)) {
                    $hierarchical_excludes[] = $term->slug;
                }
                // If any ancestor is excluded, add this term too
                else if ($this->is_ancestor_excluded($term, $selected_taxonomy, $exclude_terms)) {
                    $hierarchical_excludes[] = $term->slug;
                }
            }
            
            return array_unique($hierarchical_excludes);
        }

        private function is_ancestor_excluded($term, $selected_taxonomy, $exclude_terms){
            $current_term = $term;
            
            while($current_term && $current_term->parent > 0){
                $parent_term = get_term_by('id', $current_term->parent, $selected_taxonomy);
                if($parent_term && in_array($parent_term->slug, $exclude_terms)){
                    return true;
                }
                $current_term = $parent_term;
            }
            
            return false;
        }
    }
}