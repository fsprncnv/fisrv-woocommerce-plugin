=== Fiserv Checkout for Woocommerce ===
Tags: WooCommerce, payment
Requires at least: 5.4
Tested up to: 6.4
License: GNU General Public License v3.0
License URI: http://www.gnu.org/licenses/gpl-3.0.html
Stable tag: 0.0.3
Requires PHP: 8.1

This is the official Fiserv plugin for WooCommerce.

== Description ==

Seamlessly accept credit cards, Google Pay and Apple Pay. 

## What does this plugin do

### Features:

– Seamlessly accept credit cards, Google Pay and Apple Pay.
– Accept payments via Fiserv
– Customize the style of the card field in the checkout
– Capture orders
– Support for manual order creation
– Secure Transactions: Rest assured that all transactions are encrypted and comply with industry standards
– Customize Checkout

== Pre-requisites to install the plug-ins ==

– PHP v8.1 and above
– WooCommerce 7.8 and above
– Wordpress 5.4 and above

== Installation ==

## Quick installation
Search for 'Fiserv Checkout' in the Plugin Directory and add plugin.
Make sure to deactivate Block layout

## Manual installation by uploading ZIP file from WordPress administration environment
1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New’. No files are overwritten.
2. Select 'Upload plugin'.
3. Select the zip file.
4. Continue with step 3 of Installation using (s)FTP.

## Disable Blocks
To revert to the classic WooCommerce checkout and disable WooCommerce Blocks, follow these steps:

1. Log in to your WooCommerce dashboard as an admin.
2. Open the Checkout page using the editor under Pages.
3. In the Payment options section, select the block.
4. On the right side panel click the 'Switch to classic checkout' button and save your changes.

## Activate Payment Gateway
1. Log in to your WooCommerce dashboard as an admin.
2. Navigate to Settings page of WooCommerce
3. Go to the 'Payments' panel
4. Under our Payment Methods (Fiserv), go to 'Manage'
5. Apply your API key and secret in the settings fields respectively

## Get API Credentials
To acquire the API key and secret, create an account on our developer portal: https://portal.fiserv.dev/user/registration
Copy and paste the acquired credentials into the payments settings under WooCommerce.

Compatibility: WordPress 5.6 or higher

== Screenshots ==

1. Checkout page: Fiserv payment methods

== Frequently Asked Questions ==

= I can't install the plugin =
Make sure to check compatibility of PHP, Wordpress and WooCommerce.
Also, ensure to disable Block layout.

* Contact Fiserv Support

Visit the FAQ:

Contact information:


== Changelog ==

** 0.0.1 **

* Initial version

** 0.0.2 **

* Improve web hook handling 
* Improve stability and quality of request client
* Pass detailed order summary from shop to checkout

** 0.0.3 **

* Improve code quality, type safety
* Add dedicated payment gateways per supported APM
