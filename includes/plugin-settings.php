<?php

if (!defined('ABSPATH')) exit;

/**
 * This class handles the settings page for plugin configuration and SDK/API parameters.
 */
class PluginSettings extends WC_Settings_Page
{
    /**
     * Register hooks
     */
    public function __construct()
    {
        $this->id = 'checkout_settings';
        $this->label = 'Fiserv Plugin';

        add_action('woocommerce_settings_' . $this->id, [$this, 'output']);
        add_action('woocommerce_settings_save_' . $this->id, [$this, 'save']);

        parent::__construct();
    }

    /**
     * Output to WC admin settings instance
     */
    public function output()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::output_fields($settings);
    }


    /**
     * On save button press, parameters become available via Wordpress hook
     * 
     * @see get_option
     */
    public function save()
    {
        $settings = $this->get_settings();
        WC_Admin_Settings::save_fields($settings);
    }


    /**
     * Initialize setting fields
     */
    public function get_settings()
    {
        $settings = [
            array('title' => 'Fiserv Plugin Settings', 'type' => 'title', 'desc' => 'Configure Checkout Solution settings. Retrieve the API key and secret from the developer portal.', 'id' => 'api_options'),
            self::render_textfield('API Key', '7V26q9EbRO2hCmpWARdFtOyrJ0A4cHEP', 'password'),
            self::render_textfield('API Secret', 'KCFGSj3JHY8CLOLzszFGHmlYQ1qI9OSqNEOUj24xTa0', 'password'),
            self::render_textfield('Store ID', '72305408'),
            self::render_textfield('Button Content', 'AB-1234'),
            self::render_textfield('Preferred Methods', 'Credit Card'),
            array('type' => 'sectionend', 'id' => 'api_options'),
        ];

        return $settings;
    }

    /**
     * Render a text field into settings page.
     * 
     * @param string $title Option title
     * @param string $default Default value
     * @param string $type 'text' for plain text and 'password' for password string
     * @param bool $required If true, field has to have some value
     * @return array List containing text field data
     */
    private function render_textfield(string $title, string $default = '', string $type = 'text', bool $required = false): array
    {
        return [
            'title' => $title,
            'id' => self::strp($title) . '_id',
            'css' => 'min-width:300px;',
            'default' => $default,
            'type' => $type,
            'custom_attributes' => ['required' => 'required']
        ];
    }

    /**
     * Utility method to create ID string from given text field title.
     * Example: 'API Key' -> 'api_key';
     * 
     * @param string $str String to strip
     * @return string Resulting string that is transformed
     */
    private static function strp(string $str): string
    {
        $str = str_replace(' ', '_', $str);
        return strtolower($str);
    }
}
