jQuery(document).ready(function ($) {
    if (typeof elementor !== 'undefined' && elementor.hooks) {

        elementor.hooks.addAction('panel/open_editor/widget/taxonomy-filter', function () {

            // Retry until Elementor finishes rendering the control panel
            const interval = setInterval(function () {
                const select = $('select[data-setting="lgefep_taxonomy_dropdown_style"]');

                if (select.length) {
                    const checkboxOption = select.find('option[value="checkbox"]');

                    if (checkboxOption.length) {
                        checkboxOption.prop('disabled', true).text('Checkbox (PRO)');

                        clearInterval(interval);
                    }
                }
            }, 200);
        });

        // Handle dynamic data updates when settings change
        elementor.hooks.addAction('panel/open_editor/widget/taxonomy-filter', function (panel, model, view) {
            const updateDataDynamically = function() {
                // Get current widget settings
                const settings = model.get('settings').toJSON();
                
                // Only update if relevant settings changed
                const relevantSettings = {
                    lgefep_taxonomy_dropdown: settings.lgefep_taxonomy_dropdown || 'no',
                    lgefep_taxonomy_dropdown_style: settings.lgefep_taxonomy_dropdown_style || 'default',
                    lgefep_taxonomy_show_count: settings.lgefep_taxonomy_show_count || 'no',
                    lgefep_taxonomy_exclude_terms: settings.lgefep_taxonomy_exclude_terms || '',
                    lgefep_taxonomy_include_terms: settings.lgefep_taxonomy_include_terms || '',
                    selected_element: settings.selected_element || '',
                    taxonomy: settings.taxonomy || ''
                };


                // Send AJAX request to update data
                if (typeof lgefep_editor_ajax !== 'undefined') {
                    $.ajax({
                        url: lgefep_editor_ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: lgefep_editor_ajax.action,
                            nonce: lgefep_editor_ajax.nonce,
                            settings: relevantSettings
                        },
                        success: function(response) {
                            if (response.success) {
                                
                                // Update global data object if it exists
                                if (typeof lgefep_taxonomy_addon_data !== 'undefined') {
                                    lgefep_taxonomy_addon_data = response.data.data;
                                }
                                
                                // Trigger a custom event for other scripts to listen to
                                $(document).trigger('lgefep_data_updated', [response.data.data]);
                            }
                        },
                        error: function(xhr, status, error) {
                        }
                    });
                }
            };

            // Listen for setting changes
            model.on('change', function(model) {
                const changedAttributes = model.changedAttributes();
                
                // Check if any relevant settings changed
                const relevantKeys = [
                    'lgefep_taxonomy_dropdown',
                    'lgefep_taxonomy_dropdown_style', 
                    'lgefep_taxonomy_show_count',
                    'lgefep_taxonomy_exclude_terms',
                    'lgefep_taxonomy_include_terms'
                ];
                
                const hasRelevantChanges = relevantKeys.some(key => 
                    changedAttributes && changedAttributes.hasOwnProperty(key)
                );
                
                if (hasRelevantChanges) {
                    clearTimeout(updateDataDynamically.timeout);
                    updateDataDynamically.timeout = setTimeout(updateDataDynamically, 500);
                }
            });
        });
    }
      // Elementor Review Notice Start
      jQuery(document).on('click','#lgefep_elementor_review_dismiss',(event)=>{
        jQuery(".lgefep_elementor_review_notice").hide();
        const btn=jQuery(event.target);
        const nonce=btn.data('nonce');
        const url=btn.data('url');

        jQuery.ajax({
            type: 'POST',
            // eslint-disable-next-line no-undef
            url: url, // Set this using wp_localize_script
            data: {
                action: 'lgefep_elementor_review_notice',
                lgefep_notice_dismiss: true,
                nonce: nonce
            },
            success: (response) => {
                btn.closest('.elementor-control').remove();
            },
            error: (xhr, status, error) => {
                console.log(xhr.responseText);
                console.log(error);
                console.log(status);
            }
        });
    });
    // Elementor Review Notice End
});
