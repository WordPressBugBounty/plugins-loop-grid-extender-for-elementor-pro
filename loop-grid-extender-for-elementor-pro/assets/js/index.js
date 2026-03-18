class LGEFEP_Addon extends elementorModules.frontend.handlers.Base {
    getDefaultSettings() {
        return {
            selectors: {
                wrapper: '.lgefep-filter-bar-wrapper',
                listWrapper: '.lgefep-filter-bar-list',
            },
        };
    }

    getDefaultElements() {
        const { wrapper, listWrapper } = this.getSettings('selectors');

        return {
            $wrapper: this.$element.find(wrapper),
            $listWrapper: this.$element.find(listWrapper),
        };
    }

    bindEvents() {
        this.handleDefaultPostCount();
        if (this.elements.$wrapper.data('lgefep-style') === 'dropdown') {
            this.select2Init();
        }
    }

    handleDefaultPostCount() {
        // Get widget-specific data from the data container
        const dataContainer = this.$element.find('.lgefep-data-container');
        if (!dataContainer.length) {
            return;
        }
        
        const configData = dataContainer.data('lgefep-config');
        if (!configData) {
            return;
        }
        
        const settings = configData.settings || {};
        const terms = configData.terms || [];
        const includeTerms = settings.include_terms || [];
        
        // Apply include/exclude filtering first
        const hierarchicalExcludes = settings.hierarchical_exclude_terms || [];
        this.applyIncludeExcludeFiltering(includeTerms, hierarchicalExcludes);
        
        // Reorder DOM elements to match the sorted terms array
        this.reorderFilterElements(terms);
    
        if (settings.show_count === 'yes') {
    
            // Add count next to each individual term button
            terms.forEach(term => {
    
                const $button = this.$element.find(`.e-filter-item[data-filter="${term.slug}"]`)[0];
    
                if ($button && !$button.dataset.countAdded) {
                    $button.textContent = `${$button.textContent.trim()} (${term.count})`;
                    $button.dataset.countAdded = 'true';
                }
            });
    
            // Calculate total count for visible parent terms only
            let visibleParentCount = configData.total_count;
            
            // Subtract count of excluded parent terms only
            terms.forEach(term => {
                // Only check parent terms (parent === 0)
                if (term.parent === 0 && hierarchicalExcludes.length > 0 && hierarchicalExcludes.includes(term.slug)) {
                    visibleParentCount -= parseInt(term.count) || 0;
                }
            });
            
            // Update the "All" button with adjusted parent count
            const allButton = this.$element.find('.e-filter-item[data-filter="__all"]')[0];
            if (allButton && !allButton.dataset.countAdded) {
                allButton.textContent = `${allButton.textContent.trim()} (${visibleParentCount})`;
                allButton.dataset.countAdded = 'true';
            }
        }
    }
    
    applyIncludeExcludeFiltering(includeTerms, hierarchicalExcludes = []) {
        // Find all filter items except "All" button
        const filterItems = this.$element.find('.e-filter-item:not([data-filter="__all"])');
        
        filterItems.each((index, element) => {
            const $element = jQuery(element);
            const dataFilter = $element.attr('data-filter');
            
            let shouldHide = false;
            
            // Apply hierarchical exclude filter (includes direct exclusions + children)
            if (hierarchicalExcludes.length > 0 && hierarchicalExcludes.includes(dataFilter)) {
                shouldHide = true;
            }
            
            // Apply include filter (only if include terms are specified)
            if (includeTerms.length > 0 && !includeTerms.includes(dataFilter)) {
                shouldHide = true;
            }
            
            if (shouldHide) {
                $element.remove();
            }
        });
    }
    
    reorderFilterElements(sortedTerms) {
        if (!sortedTerms || !sortedTerms.length) {
            return;
        }

        const $filterList = this.$element.find('.e-filter-item');
        if (!$filterList.length) {
            return;
        }

        const filterItems = Array.from($filterList);
        
        // Separate "All" item from other items
        const allItem = filterItems.find(item => item.getAttribute('data-filter') === '__all');
        const termItems = filterItems.filter(item => item.getAttribute('data-filter') !== '__all');
        
        // Create a map of slug to DOM element for quick lookup
        const termElementMap = {};
        termItems.forEach(item => {
            const slug = item.getAttribute('data-filter');
            if (slug) {
                termElementMap[slug] = item;
            }
        });
        
        // Create new array of elements in the sorted order
        const reorderedElements = [];
        
        // Add "All" item first if it exists
        if (allItem) {
            reorderedElements.push(allItem);
        }
        
        // Add term elements in the order they appear in sortedTerms
        sortedTerms.forEach(term => {
            const element = termElementMap[term.slug];
            if (element) {
                reorderedElements.push(element);
            }
        });
        
        // Add any remaining elements that weren't in the sorted terms (fallback)
        termItems.forEach(item => {
            const slug = item.getAttribute('data-filter');
            if (slug && !sortedTerms.find(term => term.slug === slug)) {
                reorderedElements.push(item);
            }
        });
        
        // Re-append elements in the new order
        const $parent = $filterList.first().parent();
        reorderedElements.forEach(item => {
            $parent.append(item);
        });
    }

    select2Init() {
        this.customrClassApplied = false;
        const multiSelect = this.elements.$wrapper.data('multi-select') === 'yes' ? true : false;

        this.elements.$listWrapper.select2({
            width: '100%',
            multiple: multiSelect,
            templateSelection: (data) => {
                const text = data.text.replace(/\([^)]*\)/g, '');
         
                return text;
            },
            templateResult: (data) => {
                setTimeout(() => {
                    const select2Item = document.querySelector(`li[data-select2-id="select2-data-${data._resultId}"]`);
            
                    if (select2Item && data.element) {
                        // Add parent class
                        if (data.element.classList.contains('lgefep-filter-bar-list-item-parent')) {
                            select2Item.classList.add('lgefep-filter-bar-list-item-parent');
                        }
            
                        // Add child class
                        if (data.element.classList.contains('lgefep-filter-bar-list-item-child')) {
                            select2Item.classList.add('lgefep-filter-bar-list-item-child');
                        }
            
                        // Add depth-specific classes for different levels
                        const depthClasses = data.element.className.match(/lgefep-filter-bar-list-item-child-level-\d+/g);
                        if (depthClasses) {
                            depthClasses.forEach(depthClass => {
                                select2Item.classList.add(depthClass);
                            });
                        }
            
                        // Also add the depth as a data attribute for easier targeting
                        const termDepth = data.element.getAttribute('data-term-depth');
                        if (termDepth) {
                            select2Item.setAttribute('data-term-depth', termDepth);
                        }
                    }
                });
            
                // Extract number from parentheses and return with span
                const text = data.text || '';
                const match = text.match(/^(.+?)\s*\((\d+)\)$/);
                
                if (match) {
                    const name = match[1];
                    const number = match[2];
                  
                    const nameDiv = document.createElement('div');
                    nameDiv.classList.add('lgefep-term-name');


                    nameDiv.textContent = name + ' ';


                    const numberSpan = document.createElement('span');
                    numberSpan.textContent = number;
                    numberSpan.classList.add('number-span');


                    nameDiv.appendChild(numberSpan);


                    const container = document.createElement('div');
                    container.appendChild(nameDiv);


                    return container.innerHTML;


                                
                 }
                
                return data.text;
            },
            
            escapeMarkup: function(markup) {
                return markup;
            },
            
            templateSelection: (data) => {
                // For selected items, return plain text without HTML
                const text = data.text || '';
                const match = text.match(/^(.+?)\s*\((\d+)\)$/);
                
                if (match) {
                    return match[1]; // Return just the name without parentheses
                }
                
                return data.text;
            }
        });

        this.elements.$listWrapper.on('select2:select', (event) => {
            const $listWrapper = this.elements.$listWrapper;
            const selectedElement = event.params.data.element;
            const selectedValue = event.params.data.id; // Will be option text if no 'value' attribute
        
            selectedElement.click();
        
            // Get all selected values
            let selectedValues = $listWrapper.val() || [];
        
            const allValue = "All"; // OR "__all" if you use value="__all"
        
            // CASE 1: If user selects "All", remove everything else
            if (selectedValue === allValue && selectedValues.length > 1) {
                $listWrapper.val([allValue]).trigger('change.select2');
            }
        
            // CASE 2: If user selects another category while "All" is selected, remove "All"
            else if (selectedValue !== allValue && selectedValues.includes(allValue)) {
                const newValues = selectedValues.filter(val => val !== allValue);
                $listWrapper.val(newValues).trigger('change.select2');
            }
        });

        
        
        this.elements.$listWrapper.on('select2:unselect', (event) => {
            if (event && event.params && event.params.data && event.params.data.element) {
                event.params.data.element.click();
            }
        });

        this.elements.$listWrapper.on('select2:open', (event) => {
            this.updateDropdownPosition();

            setTimeout(() => {
                const wrapperId = this.elements.$wrapper.data('lgefep-id');
                if (wrapperId && '' !== wrapperId) {
                    jQuery('.select2-container.select2-container--open:not(.select2-container--below)').addClass(`lgefep-taxonomy-filter-${wrapperId}`);
                }
            });
        });
    }

    updateDropdownPosition() {
        const htmlEle = document.querySelector('html');
        if (htmlEle) {
            const htmlTopSpacing = htmlEle.offsetTop;
            const select2Wrapper = document.querySelector('.select2-container.select2-container--open:not(.select2-container--below)');

            if (select2Wrapper && htmlTopSpacing > 0) {
                select2Wrapper.style.marginTop = htmlTopSpacing + 'px';
            }
        }
    }

    onDestroy() {
        this.elements.$listWrapper.select2('destroy');
    }
}



jQuery(window).on('elementor/frontend/init', () => {
    const addHandler = ($element) => {
        elementorFrontend.elementsHandler.addHandler(LGEFEP_Addon, {
            $element,
        });
    };

    elementorFrontend.hooks.addAction('frontend/element_ready/taxonomy-filter.default', addHandler);
});


