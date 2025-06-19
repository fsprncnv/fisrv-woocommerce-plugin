<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enum for payment gateway IDs used on WC Gateways.
 *
 * @since 1.1.0
 */
enum Fisrv_Identifiers: string
{
    case GATEWAY_GENERIC = 'fiserv-gateway-generic';
    case GATEWAY_GOOGLEPAY = 'fiserv-google-pay';
    case GATEWAY_CREDITCARD = 'fiserv-credit-card';
    case GATEWAY_APPLEPAY = 'fiserv-apple-pay';
    case FISRV_NONCE = 'fiserv-nonce';
}
