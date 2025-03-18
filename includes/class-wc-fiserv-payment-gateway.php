<?php

if (!defined('ABSPATH')) {
    exit;
}

use Fisrv\Exception\ErrorResponse;
use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Custom WooCommerce payment gateway.
 *
 * @package  WooCommerce
 * @category Payment Gateways
 * @author   fiserv
 * @since    1.0.0
 */
abstract class WC_Fiserv_Payment_Gateway extends WC_Fiserv_Payment_Settings
{

    protected ?PreSelectedPaymentMethod $selected_method = null;

    protected array $supported_methods = array();

    public function __construct()
    {
        parent::__construct();

        $this->has_fields = false;
        $this->init_form_fields();
        $this->init_properties();
        $this->supports = array(
            'products',
            'refunds',
        );

        add_action('add_meta_boxes', [$this, 'custom_order_meta_box'], 10, 2);
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

        add_filter('woocommerce_gateway_icon', array($this, 'custom_payment_gateway_icons'), 10, 2);
        add_filter('woocommerce_generate_custom_icon_html', array(WC_Fiserv_Payment_Settings::class, 'render_icons_component'), 1, 4);
        add_filter('woocommerce_generate_wp_theme_data_html', array(WC_Fiserv_Payment_Settings::class, 'render_wp_theme_data'), 1, 4);
        add_filter('woocommerce_generate_healthcheck_html', array(WC_Fiserv_Payment_Settings::class, 'render_healthcheck'), 10, 4);
        add_filter('woocommerce_settings_api_sanitized_fields_' . $this->id, array(WC_Fiserv_Payment_Settings::class, 'custom_save_icon_value'), 10, 1);
        add_filter('woocommerce_locate_template', array($this, 'custom_woocommerce_locate_template'), 10, 3);
        // add_filter('woocommerce_generate_text_html', array(WC_Fiserv_Payment_Settings::class, 'render_text_field'), 1, 4);

        add_filter('woocommerce_order_button_html', array(WC_Fiserv_Gateway_Googlepay::class, 'replace_order_button_html'), 10, 2);
    }

    /**
     * UI component used for detailed checkout response report
     * 
     * @param mixed $id
     * @param mixed $order
     * @return void
     */
    public function custom_order_meta_box($id, $order)
    {
        add_meta_box(
            'fiserv-checkout-order-meta-box',
            'Fiserv Checkout Info ',
            function () use ($order) {
                echo wp_kses($this->custom_order_meta_box_callback($order), [
                    'div' => [
                        'class' => true,
                        'id' => true,
                        'onclick' => true,
                        'reported' => true,
                    ],
                    'a' => [
                        'href' => true
                    ],
                    'span' => [
                        'class' => true
                    ],
                    'h4' => true,
                ]);
            },
            $id,
            'side',
            'core'
        );
    }

    /**
     * Callback used for custom_order_meta_box. Contains UI markup.
     * 
     * @param mixed $order
     * @return string
     */
    public function custom_order_meta_box_callback($order): string
    {
        ob_start();

        $meta_data = [
            'Checkout Link' => "<a href='" . $order->get_meta('_fiserv_plugin_checkout_link') . "'>Go to checkout page</a>",
            'Checkout ID' => $order->get_meta('_fiserv_plugin_checkout_id'),
            'Trace ID' => $order->get_meta('_fiserv_plugin_trace_id'),
        ];

        ?>
        <div class="customer-history order-attribution-metabox">
            <div id="fs-checkout-info-container">
                <?php
                foreach ($meta_data as $key => $value) {
                    ?>
                    <h4><?php echo esc_html($key) ?></h4>
                    <span class="order-attribution-total-orders"><?php echo wp_kses($value, ['a' => ['href' => true]]) ?></span>
                    <?php
                }
                ?>
            </div>
            <div class="fs-checkout-report-button" reported="false"
                onclick="fetchCheckoutReport('<?php echo esc_html($order->get_meta('_fiserv_plugin_checkout_id')) ?>', this)">
                Fetch Full
                Checkout
                Data</div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Check if payment method is available. Disable if API credentials are empty.
     * Does not validate set values.
     * 
     * @return bool True if payment gateway available
     */
    public function is_available(): bool
    {
        $generic_gateway = WC()->payment_gateways()->payment_gateways()[Fisrv_Identifiers::GATEWAY_GENERIC->value];

        return !in_array(
            '',
            array(
                $generic_gateway->get_option('api_key'),
                $generic_gateway->get_option('api_secret'),
                $generic_gateway->get_option('store_id'),
            )
        ) && parent::is_available();
    }

    /**
     * Auto-toggle generic payment gateway to disabled if any of the specific (pre-selection) gateways 
     * have been enabled and vice versa.
     * 
     * @param mixed $key
     * @param mixed $value
     * @return void
     */
    private function toggle_exclusive_payment_methods($key, $value)
    {
        if ($key === 'enabled' && $value === 'yes') {
            if ($this->id === Fisrv_Identifiers::GATEWAY_GENERIC->value) {
                $this->disable_gateway(new WC_Fiserv_Gateway_Applepay());
                $this->disable_gateway(new WC_Fiserv_Gateway_Googlepay());
                $this->disable_gateway(new WC_Fiserv_Gateway_Cards());
                WC_Fiserv_Logger::generic_log('Disabled specific gateways since generic gateway was enabled');
            } else {
                $this->disable_gateway(new WC_Fiserv_Payment_Generic());
                WC_Fiserv_Logger::generic_log('Disabled generic gateway since specific gateways were enabled');
            }
        }
    }

    /**
     * Shorthand to disable a given gateway
     * @param WC_Payment_Gateway $gateway
     * @return void
     */
    private function disable_gateway(WC_Payment_Gateway $gateway): void
    {
        if ($gateway->get_option('enabled') === 'yes') {
            $gateway->update_option('enabled', 'no');
        }
    }

    /**
     * Initialize properties from options
     */
    protected function init_properties(): void
    {
        $credit_title = 'Credit / Debit Card';
        if ($this->id === Fisrv_Identifiers::GATEWAY_CREDITCARD->value) {
            if ($this->title !== $credit_title) {
                $this->title = $credit_title;
                $this->update_option('title', $credit_title);
            }
            $this->description = $this->get_option('description');
            return;
        }
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
    }

    /**
     * Render custom icons on payment selection page
     * @param mixed $icon
     * @param mixed $gateway_id
     * @return mixed
     */
    public static function custom_payment_gateway_icons($icon, $gateway_id)
    {
        if (!str_starts_with($gateway_id, 'fiserv')) {
            return $icon;
        }

        return self::render_gateway_icons($gateway_id, true);
    }

    /**
     * Inject payment method template to adjust layout on selection box
     * 
     * @param mixed $template
     * @param mixed $template_name
     * @param mixed $template_path
     * @return mixed
     */
    public function custom_woocommerce_locate_template($template, $template_name, $template_path)
    {
        if (!str_starts_with($this->id, 'fiserv')) {
            return $template;
        }

        $custom_template_path = plugin_dir_path(__FILE__) . '../templates/checkout/payment-method.php';

        if ($template_name === 'checkout/payment-method.php') {
            return $custom_template_path;
        }

        return $template;
    }

    /**
     * {@inheritDoc}
     * 
     * @return array<string, string>
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            throw new Exception(esc_html__('Processing payment failed. Order is invalid.', 'fiserv-checkout-for-woocommerce'));
        }

        try {
            $checkout_link = WC_Fiserv_Checkout_Handler::create_checkout_link($order, $this->selected_method);
            return array(
                'result' => 'success',
                'redirect' => $checkout_link,
            );
        } catch (\Throwable $th) {
            WC_Fiserv_Logger::error($order, $th->getMessage());
            return array(
                'result' => 'failure',
            );
        }
    }

    /**
     * {@inheritDoc}
     * 
     * @param mixed $order_id
     * @param mixed $amount
     * @param mixed $reason
     * @throws \Exception
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = ''): bool|WP_Error
    {
        $order = wc_get_order($order_id);
        if (!$order instanceof WC_Order) {
            throw new Exception(esc_html__('Processing payment failed. Order is invalid.', 'fiserv-checkout-for-woocommerce'));
        }
        try {
            $response = WC_Fiserv_Checkout_Handler::refund_checkout($order, $amount);
            if (isset($response->error)) {
                $order->add_order_note("Refund failed due to {($response->error->title ?? 'server error')}. Check debug logs for detailed report." . (($reason !== '') ? (" Refund reason given: $reason") : ''));
                return false;
            }
            $order->add_order_note("Order refunded via Fiserv Gateway. Refunded amount: {$response->approvedAmount->total} {$response->approvedAmount->currency->value} Transaction ID: {$response->ipgTransactionId}" . (($reason !== '') ? (" Refund reason given: $reason") : ''));
            return true;
        } catch (ErrorResponse $e) {
            WC_Fiserv_Logger::log($order, 'Refund has failed on API client (or server) level: ' . $e->getMessage());
        } catch (\Throwable $th) {
            WC_Fiserv_Logger::log($order, 'Refund has failed on backend level: ' . $th->getMessage());
        }
        return false;
    }

    /**
     * Summary of can_refund_order
     *
     * @param  WC_Order $order
     * @return bool
     */
    public function can_refund_order(mixed $order)
    {
        if (
            !($order instanceof WC_Order)
            || !(str_starts_with($order->get_payment_method(), 'fiserv'))
            || is_null($order->get_date_completed())
            || !($order->is_paid())
        ) {
            return false;
        }

        return true;
    }
}
