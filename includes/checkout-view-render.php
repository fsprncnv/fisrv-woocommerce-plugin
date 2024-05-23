<?php

use FiservWoocommercePlugin\CheckoutHandler;

class CheckoutViewRenderer
{
    public static string $default_button_text = 'Place order';

    /**
     * Fill out text fields on billing section on checkout
     * with default values.
     */
    public static function fill_out_fields($fields)
    {
        $fields['billing']['billing_first_name']['default'] = 'Eartha';
        $fields['billing']['billing_last_name']['default'] = 'Kitt';
        $fields['billing']['billing_address_1']['default'] = 'Oskar Schindler Strasse';
        $fields['billing']['billing_postcode']['default'] = '60359';
        $fields['billing']['billing_city']['default'] = 'Frankfurt';
        $fields['billing']['billing_phone']['default'] = '0162345678';
        $fields['billing']['billing_email']['default'] = 'earth.kitt@dev.com';
        return $fields;
    }

    /**
     * Render list of cards containing payment options in 
     * payment method section.
     */
    public static function inject_payment_options()
    {
        $card_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="30%" height="30%" viewBox="0 0 576 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M512 80c8.8 0 16 7.2 16 16v32H48V96c0-8.8 7.2-16 16-16H512zm16 144V416c0 8.8-7.2 16-16 16H64c-8.8 0-16-7.2-16-16V224H528zM64 32C28.7 32 0 60.7 0 96V416c0 35.3 28.7 64 64 64H512c35.3 0 64-28.7 64-64V96c0-35.3-28.7-64-64-64H64zm56 304c-13.3 0-24 10.7-24 24s10.7 24 24 24h48c13.3 0 24-10.7 24-24s-10.7-24-24-24H120zm128 0c-13.3 0-24 10.7-24 24s10.7 24 24 24H360c13.3 0 24-10.7 24-24s-10.7-24-24-24H248z"/></svg>';
        $apay_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="30%" height="30%" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M116.9 158.5c-7.5 8.9-19.5 15.9-31.5 14.9-1.5-12 4.4-24.8 11.3-32.6 7.5-9.1 20.6-15.6 31.3-16.1 1.2 12.4-3.7 24.7-11.1 33.8m10.9 17.2c-17.4-1-32.3 9.9-40.5 9.9-8.4 0-21-9.4-34.8-9.1-17.9 .3-34.5 10.4-43.6 26.5-18.8 32.3-4.9 80 13.3 106.3 8.9 13 19.5 27.3 33.5 26.8 13.3-.5 18.5-8.6 34.5-8.6 16.1 0 20.8 8.6 34.8 8.4 14.5-.3 23.6-13 32.5-26 10.1-14.8 14.3-29.1 14.5-29.9-.3-.3-28-10.9-28.3-42.9-.3-26.8 21.9-39.5 22.9-40.3-12.5-18.6-32-20.6-38.8-21.1m100.4-36.2v194.9h30.3v-66.6h41.9c38.3 0 65.1-26.3 65.1-64.3s-26.4-64-64.1-64h-73.2zm30.3 25.5h34.9c26.3 0 41.3 14 41.3 38.6s-15 38.8-41.4 38.8h-34.8V165zm162.2 170.9c19 0 36.6-9.6 44.6-24.9h.6v23.4h28v-97c0-28.1-22.5-46.3-57.1-46.3-32.1 0-55.9 18.4-56.8 43.6h27.3c2.3-12 13.4-19.9 28.6-19.9 18.5 0 28.9 8.6 28.9 24.5v10.8l-37.8 2.3c-35.1 2.1-54.1 16.5-54.1 41.5 .1 25.2 19.7 42 47.8 42zm8.2-23.1c-16.1 0-26.4-7.8-26.4-19.6 0-12.3 9.9-19.4 28.8-20.5l33.6-2.1v11c0 18.2-15.5 31.2-36 31.2zm102.5 74.6c29.5 0 43.4-11.3 55.5-45.4L640 193h-30.8l-35.6 115.1h-.6L537.4 193h-31.6L557 334.9l-2.8 8.6c-4.6 14.6-12.1 20.3-25.5 20.3-2.4 0-7-.3-8.9-.5v23.4c1.8 .4 9.3 .7 11.6 .7z"/></svg>';
        $gpay_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="30%" height="30%" viewBox="0 0 640 512"><!--!Font Awesome Free 6.5.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path d="M105.7 215v41.3h57.1a49.7 49.7 0 0 1 -21.1 32.6c-9.5 6.6-21.7 10.3-36 10.3-27.6 0-50.9-18.9-59.3-44.2a65.6 65.6 0 0 1 0-41l0 0c8.4-25.5 31.7-44.4 59.3-44.4a56.4 56.4 0 0 1 40.5 16.1L176.5 155a101.2 101.2 0 0 0 -70.8-27.8 105.6 105.6 0 0 0 -94.4 59.1 107.6 107.6 0 0 0 0 96.2v.2a105.4 105.4 0 0 0 94.4 59c28.5 0 52.6-9.5 70-25.9 20-18.6 31.4-46.2 31.4-78.9A133.8 133.8 0 0 0 205.4 215zm389.4-4c-10.1-9.4-23.9-14.1-41.4-14.1-22.5 0-39.3 8.3-50.5 24.9l20.9 13.3q11.5-17 31.3-17a34.1 34.1 0 0 1 22.8 8.8A28.1 28.1 0 0 1 487.8 248v5.5c-9.1-5.1-20.6-7.8-34.6-7.8-16.4 0-29.7 3.9-39.5 11.8s-14.8 18.3-14.8 31.6a39.7 39.7 0 0 0 13.9 31.3c9.3 8.3 21 12.5 34.8 12.5 16.3 0 29.2-7.3 39-21.9h1v17.7h22.6V250C510.3 233.5 505.3 220.3 495.1 211zM475.9 300.3a37.3 37.3 0 0 1 -26.6 11.2A28.6 28.6 0 0 1 431 305.2a19.4 19.4 0 0 1 -7.8-15.6c0-7 3.2-12.8 9.5-17.4s14.5-7 24.1-7C470 265 480.3 268 487.6 273.9 487.6 284.1 483.7 292.9 475.9 300.3zm-93.7-142A55.7 55.7 0 0 0 341.7 142H279.1V328.7H302.7V253.1h39c16 0 29.5-5.4 40.5-15.9 .9-.9 1.8-1.8 2.7-2.7A54.5 54.5 0 0 0 382.3 158.3zm-16.6 62.2a30.7 30.7 0 0 1 -23.3 9.7H302.7V165h39.6a32 32 0 0 1 22.6 9.2A33.2 33.2 0 0 1 365.7 220.5zM614.3 201 577.8 292.7h-.5L539.9 201H514.2L566 320.6l-29.4 64.3H561L640 201z"/></svg>';

        $section = '
            <style>' . Assets::$payment_method_card_css . '</style>
            <script>
                function selectAction(id) {
                    const cards = document.getElementsByClassName("f-card");
                    for (let i = 0; i < cards.length; i++) {
                        cards.item(i).classList.remove("f-card-selected"));
                    }
                    const selected = document.getElementById(id);
                    selected.classList.add("f-card-selected");
                }
            </script>
            <div style="display:flex; flex-direction: column;">
                <h3>Payment Method</h3>
                <div style="display:flex; flex-direction: row; width: 100%; margin-bottom: 1.25em;">
                    <div onclick="selectAction(\'fcard-card\')" id="fcard-card" class="f-card f-card-selected"> ' . $card_icon . ' Card Payment </div>
                    <div onclick="selectAction(\'fcard-apay\')" id="fcard-apay" class="f-card"> ' . $apay_icon . ' Apple Pay </div>
                    <div onclick="selectAction(\'fcard-gpay\')" id="fcard-gpay" class="f-card"> ' . $gpay_icon . ' Google Pay </div>
                </div>
            </div>
        ';

        echo $section;
    }


    /**
     * This block instantiates the HTML markup for the button component.
     * There are two version of the button renderer. One as form (this) and the other as HTML button.
     * 
     * @todo Decide on better component type. Form is needed when workaround is used where button onclick is handled via form POST.
     * If the workaround is removed, this method is obsolete.
     * @see CheckoutHandler::inject_fiserv_checkout_button
     * @see render_checkout_button_as_button
     */
    public static function render_checkout_button_as_form(): void
    {
        $form_post_target = '#';

        $loader_html = '
        <div id="loader-spinner" class="lds-ellipsis hidden"><div></div><div></div><div></div><div></div></div>
        ';

        $button_text = CheckoutHandler::get_request_failed() ? 'Something went wrong. Try again.' : get_option('button_content_id', self::$default_button_text);

        $component =
            '
            <form action="' . $form_post_target . '" method="post">
                <input type="hidden" name="action" value="some_action" />
                ' . self::button_html($button_text, $loader_html) . '
            </form>
        ';

        echo $component;
    }

    /**
     * This block instantiates the HTML markup for the button component.
     * This renders the checkout button as HTML button.
     * 
     * @see render_checkout_button_as_form
     */
    public static function render_checkout_button_as_button(): void
    {
        $loader_html = '
        <div id="loader-spinner" class="lds-ellipsis hidden"><div></div><div></div><div></div><div></div></div>
        ';

        $button_text = CheckoutHandler::get_request_failed() ? 'Something went wrong. Try again.' : get_option('button_content_id', self::$default_button_text);
        echo self::button_html($button_text, $loader_html);
    }

    public static function button_html($button_text, $loader_html)
    {
        return '
            <style>' . Assets::$loader_css . '</style>
            <script>
                function load() { 
                    document.getElementById(\'loader-spinner\').classList.add(\'show\');
                }
            </script>
            <button 
                id="checkout-btn-target"
                onclick="load()"
                type="submit"
                class="checkout-button button alt"
                style="
                    margin: 2rem 2rem 0 0;
                    background-color: #ff6600;
                    font-weight: 700;
                    padding: 1em;
                    font-size: 1.25em;
                    text-align: center; width: 100%;
                    display: flex;
                    justify-content: center;
                    align-items: center;">
                ' . $button_text . '
                ' . $loader_html . '
            </button>
        ';
    }
}
