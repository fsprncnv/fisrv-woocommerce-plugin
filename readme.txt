=== Fisrv Checkout for WooCommerce === 
Tags: WooCommerce, payment 
Requires at least: 5.4
Tested up to: 6.4 
License: GNU General Public License v3.0 
License URI: http://www.gnu.org/licenses/gpl-3.0.html 
Stable tag: 0.0.3
Requires PHP: 8.1

This is the official Fisrv plugin for WooCommerce powered by our Checkout solution.

== Description ==

Fisrv WooCommerce plugin has been developed in combination with our Checkout solution. It will allow you quickly and easily start accepting payments (e.g.: credit cards, Google Pay,Apple Pay & more) from your WordPress checkout’s webshop.

## What does this plugin do

### Features:

- Accept payments via the Fisrv Checkout solution
- Wide range of payment methods e.g.: credit cards, Google Pay, Apple Pay & more
- Allow a payment selection to be performed directly in your checkout’s webshop
- Customize the style of the payment selection field in your webshop’s checkout.
- Secure Transactions
- Localization of the hosted page being part of our Checkout solution

== Pre-requisites to install the plug-ins ==

- PHP v8.1 and above
- WooCommerce 7.8 and above
- Wordpress 5.4 and above

== Installation ==

## Quick installation 
Search for 'Fisrv Checkout' in the Plugin Directory and add plugin. Make sure to deactivate Block layout

## Manual installation 
By uploading ZIP file from WordPress administration environment

1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New’. No files are overwritten. 
2. Select 'Upload plugin' 
3. Select the zip file
4. Continue with step 3 of Installation using (s)FTP

## Disable Blocks 
To revert to the classic WooCommerce checkout and disable WooCommerce Blocks, follow these steps:



1. Log in to your WooCommerce dashboard as an admin
2. Open the Checkout page using the editor under Pages
3. In the Payment options section, select the block
4. On the right side panel click the 'Switch to classic checkout' button and save your changes

## Activate Payment Method in WooCommerce          
1. Log in to your WooCommerce dashboard as an admin. 
2. Navigate to Settings page of WooCommerce
3. Go to the 'Payments' panel
4. Under our Payment Methods (Fisrv), go to 'Manage'
5. Apply your API key and secret in the settings fields respectively. To get API Credentials, follow the link below

## Get API Credentials

To acquire the API key and secret, create an account on our developer portal: https://portal.fiserv.dev/user/registration Copy and paste the acquired credentials into the payments settings under WooCommerce.

Compatibility: WordPress 5.6 or higher

== Frequently Asked Questions ==

= I can't install the plugin = Make sure to check compatibility of PHP, WordPress and WooCommerce. Also, ensure to disable Block layout.

* Contact Fisrv Support

Visit the FAQ:

Contact information:

== Changelog ==

** 0.0.1 **

* Initial version