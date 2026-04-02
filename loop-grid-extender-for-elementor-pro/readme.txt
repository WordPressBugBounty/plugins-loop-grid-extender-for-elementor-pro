=== Loop Grid Extender for Elementor - ACF Repeater & Smart Filters ===
Contributors: coolplugins, narinder-singh, satindersingh  
Tags: loop grid, taxonomy filter, dropdown filter, Elementor, ACF repeater fields
Requires at least: 6.5  
Tested up to: 6.9.4
Requires PHP: 7.4  
Stable tag: 1.1.7
License: GPLv2 or later  
License URI: https://www.gnu.org/licenses/gpl-2.0.html  

Use ACF repeater fields inside Elementor loop items and add smart dynamic dropdown taxonomy filters to the Elementor Loop Grid widget.

== Description ==

**Loop Grid Extender for Elementor Pro** helps you add powerful filter options to Elementor's **Loop Grid widget**. By default, Loop Grid doesn't let users filter posts or products easily, that's where this addon comes in.

== Important ==
This plugin does not currently support Elementor's Widget Cache feature. Please disable it to ensure proper functionality.

https://www.youtube.com/watch?v=kbuOELxj5sk

It is built specifically for people using **Elementor Pro + ACF** (Advanced Custom  Fields), allowing to add **dropdown filters**, show how many posts or products are in each **category**, and even **hide terms** you don't want to show. 

https://www.youtube.com/watch?v=TBGzbJdLXWs

Think of it as a missing piece that gives **Elementor Pro** superpowers when working with repeater fields and dynamic layouts.

 [**View Demo**](https://coolplugins.net/loop-grid-extender-for-elementor-pro/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=demo&utm_content=check_demo)


###  KEY FEATURES

* **ACF Repeater Field Support with Elementor Pro**: Directly use ACF Pro's Repeater fields inside Elementor Pro's dynamic widgets through the Loop Grid widget. It means you can display complex, repeated content without writing any code.

* **Dropdown Display for Filters:** You can easily filter posts or products by categories, tags, or custom taxonomies using dropdown menus.

* **Post/Product Count Display:** Display the number of posts or products available next to each filter option, helping visitors quickly see how many items are in each category before they choose to filter.

* **Exclude Specific Taxonomies or Terms:** Exclude unwanted taxonomy terms like "Uncategorized" or any other custom terms from the filter list.

* **Custom Post Type & ACF Taxonomy Support:** The plugin fully supports custom post types and taxonomies created with ACF, ensuring that all your unique content types work with the filtering system.

* **Multilingual + Multi-taxonomy (Pro)**: Build complex, language-aware filters across categories, tags, and custom taxonomies.

* **Responsive, Optimized, Code-free**: Lightweight scripts, clean markup, and mobile-first design ensure performance.

### USE CASES

- Building **FAQ or testimonial sections** with toggles and pop-ups using ACF repeater data.
- Creating **WooCommerce product grids** with filters for size, color, and brand.
- Crafting **team member directories**, **event listings**, or **portfolio galleries** with dynamic filter UI.
- **Multilingual websites** requiring term-specific filtering.

##  From Frustration to Solution
Elementor users have been requesting **native support for ACF Repeater fields in Loop Grids, toggle/accordion widgets, and dynamic pop-ups** for years. For example, issue **[#5979](https://github.com/elementor/elementor/issues/5979)** on GitHub was opened 6+ years ago, stated:

> *“I’m always frustrated when building a dynamic FAQ section using a custom post type with ACF fields and Dynamic Pop‑up. Elementor Pro doesn’t support a repeater functionality yet.”*

**[#5979](https://github.com/elementor/elementor/issues/5979)**
**[#23185](https://github.com/orgs/elementor/discussions/23185)**

Despite some advances, Elementor still lacks an intuitive way to:

- Populate toggles, pop-ups, or loop templates with ACF repeater data, without custom PHP.
- Add flexible filter UI (dropdowns or checkboxes) with taxonomy and post-count controls.

Our plugin **directly addresses these gaps**.

### Explore More Cool Addons For Elementor 

* **[Cool FormKit For Elementor](https://coolplugins.net/cool-formkit-for-elementor-forms/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=get_pro&utm_content=cfkef_more_plugins)**: Add advanced fields like conditional logic, range sliders, calculator fields, and country code selection to Elementor forms.

* **[Conditional Fields For Elementor Form](https://coolplugins.net/product/conditional-fields-for-elementor-form/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=get_pro&utm_content=cfef_more_plugins)**: An essential addon for Elementor forms that allows you to add conditional logic to input fields, enabling fields to show/hide based user input.

* **[Country Code For Elementor Form Telephone Field](https://coolplugins.net/add-country-code-telephone-elementor-form/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=blog&utm_content=ccfef_more_plugins)**: Enhances phone fields with a country code selection feature for accurate data input.

* **[Timeline Widget for Elementor](https://cooltimeline.com/plugin/timeline-widget-pro/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=get_pro&utm_content=twae_more_plugins)**: Use this plugin to showcase your history in a stylish vertical or horizontal timeline layout on Elementor pages.

* **[Events Widgets for Elementor](https://eventscalendaraddons.com/plugin/events-widgets-pro/?utm_source=lge_plugin&utm_medium=readme&utm_campaign=get_pro&utm_content=ectbe_more_plugins)**: This plugin provides The Events Calendar widgets for Elementor, allowing you to easily display events in a grid, list, or carousel layout.

**Use of 3rd Party Services:** This plugin connects to the Cool Plugins feedback server only for optional usage data sharing and voluntary feedback submission (for example, during plugin deactivation). Data is transmitted solely after explicit user consent. No hidden tracking is performed, and no frontend visitor or site user data is collected.  For more details, please review our [Data Usage Policy](https://my.coolplugins.net/terms/usage-tracking/), [TOS](https://my.coolplugins.net/terms/), and [Privacy Policy](https://my.coolplugins.net/terms/privacy-policy/).

== Installation == 
1. Upload plugin to `/wp-content/plugins/` or install via WP directory  
2. Activate it  
3. In Elementor, drag the **Loop Grid Extender** widget  
4. For Repeater support: 
   - Ensure both **ACF Pro** and **Elementor Pro** are active  
   - Create a Repeater field and sub-fields under **Custom Fields**  inside ACF.
  
5. Add a Loop Grid to your page, select your Loop Item, enable ACF Repeater, and choose your field  
6. Configure dropdown or checkbox filters, exclude terms, enable multilingual or counts, and publish

== FAQ ==

= Does this plugin work with Elementor Free? =
No. This plugin extends the **Elementor Pro** Loop Grid and requires both **Elementor Pro** and optionally **ACF Pro** for Repeater field support.

= Is ACF Pro required to use the repeater features? =
Yes. Repeater fields are a **Pro-only feature in ACF**, so you must have **ACF Pro** installed and active to use repeater integration.

= Can I use this with Flexible Content fields from ACF? =
Not yet. Support is currently limited to ACF Repeater fields. **Flexible Content support is on our roadmap** for future releases.

= Will this slow down my page? =
No. The plugin is optimized for performance. It loads minimal scripts and clean HTML, and filtering is handled efficiently using Elementor’s native AJAX.

= Can I style the filters (dropdowns/checkboxes)? =
Yes. The filters inherit your site’s theme styles. You can further customize them using Elementor's widget settings or custom CSS.

= What happens if I deactivate ACF Pro? =
ACF-related fields will no longer render correctly. To use repeater functionality, ACF Pro must be active.

= Can I show images or videos inside ACF Repeater fields? =
Yes. You can display image fields as inline content or even use them in background sections. Video fields may require additional customization.

= How do I control which taxonomy terms show up in the filter? =
Use the plugin’s **“Exclude Terms”** option in the widget settings to hide specific terms or show only relevant ones.

= Does this plugin support nested repeaters? =
Currently, **nested repeater fields are not supported.** Support is planned based on demand and feasibility.

= Is this plugin compatible with FSE (Full Site Editing)? =
This plugin is specifically built for **Elementor Pro**'s Loop Grid and is not tested with Gutenberg Full Site Editing.

=  How can I report security bugs? = 
You can report security bugs through the Patchstack Vulnerability Disclosure Program. The Patchstack team help validate, triage and handle any security vulnerabilities. [Report a security vulnerability](https://patchstack.com/database/wordpress/plugin/loop-grid-extender-for-elementor-pro/vdp)

==  Screenshots == 

1. Taxonomy Filters with post counts preview 
2. ACF Repeater Field usage inside Elementor LoopGrid  
3. ACF Repeater Field Setup/configuration preview 

==  Changelog == 

= Version 1.1.7 | 02/04/2026 =
* **Tested upto:** Elementor version 4.0.0 & Elementor Pro version 4.0.0. 

= Version 1.1.6 | 26/02/2026 =
* **Fixed:** Issues reported by “Plugin Check” plugin. 

= Version 1.1.5 | 09/12/2025 =
* **Tested Upto:** Wordpress 6.9.

= Version 1.1.4 | 29/08/2025 =
* **Added:** Support for ACF WYSISYG field.
* **Added:** Support for file field.
* **Fixed:** Security Issues.

= Version 1.1.3 | 29/07/2025 =
* **Added:** ACF email and ACF number field Support.
* **Added:** Include and Exclude functionality for default setting of the taxonomy filter.
* **Improved:** Handling of ACF URL field.

= Version 1.1.2 | 11/07/2025 =
* **Added:** ACF URL field Support.

= Version 1.1.1 | 02/07/2025 =
* **Added:** Support for sorting within taxonomy filters for improved user experience.
* **Fixed:** An issue where repeater field data beyond the 10th subfield was not being displayed in the loop grid.

= Version 1.1.0 | 26/06/2025 =

* Fixed:- Issues with ACF Pro Repeater field
* Added:- Deactivation feedback and review notice
* Tweaks:- Added YouTube video link and improved readme.txt content.

= Version 1.0.2 =  
* Updated dependencies and plugin header metadata

= Version 1.0.1 =  
* Added required plugin checks

= Version 1.0.0 =  
* Initial release - ACF Repeater, dropdown filters, term counts, and exclusions

== Upgrade Notice == 
Initial release 