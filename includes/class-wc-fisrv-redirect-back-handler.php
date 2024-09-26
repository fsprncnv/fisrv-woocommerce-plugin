<?php

final class WC_Fisrv_Redirect_Back_Handler
{
    /**
     * Triggered right before the Pay for Order form, after validation of the order and customer.
     *
     * @param WC_Order                  $order              The order that is being paid for.
     * @param string                    $order_button_text  The text for the submit button.
     * @param array<WC_Payment_Gateway> $available_gateways All available gateways.
     *
     * @return array<string, mixed> | false   Passed (and modified) function params
     */
    public static function retry_payment_on_checkout(WC_Order $order, string $order_button_text, array $available_gateways): array|false
    {
        if (!check_admin_referer(Fisrv_Identifiers::FISRV_NONCE->value)) {
            return false;
        }

        if (!isset($_GET['transaction_approved']) || sanitize_text_field(wp_unslash($_GET['transaction_approved'])) !== 'false') {
            return false;
        }

        $order_button_text = esc_html__('Retry payment', 'fisrv-checkout-for-woocommerce');
        $order->update_status('wc-pending', esc_html__('Retrying payment', 'fisrv-checkout-for-woocommerce'));

        self::display_error_message($order);

        return array(
            'order' => $order,
            'available_gateways' => $available_gateways,
            'order_button_text' => $order_button_text,
        );
    }

    public static function retry_payment_on_cart(): void
    {
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_REQUEST['_wpnonce'])), Fisrv_Identifiers::FISRV_NONCE->value)) {
            return;
        }

        if (
            !isset($_GET['transaction_approved']) ||
            sanitize_text_field(wp_unslash($_GET['transaction_approved'])) !== 'false' ||
            !isset($_GET['wc_order_id'])
        ) {
            return;
        }

        $order = wc_get_order(sanitize_text_field(wp_unslash($_GET['wc_order_id'])));

        if (!($order instanceof WC_Order)) {
            return;
        }

        $order->update_status('wc-pending', esc_html__('Retrying payment', 'fisrv-checkout-for-woocommerce'));
        self::display_error_message($order);
    }

    private static function display_error_message(WC_Order $order): bool
    {
        if (!check_admin_referer(Fisrv_Identifiers::FISRV_NONCE->value)) {
            return false;
        }

        if (isset($_GET['message']) && isset($_GET['code'])) {
            if (!check_admin_referer(Fisrv_Identifiers::FISRV_NONCE->value)) {
                return false;
            }

            $fisrv_error_message = sanitize_text_field(wp_unslash($_GET['message']));
            $fisrv_error_code = sanitize_text_field(wp_unslash($_GET['code']));
        }

        /* translators: %s: Fisrv error message */
        wc_add_notice(sprintf(esc_html__('Payment has failed: %s', 'fisrv-checkout-for-woocommerce'), $fisrv_error_message ?? 'Internal error'), 'error');
        wc_print_notices();
        /* translators: %1$s: Fisrv error message %2$s: Fisrv error message */
        WC_Fisrv_Logger::error($order, sprintf('Payment failed of checkout %s, retrying on checkout page: (%s - %s)', $order->get_meta('_fisrv_plugin_checkout_id') ?? 'No checkout ID created', $fisrv_error_message ?? 'No error message provided', $fisrv_error_code ?? 'No code provided'));

        return true;
    }

    /**
     * This is called when order is complete on thank you page.
     * Set order status and payment to completed
     *
     * @param string $order_id WC order ID
     */
    public static function order_complete_callback(string $order_id): void
    {
        $order = wc_get_order($order_id);

        if (!$order instanceof WC_Order) {
            return;
        }

        WC_Fisrv_Logger::log($order, __('Payment successful via Fiserv Checkout.', 'fisrv-checkout-for-woocommerce'));

        if (check_admin_referer(Fisrv_Identifiers::FISRV_NONCE->value)) {
            $generic_gateway = new WC_Fisrv_Payment_Generic();

            if (isset($_GET['transaction_approved']) && sanitize_text_field(wp_unslash($_GET['transaction_approved']))) {
                if ($generic_gateway->get_option('autocomplete') === 'no') {
                    $order->update_status('wc-processing', __('Order was paid sucessfully.', 'fisrv-checkout-for-woocommerce'));
                    return;
                }

                $has_completed = $order->payment_complete();
                if ($has_completed) {
                    $order->update_status('wc-completed', __('Order was paid sucessfully and set to completed (auto-complete).', 'fisrv-checkout-for-woocommerce'));
                    WC_Fisrv_Logger::log($order, __('Order auto-completed.', 'fisrv-checkout-for-woocommerce'));
                }
            }
        }
    }
}
