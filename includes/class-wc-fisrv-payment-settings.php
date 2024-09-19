<?php

abstract class WC_Fisrv_Payment_Settings extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->description = esc_html__('Payment will be processed by Fiserv. You will be redirected to an external checkout page.', 'fisrv-checkout-for-woocommerce');
    }

    public static function custom_save_icon_value($settings)
    {
        return $settings;
    }

    /**
     *
     * @param string          $field_html  The markup of the field being generated (initiated as an empty string).
     * @param string          $key         The key of the field.
     * @param array           $data        The attributes of the field as an associative array.
     * @param WC_Settings_API $wc_settings The current WC_Settings_API object.
     */
    public static function render_icons_component(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        ob_start();

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo "woocommerce_{$wc_settings->id}_{$key}" ?>"><?php echo $data['title'] ?>
                    <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span>
                </label>
            </th>
            <td class="forminp">
                <fieldset style="display: flex; flex-direction: row;">
                    <legend class="screen-reader-text"><span>Gateway Name</span></legend>
                    <?php echo self::render_gateway_icons($wc_settings->id, 'display: flex; flex-direction: row;', '4rem') ?>
                    <?php
                    if ($wc_settings->id === FisrvGateway::GENERIC->value) {
                        ?>
                        <input style="margin-left: 8px; margin-right: 8px; padding: 8px 10px; border: none;"
                            class="input-text regular-input" type="text" name="fs-icons-data" id="fs-icons-data"
                            value="<?php echo implode(',', json_decode('[]', true)) ?>"
                            placeholder="Enter image URL to add to list">
                        <div style="display: flex;" class="button-primary fs-add-button" gatewayid="<?php echo $wc_settings->id ?>"
                            id="fs-icon-btn" onclick="addImage()">+</div>
                        <?php
                    }
                    ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public static function render_restore_button(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        ob_start();

        ?>
        <div class="button-primary fs-add-button" onclick="fisrvRestorePaymentSettings('<?php echo $wc_settings->id ?>', this)">
            <?php echo esc_html__('Restore default settings', 'fisrv-checkout-for-woocommerce') ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function render_fisrv_header(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        ob_start();

        ?>
        <div class="fs-block">
            <img style="width: 12em;" src="https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg" />
            <div style="margin-top: 1em; margin-bottom: 1em;">
                <?php echo esc_html__(
                    'Pay securely with Fiserv Checkout. Acquire API credentials on our developer portal',
                    'fisrv-checkout-for-woocommerce'
                ) ?>.
            </div>
            <a style="text-decoration: none;" href="https://developer.fiserv.com"><?php echo esc_html__(
                'Visit developer.fiserv.com',
                'fisrv-checkout-for-woocommerce'
            ) ?></a>
        </div>
        <?php

        return ob_get_clean();
    }

    protected static function render_gateway_icons(string $gateway_id, string $styles = '', string $height = '2rem'): string
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[FisrvGateway::GENERIC->value];

        ob_start();
        ?>
        <div stlye="<?php echo $styles ?>">
            <?php
            switch ($gateway_id) {
                case FisrvGateway::GENERIC->value:
                    $icons = json_decode($gateway->get_option('custom_icon'), true);

                    if (is_null($icons) || count($icons) === 0) {
                        $icons = array('https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg');
                    }

                    foreach ($icons as $index => $icon) {
                        echo self::render_icon_with_overlay($height, $icon, $index);
                    }

                    break;

                case FisrvGateway::APPLEPAY->value:
                    $image_src =
                        'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/applepay.svg';
                    echo self::render_icon($height, $image_src);
                    break;

                case FisrvGateway::GOOGLEPAY->value:
                    $image_src =
                        'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/googlepay.svg';
                    echo self::render_icon($height, $image_src);
                    break;

                case FisrvGateway::CREDITCARD->value:
                    $image_src = 'https://icon-library.com/images/credit-card-icon-white/credit-card-icon-white-9.jpg';
                    echo self::render_icon($height, plugins_url('../assets/images/fisrv-credit-card.svg', __FILE__));
                    break;

                default:
                    break;
            }
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function render_icon(string $height, string $image_src, int $index = 0): string
    {
        ob_start();

        ?>
        <img style="border-radius: 10%; height: <?php echo $height ?>; margin-right: 5px"
            src=" <?php echo WC_HTTPS::force_https_url($image_src) ?>" alt=" <?php esc_attr('Fisrv gateway icon') ?>" />
        <?php

        return ob_get_clean();
    }

    private static function render_icon_with_overlay(string $height, string $image_src, int $index = 0): string
    {
        ob_start();

        ?>
        <div gateway-id="<?php echo FisrvGateway::GENERIC->value ?>" id="fs-icon-container-<?php echo $index ?>"
            class="fs-icon-container" onclick="removeImage(<?php echo $index ?>, this)">
            <div id="fs-icon-overlay-<?php echo $index ?>" class="fs-icon-overlay">🞭 <?php echo esc_html__(
                   'Remove Icon',
                   'fisrv-checkout-for-woocommerce'
               ) ?></div>
            <?php echo self::render_icon($height, $image_src, $index) ?>
        </div>
        <?php

        return ob_get_clean();
    }


    public static function render_healthcheck(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
    {
        ob_start();

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label for="<?php echo "woocommerce_{$wc_settings->id}_{$key}" ?>"><?php echo $data['title'] ?><span
                        class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span></label>
            </th>
            <td class="forminp">
                <fieldset style="display: flex; flex-direction: row; align-items: center;">
                    <legend class="screen-reader-text"><span>Gateway Name</span></legend>
                    <div id="fs-health-btn" style="display: flex; color: white;" class="button-primary fs-add-button"
                        onclick="fetchHealth()">
                        +
                    </div>
                    <div style="display: flex; flex-direction: row; margin-left: 1rem; align-items: center;">
                        <div id="fs-status-indicator"
                            style="background-color: lightblue; border-radius: 100%; width: 0.8em; height: 0.8em; margin-right: 1em;">
                        </div>
                        <div id="fs-status-text"><?php echo esc_html__('Check status', 'fisrv-checkout-for-woocommerce') ?>
                        </div>
                    </div>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    /**
     * Initialize form text fields on gateway options page
     */
    public function init_form_fields(): void
    {
        if ($this->id === FisrvGateway::GENERIC->value) {
            $this->form_fields = array(
                'header' => array(
                    'type' => 'fisrv_header',
                ),
                'api_key' => array(
                    'title' => 'API Key',
                    'type' => 'text',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Acquire API Key from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'api_secret' => array(
                    'title' => 'API Secret',
                    'type' => 'password',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Acquire API Secret from Developer Portal', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'store_id' => array(
                    'title' => 'Store ID',
                    'type' => 'text',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Your Store ID for Checkout', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'is_prod' => array(
                    'title' => esc_html__('Production Mode', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'checkbox',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Use Live (Production) Mode or Test (Sandbox) Mode', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'healthcheck' => array(
                    'title' => esc_html__('API Health', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'healthcheck',
                    'description' => esc_html__('Get current status of Fiserv API and your configuration', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'autocomplete' => array(
                    'title' => esc_html__('Auto-complete Orders', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'checkbox',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Skip processing order status and set to complete status directly', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                // 'enable_tokens' => array(
                // 'title' => 'Enable Transaction Tokens',
                // 'type' => 'checkbox',
                // 'description' => esc_html__('If enabled, a successful payment of a user will be tokenized and can be optionally used on sub-sequent payment', 'fisrv-checkout-for-woocommerce'),
                // 'desc_tip' => true,
                // ),
                'transaction_type' => array(
                    'title' => esc_html__('Transaction Type', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'text',
                    'description' => esc_html__('Set transaction type. Currently, only SALE transactions are available.', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                    'default' => 'Sale',
                    'css' => 'padding: 8px 10px; border: none; pointer-events: none;',
                ),
                'enable_log' => array(
                    'title' => esc_html__('Enable Developer Logs', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'checkbox',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Enable log messages on WooCommerce', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
            );
        }

        $this->form_fields += array(
            'icons' => array(
                'title' => esc_html__('Gateway Icon', 'fisrv-checkout-for-woocommerce'),
                'description' => esc_html__('Image URL for asset', 'fisrv-checkout-for-woocommerce'),
                'type' => 'custom_icon',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => esc_html__('Gateway Name', 'fisrv-checkout-for-woocommerce'),
                'type' => 'text',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Custom name of gateway', 'fisrv-checkout-for-woocommerce'),
                'default' => $this->title,
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => esc_html__('Gateway Description', 'fisrv-checkout-for-woocommerce'),
                'type' => 'text',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Custom description of gateway', 'fisrv-checkout-for-woocommerce'),
                'default' => $this->description,
                'desc_tip' => true,
            ),
            'fail_page' => array(
                'title' => esc_html__('Redirect after payment failure', 'fisrv-checkout-for-woocommerce'),
                'type' => 'select',
                'css' => 'padding: 8px 10px; border: none;',
                'description' => esc_html__('Where to redirect if payment failed', 'fisrv-checkout-for-woocommerce'),
                'default' => 'checkout',
                'desc_tip' => true,
                'options' => array(
                    'checkout' => esc_html__('Checkout page', 'fisrv-checkout-for-woocommerce'),
                    'cart' => esc_html__('Shopping cart', 'fisrv-checkout-for-woocommerce'),
                    'home' => esc_html__('Home page', 'fisrv-checkout-for-woocommerce'),
                ),
            ),
            'restore' => array(
                'type' => 'restore_settings',
            ),
        );
    }

    public static function isLoggingEnabled(): bool
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[FisrvGateway::GENERIC->value];
        return $gateway->get_option('enable_log') === 'yes';
    }
}
