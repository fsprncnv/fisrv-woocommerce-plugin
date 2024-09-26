=== Fiserv Checkout for WooCommerce === 
Tags: WooCommerce, payment 
Requires at least: 5.4
Tested up to: 6.6
License: GNU General Public License v3.0 
License URI: http://www.gnu.org/licenses/gpl-3.0.html 
Stable tag: 1.1.0
Requires PHP: 8.1
Contributors: fiserv

Fiserv Checkout for WooCommerce
By Fiserv
This is the official Fiserv plugin for WooCommerce powered by our Checkout solution.

== Description ==

## Why to choose Fiserv?

Fiserv offers top-notch payment processing solutions for businesses, which include accepting multiple payment methods, managing settlement and funding, implementing robust fraud prevention and security measures, and providing round-the-clock customer support. For more information on Fiserv, please visit our [page]( https://www.fiserv.com/en-em.html).

The Fiserv WooCommerce plugin has been created in conjunction with our Checkout solution. This plugin will enable you to promptly and securely begin accepting various payment methods through your WordPress checkout webshop.

## Key features
- Accept payments through the Fiserv Checkout solution 
- Offer an array of payment options including credit cards, digital wallets (such as Google Pay and Apple Pay), and many more
- Provide customers with the ability to select their preferred payment method directly within your webshop's checkout 
- Make use of a checkout solution that always incorporates the latest updates by Fiserv
- Personalize the appearance of the payment selection field in your webshop's checkout
- Rely on a secure payment process that comply with the latest Payment Card Industry Data Security Standard
- Display Fiserv’s checkout page in your local language (currently supported: English, German, French, Dutch, Spanish)

== Installation ==

You can simply install the plugin from the WordPress website. Alternatively, you can manually install the plugin by uploading ZIP file from WordPress administration environment or by uploading the plugin using (s)FTP.
To make use of the plugins these tasks need to be completed:

- Revert to the classic WooCommerce checkout and disable WooCommerce Blocks
- Activate Payment Method in WooCommerce          
- Get API Credentials

A detailed description of each task can be found in Fiserv’s Developer Portal.
For more useful information you can visit the Fiserv [forum](https://wordpress.org/support/plugin/fiserv-checkout-for-woocommerce/)

### Steps for manual installation
If you decide to install the Fiserv Checkout for WooCommerce plugin manually, you can upload the ZIP file from WordPress administration environment. For this approach please follow the following steps: 

1. Go to your WordPress admin environment. Upload the ZIP file to your WordPress installation by clicking on ‘Plugins’ > ‘Add New Plugin’. No files are overwritten.
2. Select 'Upload Plugin
3. Select the ZIP file that is stored locally
4. Activate the plugin

Alternatively, you can manually upload the plugin using (s)FTP. For such, the ZIP file into the ‘wp-content/plugins’ folder of your WordPress installation. You can use any sFTP or SCP program.

## Disable Blocks
To revert to the classic WooCommerce checkout and disable WooCommerce Blocks, follow these steps:

1. Log in to your WooCommerce dashboard as an admin
2. Open the Checkout page using the editor under Pages
3. In the Payment options section, select the block
4. On the right-side panel click the 'Switch to classic checkout' button and save your changes

## Configuration of Payment Methods in WooCommerce
1. Log in to your WooCommerce Dashboard as an admin.
2. Navigate to ‘WooCommerce’ in the menu and then to ‘Settings’.
3. Click on ‘Payments’ in the top menu.
4. Click on the 'Manage' button next to the generic payment option or choose the available payment methods offered by Fiserv. By choosing the generic payment option, your customers will be redirected to the Fiserv Checkout solution where they can choose the  preferred payment method.
By selecting one of the available payment methods your customer will be able to pre-select a specific payment method already during the checkout page of your webshop.
5. Apply your API key and secret in the settings fields respectively. To get API Credentials, follow the link below presented in the next section.

## Get API Credentials
To acquire the API key and secret, create an account on our [developer portal](https://portal.fiserv.dev/user/registration). 
Copy and paste the acquired credentials into the ‘Payments’ settings under WooCommerce after clicking on the ‘Manage’ button.

== Screenshots ==
1. Activate the Fiserv Checkout plugin in ‘Plugins’ > Installed Plugins.
2. Select ‘WooCommerce’ > ‘Settings’ > Payments and click on Fiserv Checkout for WooCommerce (Enabled).   
3. Configure the Fiserv Checkout module (‘Manage’ button)

== Pre-requisites to install the plug-ins ==
- PHP v8.1 or higher
- WooCommerce v7.8 or higher
- WordPress v5.4 or higher

== Upgrade Notice ==

== Frequently Asked Questions ==
=  Can't install the plugin =
* Make sure to check compatibility of PHP, WordPress and WooCommerce. Also, ensure to disable Block layout.

== Changelog ==
** 1.1.0 **
* Initial version