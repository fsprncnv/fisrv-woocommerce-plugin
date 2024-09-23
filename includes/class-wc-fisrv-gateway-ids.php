<?php

enum FisrvGateway: string
{
    case GENERIC = 'fisrv-gateway-generic';
    case GOOGLEPAY = 'fisrv-google-pay';
    case CREDITCARD = 'fisrv-credit-card';
    case APPLEPAY = 'fisrv-apple-pay';
}
