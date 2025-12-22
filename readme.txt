=== Fiserv Checkout for WooCommerce === 
Tags: WooCommerce, payment 
Requires at least: 5.4
Tested up to: 6.8
License: GNU General Public License v3.0 
License URI: http://www.gnu.org/licenses/gpl-3.0.html 
Stable tag: 1.1.9
Requires PHP: 8.1
Contributors: ipgpluginsupportfiserv

This is the official Fiserv plugin for WooCommerce powered by IPG's Checkout solution.
 
== Description ==
 
## Why choose Fiserv?
Fiserv provides leading payment processing solutions on a global scale. These solutions encompass the acceptance of various payment methods, efficient management of settlements and funding, the integration of advanced fraud prevention and security measures, and continuous access to dedicated customer support. For further details on Fiserv's offerings, please visit their [website](https://www.fiserv.com/en-em.html).
 
The Fiserv WooCommerce plugin has been developed in collaboration with IPG's Checkout solution. This plugin allows you to efficiently and securely start accepting a wide range of payment methods through your WordPress checkout web shop.
 
## Key features
-             Accept payments using the IPG's Checkout solution 
-             Offer a variety of payment options, including credit cards, digital wallets (such as Google Pay and Apple Pay), and more
-             Allow customers to choose their preferred payment method directly during checkout on your web shop
-             Benefit from regular updates and enhancements provided by Fiserv for the checkout solution
-             Customize the appearance of the payment selection field in your web shop's checkout
-             Ensure a secure payment process that complies with the latest Payment Card Industry Data Security Standard
-   Display Fiserv's checkout page in your preferred language (currently supported: English, German, French, Dutch, Spanish)
 
 
== Installation ==
 
You can install the plugin from the WordPress website. 
Alternatively, you can manually install the plugin by uploading the ZIP file from the WordPress administration environment, or by uploading the plugin using (s)FTP.
To make use of the plugins, please follow the following steps:
â€¢       Revert to the classic WooCommerce checkout and disable WooCommerce Blocks
â€¢       Activate the Payment Method in WooCommerce          
â€¢       Get API Credentials
A detailed description of each task can be found on the [Fiserv Developer Portal](https://docs.fiserv.dev/public/docs/woocommerce).
 
 
== Screenshots ==
1. Activate the Fiserv Checkout plugin in â€˜Pluginsâ€™ > Installed Plugins.
2. Select â€˜WooCommerceâ€™ > â€˜Settingsâ€™ > Payments and click on Fiserv Checkout for WooCommerce (Enabled)   
3. Configure the Fiserv Checkout module (â€˜Manageâ€™ button)
 
== Pre-requisites to install the plug-ins ==
- PHP v8.1 or higher
- WooCommerce v7.8 or higher
- WordPress v5.4 or higher
 
== Upgrade Notice ==
 
== Frequently Asked Questions ==
=  Can't install the plugin =
* Make sure to check compatibility of PHP, WordPress and WooCommerce. Also, ensure to disable Block layout.
=  Plugin UI loaders are stuck or webhook events are not received properly =
* Try switching Permalink type in WordPress admin settings to 'Post name'

== Changelog ==
** 1.1.9 **
* Fix failure page redirect option 
* Improve settings reset flow
* Improve method icon customization
* Update Readme
** 1.1.8 **
* Add support for Bizum
* Improve i18n
** 1.1.7 **
* Improve icon upload
* Use updated Fiserv checkout library
* Log cloud security support case number in case of blocked requests
* Remove brand references
** 1.1.6 **
* Fix issues with parsing shipping totals 
** 1.1.5 **
* Fix issues with JSON parsing 
* Improve log formats
* Remove hosted data creation from payment request
** 1.1.4 **
* Fix issues with order info meta box
** 1.1.3 **
* Add currency support for MUR, AED and AUD
** 1.1.2 **
* Change wording from credit/debit to credit / debit card and remove option to edit gateway payment method name
* Fix description hints in settings
* Clean-up filtered markup tags
* Fix plugin version trace
* Fix refunds for non-generic payments
* Improve theme data palette stability 
** 1.1.1 **
* Fix retrieval of order ID and webhook route
** 1.1.0 **
* Initial version
