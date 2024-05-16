<?php

if (!defined('ABSPATH')) exit;

class PluginSettings extends WC_Settings_Page
{
    /**
     * Register hooks
     */
    public function __construct()
    {
        $this->id = 'checkout_settings';
        $this->label = 'Checkout Solution';

        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'));
        add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
        add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));
        parent::__construct();
    }


    public function output()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields($settings);
    }


    public function save()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
    }


    public function get_settings()
    {
        $settings = [
            array('title' => 'Fiserv Plugin Settings', 'type' => 'title', 'desc' => 'Configure Checkout Solution settings. Retrieve the API key and secret from the developer portal.', 'id' => 'api_options'),
            self::render_textfield('API Key', '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP', 'password'),
            self::render_textfield('API Secret', '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP', 'password'),
            self::render_textfield('Store ID', '72305408'),
            self::render_textfield('Merchant Transaction ID', 'AB-1234'),
            self::render_textfield('Preferred Methods', 'Credit Card'),
            array('type' => 'sectionend', 'id' => 'api_options'),
        ];

        return $settings;
    }

    private function render_textfield(string $title, string $default = '', string $type = 'text', bool $required = false): array
    {
        return [
            'title' => $title,
            'id' => self::strp($title) . '_id',
            'css' => 'min-width:300px;',
            'default' => $default,
            'type' => $type,
            'custom_attributes' => array('required' => 'required')
        ];
    }

    private static function strp(string $str): string
    {
        $str = str_replace(' ', '', $str);
        return strtolower($str);
    }
}
