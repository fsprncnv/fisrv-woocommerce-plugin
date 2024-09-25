<?php

/**
 * Enum for payment gateway IDs used on WC Gateways.
 * @since 1.1.0
 */
enum Fisrv_Identifiers: string
{
    case GATEWAY_GENERIC = 'fisrv-gateway-generic';
    case GATEWAY_GOOGLEPAY = 'fisrv-google-pay';
    case GATEWAY_CREDITCARD = 'fisrv-credit-card';
    case GATEWAY_APPLEPAY = 'fisrv-apple-pay';
    case FISRV_NONCE = 'fisrv-nonce';
}
