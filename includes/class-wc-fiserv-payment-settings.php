<?php

/**
 * Abstract class that is inherited by WC_Fiserv_Payment_Gateway and inherits
 * WC_Payment_Gateway. This class contains all render methods (HTML markup) and
 * logic pertaining to payment gateway settings only.
 * 
 * @since 1.1.0
 */
abstract class WC_Fiserv_Payment_Settings extends WC_Payment_Gateway
{
    /**
     * Used by wp_kses. List of allowed markup tags when sanitized by WP method.
     * @var array
     */
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
            'value' => true,
            'id' => true,
            'type' => true,
            'checked' => true,
        ],
        'table' => [
            'class' => true,
        ],
        'td' => [
            'class' => true,
        ],
        'tr' => [
            'valign' => true,
        ],
        'th' => [
            'scope' => true,
            'class' => true,
        ],
        'img' => [
            'src' => true,
            'alt' => true,
            'style' => true,
        ],
        'h1' => [
            'style' => true,
        ],
        'script' => [
            'async' => true,
            'src' => true,
            'onload' => true,
        ],
        'tbody' => true,
        'fieldset' => [
            'style' => true,
            'class' => true,
        ],
        'span' => [
            'class' => true,
        ],
        'legend' => [
            'class' => true,
        ],
        'select' => [
            'class' => true,
            'name' => true,
            'id' => true,
        ],
        'option' => [
            'value' => true,
            'selected' => true,
        ],
        'label' => [
            'for' => true,
            'class' => true,
        ],
    ];

    public function __construct()
    {
        $this->description = esc_html__('Payment will be processed by Fiserv. You will be redirected to an external checkout page.', 'fiserv-checkout-for-woocommerce');
    }

    public static function custom_save_icon_value($settings)
    {
        return $settings;
    }

    /**
     * {@inheritDoc}
     * @return void
     */
    public function admin_options()
    {
        ob_start();
        $onGeneric = $this->id === Fisrv_Identifiers::GATEWAY_GENERIC->value;

        ?>
                                <?php echo $onGeneric ? wp_kses(self::render_fiserv_header(), self::WP_KSES_ALLOWED) : '' ?>
                                <table class="form-table">
                                    <?php echo wp_kses($this->generate_settings_html($this->get_form_fields(), false), self::WP_KSES_ALLOWED) ?>
                                </table>
                                <?php echo $onGeneric ? wp_kses(self::render_restore_button($this->id, $this->get_form_fields()), self::WP_KSES_ALLOWED) : '' ?>
                                <?php

                                echo wp_kses(ob_get_clean(), self::WP_KSES_ALLOWED);
    }

    public function generate_transaction_type_html(string $key, array $data): string
    {
        return self::render_option_tablerow('transaction_type', $data, $this, 'SALE');
    }

    /**
     * {@inheritDoc}
     * @param mixed $form_fields
     * @param mixed $echo
     * @return string
     */
    public function generate_settings_html($form_fields = array(), $echo = true): string
    {
        if (empty($form_fields)) {
            $form_fields = $this->get_form_fields();
        }

        $html = "<h1>{$this->method_title}</h1><p>{$this->method_description}</p>";

        foreach ($form_fields as $k => $v) {
            if (str_starts_with($k, 'section-') && $this->id === Fisrv_Identifiers::GATEWAY_GENERIC->value) {
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
            echo wp_kses($html, self::WP_KSES_ALLOWED); // WPCS: XSS ok.
        }

        return $html;
    }

    /**
     * Render grouped section title heading
     * @param string $title
     * @param bool $top
     * @return string
     */
    private static function render_section_header(string $title, bool $top = false): string
    {
        ob_start();

        ?>
                                <tr valign=" top">
                                    <th scope="row" class="titledesc">
                                        <h1 style="font-weight: 400; margin-top: <?php echo esc_attr($top ? '0' : '1.5em') ?>">
                                            <?php echo esc_html($title) ?>
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
                                if ($wc_settings->id === Fisrv_Identifiers::GATEWAY_GENERIC->value) {
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

                                return self::render_option_tablerow($key, $data, $wc_settings, ob_get_clean());
    }

    /**
     * Render reset to defaults button
     * @param string $gateway_id
     * @param array $form_fields
     * @return string
     */
    public static function render_restore_button(string $gateway_id, array $form_fields): string
    {
        ob_start();

        ?>
                                <?php echo wp_kses(self::render_section_header(__('Restore or Save Settings', 'fiserv-checkout-for-woocommerce')), self::WP_KSES_ALLOWED) ?>
                                <div style="margin-top: 2em" class="button-primary">
                                    <?php echo esc_html__('Restore to default', 'fiserv-checkout-for-woocommerce') ?>
                                </div>
                                <?php

                                return ob_get_clean();
    }

    /**
     * Render color picker (copier) from WP theme data
     * @param string $field_html
     * @param string $key
     * @param array $data
     * @param WC_Settings_API $wc_settings
     * @throws \Exception
     * @return string
     */
    public static function render_wp_theme_data(string $field_html, string $key, array $data, WC_Settings_API $wc_settings): string
    {
        ob_start();

        $themeResponse = wp_remote_get(wp_get_theme()->get_stylesheet_directory_uri() . '/theme.json', [
            'sslverify' => false
        ]);

        if (is_wp_error($themeResponse)) {
            throw new Exception(esc_html($themeResponse->get_error_message()));
        }

        $theme = json_decode($themeResponse['body'], true);
        $colors = array_slice($theme['settings']['color']['palette'], 0, 3);
        $width = 150;

        ?>
                                <div class="fs-color-selector-container" style="width: <?php echo esc_attr($width * 3) ?>px; background-color:
        black;">
                                    <?php
                                    foreach ($colors as $color) {
                                        ?>
                                                    <div id="fs-color-selector-<?php echo esc_attr($color['slug']) ?>"
                                                        onclick="fsCopyColor('<?php echo esc_attr($color['color']) ?>', this)" class="fs-color-selector" style="width: <?php echo esc_attr($width) ?>px; background: <?php echo esc_attr($color['color']) ?>; color:
            <?php echo esc_attr(self::isDarkColor($color['color'])) ? 'white' : 'black' ?>">
                                                        <?php echo esc_html($color['color']) ?>
                                                    </div>
                                                    <?php
                                    }
                                    ?>
                                </div>
                                <?php

                                return self::render_option_tablerow($key, $data, $wc_settings, ob_get_clean());
    }

    /**
     * Utiltiy method to check if given color is dark. If true, then color is dark enough to contrast
     * better with white text (for readability). 

     * @param string $hexColor
     * @return bool
     */
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

    /**
     * Template method for all option table rows.
     * @param string $key
     * @param array $data
     * @param WC_Settings_API $wc_settings
     * @param string $child_component
     * @return bool|string
     */
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
                                        <fieldset class="fs-row" style="align-items: center;">
                                            <legend class="screen-reader-text"><span><?php echo esc_html($data['title']) ?></span></legend>
                                            <?php echo wp_kses($child_component, self::WP_KSES_ALLOWED) ?>
                                        </fieldset>
                                    </td>
                                </tr>
                                <?php

                                return ob_get_clean();
    }

    /**
     * Render branded header component (banner)
     * @return string
     */
    public static function render_fiserv_header(): string
    {
        ob_start();

        ?>
                                <div class=" fs-block">
                                    <img style="width: 12em;" src="https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg" />
                                    <div style="margin-top: 1em; margin-bottom: 1em;">
                                        <?php echo esc_html__(
                                            'Pay securely with Fiserv Checkout. Acquire API credentials on our developer portal',
                                            'fiserv-checkout-for-woocommerce'
                                        ) ?>.
                                    </div>
                                    <a style="text-decoration: none;" href="https://developer.fiserv.com"><?php echo esc_html__(
                                        'Visit developer.fiserv.com',
                                        'fiserv-checkout-for-woocommerce'
                                    ) ?></a>
                                </div>
                                <?php

                                return ob_get_clean();
    }

    /**
     * Render icon(s) of payment methods / gatways
     * @param string $gateway_id
     * @param bool $small
     * @return string
     */
    protected static function render_gateway_icons(string $gateway_id, bool $small): string
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GENERIC->value];

        ob_start();
        ?>
                                <div class=" fs-row">
                                    <?php
                                    switch ($gateway_id) {
                                        case Fisrv_Identifiers::GATEWAY_GENERIC->value:
                                            $icons = json_decode($gateway->get_option('custom_icon'), true);

                                            if (is_null($icons) || count($icons) === 0) {
                                                $icons = array('https://upload.wikimedia.org/wikipedia/commons/8/89/Fiserv_logo.svg');
                                            }

                                            foreach ($icons as $index => $icon) {
                                                echo wp_kses(self::render_icon_with_overlay($icon, $index, $small), self::WP_KSES_ALLOWED);
                                            }

                                            break;

                                        case Fisrv_Identifiers::GATEWAY_APPLEPAY->value:
                                            $image_src =
                                                'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/applepay.svg';
                                            echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED);
                                            break;

                                        case Fisrv_Identifiers::GATEWAY_GOOGLEPAY->value:
                                            $image_src =
                                                'https://woocommerce.com/wp-content/plugins/wccom-plugins/payment-gateway-suggestions/images/icons/googlepay.svg';
                                            echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED);
                                            break;
                                        case Fisrv_Identifiers::GATEWAY_CREDITCARD->value:
                                            $image_src = 'https://icon-library.com/images/credit-card-icon-white/credit-card-icon-white-9.jpg';
                                            echo wp_kses(self::render_icon(plugins_url('../assets/images/fiserv-credit-card.svg', __FILE__), $small), self::WP_KSES_ALLOWED);
                                            break;

                                        default:
                                            break;
                                    }
                                    ?>
                                </div>
                                <?php

                                return ob_get_clean();
    }

    /**
     * Render single icon
     * @param string $image_src
     * @param bool $small
     * @return string
     */
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

    /**
     * Render single icon with an interactable overlay on top. This is used for clickable icons (I.e. when
     * merchant wants to remove a custom icon).
     * @param string $image_src
     * @param int $index
     * @param bool $small
     * @return string
     */
    private static function render_icon_with_overlay(string $image_src, int $index = 0, bool $small = false): string
    {
        ob_start();

        ?>
                                <div gateway-id="<?php echo esc_attr(Fisrv_Identifiers::GATEWAY_GENERIC->value) ?>"
                                    id="fs-icon-container-<?php echo esc_attr($index) ?>" class="fs-icon-container"
                                    onclick="removeImage(<?php echo esc_attr($index) ?>, this)">
                                    <div id="fs-icon-overlay-<?php echo esc_attr($index) ?>" class="fs-icon-overlay">🞭 <?php echo esc_html__(
                                           'Remove Icon',
                                           'fiserv-checkout-for-woocommerce'
                                       ) ?></div>
                                    <?php echo wp_kses(self::render_icon($image_src, $small), self::WP_KSES_ALLOWED) ?>
                                </div>
                                <?php

                                return ob_get_clean();
    }


    /**
     * Render API health check section
     * @param string $field_html
     * @param string $key
     * @param array $data
     * @param WC_Settings_API $wc_settings
     * @return bool|string
     */
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
                                    <div id="fs-status-text"><?php echo esc_html__('Check status', 'fiserv-checkout-for-woocommerce') ?>
                                    </div>
                                </div>
                                <?php

                                return self::render_option_tablerow($key, $data, $wc_settings, ob_get_clean());
    }

    /**
     * Initialize form text fields on gateway options page
     */
    public function init_form_fields(): void
    {
        $this->form_fields += array(
            'icons' => array(
                'title' => esc_html__('Gateway Icon', 'fiserv-checkout-for-woocommerce'),
                'description' => esc_html__('Image URL for asset', 'fiserv-checkout-for-woocommerce'),
                'type' => 'custom_icon',
                'desc_tip' => true,
            ),
            'title' => array(
                'title' => esc_html__('Gateway Name', 'fiserv-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('Custom name of gateway', 'fiserv-checkout-for-woocommerce'),
                'default' => $this->title,
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => esc_html__('Gateway Description', 'fiserv-checkout-for-woocommerce'),
                'type' => 'text',
                'description' => esc_html__('Custom description of gateway', 'fiserv-checkout-for-woocommerce'),
                'default' => $this->description,
                'desc_tip' => true,
            ),
        );
    }

    /**
     * Check if log messages are enabled
     * @return bool True if enabled else false
     */
    public static function isLoggingEnabled(): bool
    {
        $gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GENERIC->value];
        return $gateway->get_option('enable_log') === 'yes';
    }
}
