<?php

abstract class WC_Fisrv_Payment_Settings extends WC_Payment_Gateway
{
    protected string $default_title = '';

    protected string $default_description = '';


    public static function custom_save_icon_value($settings)
    {
        return $settings;
    }

    /**
     * 
     * @param string $field_html The markup of the field being generated (initiated as an empty string).
     * @param string $key The key of the field.
     * @param array  $data The attributes of the field as an associative array.
     * @param WC_Settings_API $wc_settings The current WC_Settings_API object.
     */
    public static function custom_icon_settings_field(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
    {
        $html_identifier = "woocommerce_{$wc_settings->id}_{$key}";
        // $variable_icon = esc_attr($wc_settings->get_option('icon', $this->get_default_icon()));

        $field_html = '<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="' . $html_identifier . '">' . $data['title'] . ' <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span></label>
			</th>
			<td class="forminp">
				<fieldset style="display: flex; flex-direction: row;">
					<legend class="screen-reader-text"><span>Gateway Name</span></legend>
					' . self::render_gateway_icons(false, $wc_settings->id, 'display: flex; flex-direction: row;', $height = '4rem') . '
					<input class="input-text regular-input" type="text" name="fs-icons-data" id="fs-icons-data" value="' . implode(',', json_decode('[]', true)) . '" placeholder="Enter image URL to add to list">
					<div class="fs-add-button button-primary" gatewayid="' . $wc_settings->id . '" id="fs-icon-btn" onclick="addImage()">+</div>
				</fieldset>
			</td>
		</tr>';

        return $field_html;
    }

    public static function custom_restore_settings_field(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        return '
            <div class="button-primary" onclick="fisrvRestorePaymentSettings(\'' . $wc_settings->id . '\', this)">Restore default settings</div>
        ';
    }

    protected static function render_gateway_icons(bool $display, string $gateway_id, string $styles = '', string $height = '2rem')
    {
        $icon_html = '';
        $icon_html = '<div style="' . $styles . '">';
        $gateway = WC()->payment_gateways()->payment_gateways()['fisrv-gateway-generic'];

        switch ($gateway_id) {
            case 'fisrv-gateway-generic':
                $icons = json_decode($gateway->get_option('custom_icon'), true);

                if (is_null($icons) || count($icons) === 0) {
                    $icons = ['https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg'];
                }

                foreach ($icons as $index => $icon) {
                    $icon_html .= self::render_single_img($display, $height, $icon, $index);
                }

                break;

            case 'fisrv-apple-pay':
                $image_src = 'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/applepay.svg';
                $icon_html .= self::render_single_img(false, $height, $image_src);
                break;

            case 'fisrv-google-pay':
                $image_src = 'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/googlepay.svg';
                $icon_html .= self::render_single_img(false, $height, $image_src);
                break;

            case 'fisrv-credit-card':
                $image_src = 'https://icon-library.com/images/credit-card-icon-white/credit-card-icon-white-9.jpg';
                $icon_html .= self::render_single_img(false, $height, plugins_url('../assets/images/fisrv-credit-card.svg', __FILE__));
                break;

            default:
                break;
        }

        $icon_html .= '</div>';
        return $icon_html;
    }

    private static function render_single_img(bool $display, string $height, string $image_src, int $index = 0): string
    {
        if ($display) {
            return '
                <img style="border-radius: 10%; width: 3em;" margin-right: 5px" src="' . WC_HTTPS::force_https_url($image_src) . '" alt="' . esc_attr('Fisrv gateway icon') . '" />
            ';
        }

        return '
        <div gateway-id="fisrv-gateway-generic" id="fs-icon-container-' . $index . '" class="fs-icon-container" onclick="removeImage(' . $index . ', this)">
            <div id="fs-icon-overlay-' . $index . '" class="fs-icon-overlay">🞭 Remove Icon</div>
            <img style="border-radius: 10%; margin-right: 5px; height: ' . $height . '" src="' . WC_HTTPS::force_https_url($image_src) . '" alt="' . esc_attr('Fisrv gateway icon') . '" />
        </div>';
    }


    public static function healthcheck_settings_field(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
    {
        $html_identifier = "woocommerce_{$wc_settings->id}_{$key}";

        $field_html = '<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="' . $html_identifier . '">' . $data['title'] . ' <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span></label>
			</th>
			<td class="forminp">
				<fieldset style="display: flex; flex-direction: row; align-items: center;">
					<legend class="screen-reader-text"><span>Gateway Name</span></legend>
					<div id="fs-health-btn" class="fs-add-button button-primary" onclick="fetchHealth()">+</div>
					<div style="display: flex; flex-direction: row; margin-left: 1rem; align-items: center;">
						<div id="fs-status-indicator" style="background-color: lightblue; border-radius: 100%; width: 0.8em; height: 0.8em; margin-right: 1em;"></div>
						<div id="fs-status-text">Check status</div>
					</div>
				</fieldset>
			</td>
		</tr>';
        return $field_html;
    }

    private function render_text_field(string $value = '', string $key, WC_Settings_API $wc_settings)
    {
        $html_identifier = "woocommerce_{$wc_settings->id}_{$key}";
        return '<input class="input-text regular-input " type="text" 
					name="' . $html_identifier . '" id="' . $html_identifier . '" style="" value="' . $value . '" placeholder="">';
    }

    /**
     * Initialize form text fields on gateway options page
     */
    public function init_form_fields(): void
    {
        if ($this->id === 'fisrv-gateway-generic') {
            $this->form_fields = [
                'api_key' => array(
                    'title' => 'API Key',
                    'type' => 'text',
                    'description' => esc_html__('Acquire API Key from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'api_secret' => array(
                    'title' => 'API Secret',
                    'type' => 'password',
                    'description' => esc_html__('Acquire API Secret from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'store_id' => array(
                    'title' => 'Store ID',
                    'type' => 'text',
                    'description' => esc_html__('Your Store ID for Checkout', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'is_prod' => array(
                    'title' => 'Production Mode',
                    'type' => 'checkbox',
                    'description' => esc_html__('Use Live (Production) Mode or Test (Sandbox) Mode', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'healthcheck' => array(
                    'title' => 'API Health',
                    'type' => 'healthcheck',
                    'description' => esc_html__('Get current status of Fiserv API and your configuration', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'autocomplete' => array(
                    'title' => 'Auto-complete Orders',
                    'type' => 'checkbox',
                    'description' => esc_html__('Skip processing order status and set to complete status directly', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                // 'enable_tokens' => array(
                // 	'title' => 'Enable Transaction Tokens',
                // 	'type' => 'checkbox',
                // 	'description' => esc_html__('If enabled, a successful payment of a user will be tokenized and can be optionally used on sub-sequent payment', 'fisrv-checkout-for-woocommerce'),
                // 	'desc_tip' => true,
                // ),
                'transaction_type' => array(
                    'title' => 'Transaction Type',
                    'type' => 'select',
                    'description' => esc_html__('Set transaction type. Currently, only SALE transactions are available.', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'sale',
                    'options' => array(
                        'SALE' => 'Sale',
                    ),
                ),
                'enable_log' => array(
                    'title' => 'Enable Developer Logs',
                    'type' => 'checkbox',
                    'description' => esc_html__('Enable log messages on WooCommerce', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
            ];
        }


        $this->form_fields += array(
            'icons' => array(
                'title' => 'Gateway Icon',
                'description' => esc_html__('Link of image asset', 'fisrv-checkout-for-woocommerce'),
                'type' => 'custom_icon',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => 'Gateway Name',
                'type' => 'text',
                'description' => esc_html__('Custom name of gateway', 'fisrv-checkout-for-woocommerce'),
                'default' => $this->default_title,
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Gateway Description',
                'type' => 'text',
                'description' => esc_html__('Custom description of gateway', 'fisrv-checkout-for-woocommerce'),
                'default' => 'Payment will be processed by Fiserv. You will be redirected to an external checkout page.',
                'desc_tip' => true,
            ),
            'fail_page' => array(
                'title' => 'Redirect after payment failure',
                'type' => 'select',
                'description' => esc_html__('Where to redirect if payment failed', 'fisrv-checkout-for-woocommerce'),
                'default' => '',
                'desc_tip' => true,
                'options' => array(
                    'checkout' => 'Checkout page',
                    'cart' => 'Shopping cart',
                    'home' => 'Home page'
                ),
            ),
            'restore' => array(
                'title' => 'Restore Settings',
                'type' => 'restore_settings',
                'description' => esc_html__('Restore all settings to default state', 'fisrv-checkout-for-woocommerce'),
                'desc_tip' => true,
            ),
        );
    }
}