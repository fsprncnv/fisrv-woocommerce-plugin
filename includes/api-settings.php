<?php

namespace FiservWoocommercePlugin;


class ApiSettings
{
    public function __construct()
    {
        add_filter('woocommerce_get_sections_shipping', [$this, 'tester_add_section']);
        add_filter('woocommerce_get_settings_shipping', [$this, 'tester_all_settings'], 10, 2);
    }


    function tester_add_section($sections)
    {
        $sections['tester'] = __('TEST MENU', 'text-domain');

        return $sections;
    }


    function tester_all_settings($settings, $current_section)
    {
        if ($current_section == 'tester') {

            $settings_slider = array();

            // Add Title to the Settings

            $settings_slider[] = [
                'name' => __('WC Slider Settings', 'text-domain'),
                'type' => 'title',
                'desc' => __('The following options are used to configure WC Slider', 'text-domain'),
                'id' => 'wcslider'
            ];

            // Add first checkbox option

            $settings_slider[] = [
                'name' => __('Auto-insert into single product page', 'text-domain'),
                'desc_tip' => __('This will automatically insert your slider into the single product page', 'text-domain'),
                'id' => 'wcslider_auto_insert',
                'type' => 'checkbox',
                'css' => 'min-width:300px;',
                'desc' => __('Enable Auto-Insert', 'text-domain'),
            ];

            $settings_slider[] = [
                'name' => __('Slider Title', 'text-domain'),
                'desc_tip' => __('This will add a title to your slider', 'text-domain'),
                'id' => 'wcslider_title',
                'type' => 'text',
                'desc' => __('Any title you want can be added to your slider with this option!', 'text-domain'),
            ];

            $settings_slider[] = array('type' => 'sectionend', 'id' => 'wcslider');
            return $settings_slider;
        }

        return $settings;
    }
}
