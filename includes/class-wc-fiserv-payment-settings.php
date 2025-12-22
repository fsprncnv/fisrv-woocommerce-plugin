<?php

if (!defined('ABSPATH')) {
    exit;
}

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
    private static $WP_KSES_ALLOWED = [
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

    private $uh = [];

    public function __construct()
    {
        self::$WP_KSES_ALLOWED = array_merge(wp_kses_allowed_html('post'), self::$WP_KSES_ALLOWED);
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
        <?php echo $onGeneric ? wp_kses(self::render_fiserv_header(), self::$WP_KSES_ALLOWED) : '' ?>
        <table class="form-table">
            <?php echo wp_kses($this->generate_settings_html($this->get_form_fields(), false), self::$WP_KSES_ALLOWED) ?>
        </table>
        <?php

        echo wp_kses(ob_get_clean(), self::$WP_KSES_ALLOWED);
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
            echo wp_kses($html, self::$WP_KSES_ALLOWED); // WPCS: XSS ok.
        }

        return $html;
    }

    /**
     * Render grouped section title heading
     * @param string $title
     * @param bool $top
     * @return string
     */
    private static function generate_section_heading_html($key, $data): string
    {
        ob_start();

        ?>
        <tr valign=" top">
            <th scope="row" class="titledesc">
                <h1 style="font-weight: 400; margin-top: 1.5em">
                    <?php echo esc_html($data['title']) ?>
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
        <?php echo wp_kses(self::render_gateway_icons($wc_settings->id, false), self::$WP_KSES_ALLOWED) ?>
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
        $fallback_colors = [
            ['color' => '#FF6600', 'slug' => 'none'],
            ['color' => '#000000', 'slug' => 'none'],
            ['color' => '#FFFFFF', 'slug' => 'none'],
        ];
        $width = 150;
        try {
            $themeResponse = wp_remote_get(wp_get_theme()->get_stylesheet_directory_uri() . '/theme.json', [
                'sslverify' => false
            ]);
            if (is_wp_error($themeResponse)) {
                throw new Exception(esc_html($themeResponse->get_error_message()));
            }
            $theme = json_decode($themeResponse['body'], true);
            $palette = $theme['settings']['color']['palette'];
            $colors = array_slice(
                array_merge($palette, $fallback_colors),
                0,
                min(3, count($fallback_colors))
            );
        } catch (Throwable $th) {
            $colors = $fallback_colors;
            WC_Fiserv_Logger::generic_log('Could not retrieve theme colors, using fallback: ' . $th->getMessage());
        }
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
                    for="<?php echo esc_attr("woocommerce_{$wc_settings->id}_{$key}"); ?>"><?php echo wp_kses_post($data['title']); ?>
                    <?php echo $wc_settings->get_tooltip_html($data); // WPCS: XSS ok. ?>
                </label>
            </th>
            <td class="forminp">
                <fieldset class="fs-row" style="align-items: center;">
                    <legend class="screen-reader-text"><span><?php echo esc_html($data['title']) ?></span></legend>
                    <?php echo wp_kses($child_component, self::$WP_KSES_ALLOWED) ?>
                    <?php echo $wc_settings->get_description_html($data); // WPCS: XSS ok. ?>
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
            <img style="width: 12em;"
                src="<?php echo esc_attr(self::get_method_icon(Fisrv_Identifiers::GATEWAY_GENERIC->value)) ?>" />
            <div style="margin-top: 1em; margin-bottom: 1em;">
                <?php echo esc_html__(
                    'Pay securely with Fiserv Checkout. Acquire API credentials on our developer portal',
                    'fiserv-checkout-for-woocommerce'
                ) ?>.
            </div>
            <div><?php echo esc_html__(
                'Visit developer.fiserv.com',
                'fiserv-checkout-for-woocommerce'
            ) ?></div>
        </div>
        <?php

        return ob_get_clean();
    }

    private static function get_method_icon(string $method, string $image_type = 'svg'): string
    {
        return plugin_dir_url(__FILE__) . "../assets/images/{$method}.{$image_type}";
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
        <div class="fs-row">
            <?php
            if ($gateway_id === Fisrv_Identifiers::GATEWAY_GENERIC->value) {
                $icons = json_decode($gateway->get_option('custom_icon'), true);
                if (is_null($icons) || count($icons) === 0) {
                    $icons = array(self::get_method_icon($gateway_id));
                }
                foreach ($icons as $index => $icon) {
                    echo wp_kses(self::render_icon_with_overlay($icon, $index, $small), self::$WP_KSES_ALLOWED);
                }
            } else {
                echo wp_kses(self::render_icon(self::get_method_icon($gateway_id), $small), self::$WP_KSES_ALLOWED);
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
        <img style="height: <?php echo esc_attr($small ? '2.5em' : '8em') ?>; border-radius: 10%; margin-right: 5px"
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
            id="fs-icon-container-<?php echo esc_attr($index) ?>" class="fs-icon-container">
            <?php if (is_admin()) { ?>
                <div onclick="removeImage(<?php echo esc_attr($index) ?>, this)" id="fs-icon-overlay-<?php echo esc_attr($index) ?>"
                    class="fs-icon-overlay">🞭 <?php echo esc_html__(
                        'Remove Icon',
                        'fiserv-checkout-for-woocommerce'
                    ) ?></div>
            <?php } ?>
            <?php echo wp_kses(self::render_icon($image_src, $small), self::$WP_KSES_ALLOWED) ?>
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

    public function generate_reset_settings_html($key, $data)
    {
        ob_start();
        ?>
        <div>
            <input type="hidden" name="wc_fiserv_reset_settings" id="wc_fiserv_reset_settings" value="0" />
            <?php wp_nonce_field('wc_fiserv_reset_settings_action', 'wc_fiserv_reset_settings_nonce'); ?>

            <button type="button" class="button button-secondary js-fiserv-reset-settings" aria-disabled="true" disabled>
                <?php echo esc_html__('Restore to default', 'fiserv-checkout-for-woocommerce'); ?>
            </button>

            <script>
                (function () {
                    function q(sel) { return document.querySelector(sel); }
                    document.addEventListener('DOMContentLoaded', function () {
                        var form = q('#mainform');
                        var saveBtn = q('#mainform .woocommerce-save-button');
                        var resetBtn = q('.js-fiserv-reset-settings');
                        if (!form || !saveBtn || !resetBtn) return;

                        // Mirror function: copy disabled state & class from Save to Reset
                        function mirrorState() {
                            var isDisabled = saveBtn.hasAttribute('disabled') || saveBtn.classList.contains('disabled');
                            resetBtn.disabled = isDisabled;
                            resetBtn.classList.toggle('disabled', isDisabled);
                            resetBtn.setAttribute('aria-disabled', String(isDisabled));
                        }

                        mirrorState();

                        // Observe attribute/class changes on Save button
                        var obs = new MutationObserver(mirrorState);
                        obs.observe(saveBtn, { attributes: true, attributeFilter: ['class', 'disabled'] });

                        // Also resync when inputs change (Woo toggles Save based on "dirty" form)
                        form.addEventListener('input', mirrorState, true);
                        form.addEventListener('change', mirrorState, true);

                        // Your existing click handler (optional): prompt, set flag, submit
                        resetBtn.addEventListener('click', function (ev) {
                            ev.preventDefault();
                            if (resetBtn.disabled) return; // respect mirrored state
                            if (!confirm('<?php echo esc_js(__('Are you sure you want to restore all settings to their defaults?', 'fiserv-checkout-for-woocommerce')); ?>')) {
                                return;
                            }
                            var flag = q('#wc_fiserv_reset_settings');
                            if (flag) flag.value = '1';
                            // Submit via Woo's native Save button (ensures POST goes through expected pipeline)
                            if (!saveBtn.disabled) {
                                saveBtn.click();
                            } else {
                                form.submit();
                            }
                        });
                    });
                })();
            </script>
        </div>
        <?php
        return self::render_option_tablerow($key, $data, $this, ob_get_clean());
    }

    public function process_admin_options()
    {
        if (isset($_POST['wc_fiserv_reset_settings']) && '1' === $_POST['wc_fiserv_reset_settings']) {
            if (
                empty($_POST['wc_fiserv_reset_settings_nonce']) ||
                !wp_verify_nonce(
                    sanitize_text_field(wp_unslash($_POST['wc_fiserv_reset_settings_nonce'])),
                    'wc_fiserv_reset_settings_action'
                )
            ) {
                WC_Admin_Settings::add_error(__('Security check failed. Settings were not reset.', 'fiserv-checkout-for-woocommerce'));
                return parent::process_admin_options();
            }
            $this->reset_settings_to_defaults();
            WC_Admin_Settings::add_message(__('Settings have been restored to defaults.', 'fiserv-checkout-for-woocommerce'));
            $this->init_settings();
            return;
        }
        parent::process_admin_options();
    }

    protected function reset_settings_to_defaults()
    {
        if (empty($this->form_fields)) {
            $this->init_form_fields();
        }
        $current = get_option($this->get_option_key(), []);
        $new = is_array($current) ? $current : [];
        foreach ((array) $this->form_fields as $key => $field) {
            if (!empty($field['no_reset'])) {
                continue;
            }
            if (array_key_exists('default', $field)) {
                $value = $field['default'];
                if (isset($field['type']) && 'checkbox' === $field['type']) {
                    $value = isset($field['default']) ? $field['default'] : 'no';
                }
                $new[$key] = $value;
            }
        }
        update_option($this->get_option_key(), $new);
        $this->settings = $new;
        $this->title = isset($new['title']) ? $new['title'] : $this->method_title;
        $this->description = isset($new['description']) ? $new['description'] : '';
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
            'section-4' => [
                'title' => esc_html__('Restore or Save Settings', 'fiserv-checkout-for-woocommerce'),
                'type' => 'section_heading'
            ],
            'reset' => array(
                'title' => esc_html__('Restore default settings', 'fiserv-checkout-for-woocommerce'),
                'description' => esc_html__('Restore to initial values', 'fiserv-checkout-for-woocommerce'),
                'desc_tip' => true,
                'type' => 'reset_settings',
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
