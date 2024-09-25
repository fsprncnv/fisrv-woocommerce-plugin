<?php

/**
 * Enum for payment gateway IDs used on WC Gateways.
 * @since 1.1.0
 */
enum FisrvGateway: string
{
    case GENERIC = 'fisrv-gateway-generic';
    case GOOGLEPAY = 'fisrv-google-pay';
    case CREDITCARD = 'fisrv-credit-card';
    case APPLEPAY = 'fisrv-apple-pay';
}
