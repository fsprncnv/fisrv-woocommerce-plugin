<?php

abstract class WC_Fisrv_Payment_Settings extends WC_Payment_Gateway
{
    const WP_KSES_ALLOWED = [
        'div' => [
            'onclick' => true,
            'class' => true,
            'style' => true,
            'gateway-id' => true,
            'id' => true,
        ],
        'input' => [
            'style' => true,
            'class' => true,
            'placeholder' => true,
            'name' => true,
            'id' => true,
            'type' => true,
        ],
        'img' => [
            'src' => true,
            'alt' => true,
            'style' => true,
        ],
        'h1' => [
            'style' => true,
        ],
        'tr' => [
            'valign' => true,
        ],
        'th' => [
            'scope' => true,
            'class' => true,
        ]
    ];

    public function __construct()
    {
        $this->description = esc_html__('Payment will be processed by Fiserv. You will be redirected to an external checkout page.', 'fisrv-checkout-for-woocommerce');
    }

    public static function custom_save_icon_value($settings)
    {
        return $settings;
    }

    public function admin_options()
    {
        ob_start();
        $onGeneric = $this->id === FisrvGateway::GENERIC->value;

        ?>
        <?php echo $onGeneric ? wp_kses(self::render_fisrv_header(), self::WP_KSES_ALLOWED) : '' ?>
        <table class="form-table"> <?php echo $this->generate_settings_html($this->get_form_fields(), false) ?></table>
        <?php echo $onGeneric ? wp_kses(self::render_restore_button($this->id, $this->get_form_fields()), self::WP_KSES_ALLOWED) : '' ?>
        <?php

        echo ob_get_clean();
    }

    public function generate_settings_html($form_fields = array(), $echo = true)
    {
        if (empty($form_fields)) {
            $form_fields = $this->get_form_fields();
        }

        $html = "<h1>{$this->method_title}</h1><p>{$this->method_description}</p>";

        foreach ($form_fields as $k => $v) {
            if (str_starts_with($k, 'section-') && $this->id === FisrvGateway::GENERIC->value) {
                $html .= wp_kses(self::render_section_header($v['title']), self::WP_KSES_ALLOWED);
                continue;
            }

            $type = $this->get_field_type($v);

            if (method_exists($this, 'generate_' . $type . '_html')) {
                $html .= $this->{'generate_' . $type . '_html'}($k, $v);
            } elseif (has_filter('woocommerce_generate_' . $type . '_html')) {
                $html .= apply_filters('woocommerce_generate_' . $type . '_html', '', $k, $v, $this);
            } else {
                $html .= $this->generate_text_html($k, $v);
            }
        }

        if ($echo) {
            echo $html; // WPCS: XSS ok.
        } else {
            return $html;
        }
    }

    private static function render_section_header(string $title, bool $top = false): string
    {
        ob_start();

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <h1 style="font-weight: 400; margin-top: <?php echo esc_attr($top ? '0' : '1.5em') ?>">
                    <?php echo esc_html__($title, 'fisrv-checkout-for-woocommerce') ?>
                </h1>
            </th>
        </tr>
        <?php

        return ob_get_clean();
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
        <?php echo wp_kses(self::render_gateway_icons($wc_settings->id, false), self::WP_KSES_ALLOWED) ?>
        <?php
        if ($wc_settings->id === FisrvGateway::GENERIC->value) {
            ?>
            <div style="height: fit-content;" class="fs-row">
                <input style="margin-left: 8px; margin-right: 8px; padding: 8px 10px; border: none;"
                    class="input-text regular-input" type="text" name="fs-icons-data" id="fs-icons-data"
                    placeholder="Enter image URL to add to list">
                <div class="fs-button button-primary" gateway-id="<?php echo esc_attr($wc_settings->id) ?>"
                    onclick="fsAddImage(this)">+
                </div>
            </div>
            <?php
        }
        ?>
        <?php

        $component = ob_get_clean();
        return self::render_option_tablerow($key, $data, $wc_settings, $component);
    }

    public static function render_restore_button(string $gateway_id, array $form_fields): string
    {
        ob_start();

        ?>
        <?php echo self::render_section_header('Restore or Save Settings') ?>
        <div style="margin-top: 2em" class="button-primary">
            <?php echo esc_html__('Restore to default', 'fisrv-checkout-for-woocommerce') ?>
        </div>
        <?php

        return ob_get_clean();
    }

    public static function render_wp_theme_data(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        ob_start();
        $themeResponse = wp_remote_get(wp_get_theme()->get_stylesheet_directory_uri() . '/theme.json');
        $theme = json_decode($themeResponse['body'], true);
        $colors = array_slice($theme['settings']['color']['palette'], 0, 3);
        $width = 150;

        ?>
        <div class="fs-color-selector-container" style="width: <?php echo esc_attr($width * 3) ?>px; background-color: black;">
            <?php
            foreach ($colors as $color) {
                ?>
                <div id="fs-color-selector-<?php echo esc_attr($color['slug']) ?>"
                    onclick="fsCopyColor('<?php echo esc_attr($color['color']) ?>', this)" class="fs-color-selector"
                    style="width: <?php echo esc_attr($width) ?>px; background: <?php echo esc_attr($color['color']) ?>; color: <?php echo esc_attr(self::isDarkColor($color['color'])) ? 'white' : 'black' ?>">
                    <?php echo esc_html($color['color']) ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php

        $component = ob_get_clean();
        return self::render_option_tablerow($key, $data, $wc_settings, $component);
    }

    private static function isDarkColor(string $hexColor): bool
    {
        $c = ltrim($hexColor, '#');

        $rgb = intval($c, 16);
        $r = ($rgb >> 16) & 0xff;
        $g = ($rgb >> 8) & 0xff;
        $b = ($rgb >> 0) & 0xff;

        $luma = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
        return $luma < 40;
    }

    private static function render_option_tablerow(string $key, array $data, WC_Settings_API $wc_settings, string $child_component): bool|string
    {
        ob_start();

        ?>
        <tr valign="top">
            <th scope="row" class="titledesc">
                <label
                    for="<?php echo esc_attr("woocommerce_{$wc_settings->id}_{$key}") ?>"><?php echo esc_html($data['title']) ?>
                    <span class="woocommerce-help-tip" tabindex="0" aria-label="Custom name of gateway"></span>
                </label>
            </th>
            <td class="forminp">
                <fieldset style="display: flex; flex-direction: row; align-items: center;">
                    <legend class="screen-reader-text"><span><?php echo esc_html($data['title']) ?></span></legend>
                    <?php echo wp_kses($child_component, self::WP_KSES_ALLOWED) ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
    }

    public static function render_fisrv_header(): string
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

    protected static function render_gateway_icons(string $gateway_id, bool $small): string
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[FisrvGateway::GENERIC->value];

        ob_start();
        ?>
        <div class="fs-row">
            <?php
            switch ($gateway_id) {
                case FisrvGateway::GENERIC->value:
                    $icons = json_decode($gateway->get_option('custom_icon'), true);

                    if (is_null($icons) || count($icons) === 0) {
                        $icons = array('https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg');
                    }

                    foreach ($icons as $index => $icon) {
                        echo wp_kses(self::render_icon_with_overlay($icon, $index, $small), self::WP_KSES_ALLOWED);
                    }

                    break;

                case FisrvGateway::APPLEPAY->value:
                    $image_src =
                        'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/applepay.svg';
                    echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED);
                    break;

                case FisrvGateway::GOOGLEPAY->value:
                    $image_src =
                        'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/googlepay.svg';
                    echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED);
                    break;
                case FisrvGateway::CREDITCARD->value:
                    $image_src = 'https://icon-library.com/images/credit-card-icon-white/credit-card-icon-white-9.jpg';
                    echo wp_kses(self::render_icon(plugins_url('../assets/images/fisrv-credit-card.svg', __FILE__), $small), self::WP_KSES_ALLOWED);
                    break;

                default:
                    break;
            }
            ?>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function render_icon(string $image_src, bool $small): string
    {
        ob_start();

        ?>
        <img style="height: <?php echo esc_attr($small ? '2em' : '4em') ?>; border-radius: 10%; margin-right: 5px"
            src=" <?php echo esc_url(WC_HTTPS::force_https_url($image_src)) ?>"
            alt=" <?php esc_attr('Fisrv gateway icon') ?>" />
        <?php

        return ob_get_clean();
    }

    private static function render_icon_with_overlay(string $image_src, int $index = 0, bool $small): string
    {
        ob_start();

        ?>
        <div gateway-id="<?php echo esc_attr(FisrvGateway::GENERIC->value) ?>"
            id="fs-icon-container-<?php echo esc_attr($index) ?>" class="fs-icon-container"
            onclick="removeImage(<?php echo esc_attr($index) ?>, this)">
            <div id="fs-icon-overlay-<?php echo esc_attr($index) ?>" class="fs-icon-overlay">🞭 <?php echo esc_html__(
                   'Remove Icon',
                   'fisrv-checkout-for-woocommerce'
               ) ?></div>
            <?php echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED) ?>
        </div>
        <?php

        return ob_get_clean();
    }


    public static function render_healthcheck(string $field_html, string $key, array $data, WC_Settings_API $wc_settings)
    {
        ob_start();

        ?>
        <div id="fs-health-btn" style="display: flex; color: white;" class="button-primary fs-button"
            onclick="fsFetchHealth('<?php echo esc_attr($wc_settings->get_option('is_prod')) ?>')">
            +
        </div>
        <div class="fs-health-check-container">
            <div id="fs-status-indicator"
                style="background-color: lightblue; border-radius: 100%; width: 0.8em; height: 0.8em; margin-right: 1em;">
            </div>
            <div id="fs-status-text"><?php echo esc_html__('Check status', 'fisrv-checkout-for-woocommerce') ?>
            </div>
        </div>
        <?php

        $component = ob_get_clean();
        return self::render_option_tablerow($key, $data, $wc_settings, $component);
    }

    /**
     * Initialize form text fields on gateway options page
     */
    public function init_form_fields(): void
    {
        if ($this->id === FisrvGateway::GENERIC->value) {
            $this->form_fields = array(
                'section-1' => [
                    'title' => 'Basic Settings'
                ],
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
                'enable_log' => array(
                    'title' => esc_html__('Enable Developer Logs', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'checkbox',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Enable log messages on WooCommerce', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'section-2' => [
                    'title' => 'Order Settings'
                ],
                'autocomplete' => array(
                    'title' => esc_html__('Auto-complete Orders', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'checkbox',
                    'css' => 'padding: 8px 10px; border: none;',
                    'description' => esc_html__('Skip processing order status and set to complete status directly', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                ),
                'enable_browser_lang' => array(
                    'title' => esc_html__('Checkout Page Language', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'select',
                    'css' => 'padding: 8px 10px; border: none;',
                    'default' => 'admin',
                    'description' => esc_html__('Should language of checkout page be inferred from customer\'s browser or set to admin language', 'fisrv-checkout-for-woocommerce'),
                    'desc_tip' => true,
                    'options' => array(
                        'browser' => esc_html__('Customer\'s preferred language', 'fisrv-checkout-for-woocommerce'),
                        'admin' => esc_html__('Admin dashboard setting', 'fisrv-checkout-for-woocommerce'),
                    ),
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
                'section-3' => [
                    'title' => 'Customization'
                ],
                'wp_theme_data' => array(
                    'title' => esc_html__('Theme Colors', 'fisrv-checkout-for-woocommerce'),
                    'type' => 'wp_theme_data',
                    'description' => esc_html__('Info about current WordPress theme data which you can use to customize your checkout page on our Virtual Terminal', 'fisrv-checkout-for-woocommerce'),
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
        );
    }

    public static function isLoggingEnabled(): bool
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[FisrvGateway::GENERIC->value];
        return $gateway->get_option('enable_log') === 'yes';
    }
}
