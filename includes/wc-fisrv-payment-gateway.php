<?php

use Fisrv\Models\PreSelectedPaymentMethod;

/**
 * Custom Woocommerce payment gateway.
 *
 * @package    WooCommerce
 * @category   Payment Gateways
 * @author     fisrv
 * @since      1.0.0
 */
abstract class WC_Fisrv_Payment_Gateway extends WC_Payment_Gateway {

	protected PreSelectedPaymentMethod $selected_method;

	protected string $default_title = '';

	protected string $default_description = '';

	public function __construct() {
		$this->has_fields = false;
		$this->init_form_fields();
		$this->init_settings();
		$this->init_properties();

		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Initialize properties from options
	 */
	protected function init_properties(): void {
		$this->enabled     = $this->get_option( 'enabled' );
		$this->title       = $this->get_option( 'title' );
		$this->description = $this->get_option( 'description' );
		$this->icon        = $this->get_option( 'icon' );
	}

	/**
	 * Initialize form text fields on gateway options page
	 */
	public function init_form_fields(): void {
		$this->form_fields = array(
			'enabled'     => array(
				'title'   => 'Enable/Disable',
				'type'    => 'checkbox',
				'label'   => __( 'Enable Payment Gateway', 'fisrv-checkout-for-woocommerce' ),
				'default' => 'yes',
			),
			'api_key'     => array(
				'title'       => 'API Key',
				'type'        => 'text',
				'description' => __( 'Acquire API Key from Developer Portal', 'fisrv-checkout-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'api_secret'  => array(
				'title'       => 'API Secret',
				'type'        => 'password',
				'description' => __( 'Acquire API Secret from Developer Portal', 'fisrv-checkout-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'store_id'    => array(
				'title'       => 'Store ID',
				'type'        => 'text',
				'description' => __( 'Your Store ID for Checkout', 'fisrv-checkout-for-woocommerce' ),
				'desc_tip'    => true,
			),
			'title'       => array(
				'title'       => 'Gateway Name',
				'type'        => 'text',
				'description' => __( 'Custom name of gateway', 'fisrv-checkout-for-woocommerce' ),
				'default'     => $this->default_title,
				'desc_tip'    => true,
			),
			'description' => array(
				'title'       => 'Gateway Description',
				'type'        => 'text',
				'description' => __( 'Custom description of gateway', 'fisrv-checkout-for-woocommerce' ),
				'default'     => $this->default_description,
				'desc_tip'    => true,
			),
			'icon'        => array(
				'title'       => 'Gateway Icon',
				'type'        => 'text',
				'description' => __( 'Link of image asset', 'fisrv-checkout-for-woocommerce' ),
				'default'     => '',
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			throw new Exception( __( 'Processing payment failed. Order is invalid.', 'fisrv-checkout-for-woocommerce' ) );
		}

		$checkout_link = WC_Fisrv_Checkout_Handler::create_checkout_link( $order, $this->selected_method, $this );

		return array(
			'result'   => 'success',
			'redirect' => $checkout_link,
		);
	}
}
