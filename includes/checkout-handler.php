<?php

namespace FiservWoocommercePlugin;

class checkout_handler
{
    /**
     * Get cart data from WC stub to be served to Checkout Solution.
     */
    public function init_state(): array
    {
        $cart = WC()->cart->get_cart();
        $data = [];

        foreach ($cart as $cart_item) {
            $data['item_name'] = $cart_item['data']->get_title();
            $data['quantity'] = $cart_item['quantity'];
            $data['price'] = $cart_item['data']->get_price();
        }

        print_r($data);

        return $data;
    }
}
