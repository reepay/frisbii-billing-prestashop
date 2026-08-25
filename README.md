# Frisbii Subscription
Lastest version: 1.1.2

Frisbii for Prestashop for 8 and 9, connects your store to Frisbii's powerful recurring revenue management platform—bringing together subscriptions, automated billing, and payments in one seamless integration.

## Information
Compatible with Prestashop versions: 8, 9

## With this extension, you can:
* Sell subscription products and manage recurring payments directly from Prestashop
* Automate invoicing, renewals, and dunning workflows
* Accept all major local and international payment methods - including Dankort, VISA Dankort, Mastercard, VISA, MobilePay, ViaBill, and more
* Leverage flexible billing intervals, free trials, and discount logic
* Track and manage customer lifecycles with full visibility via the Frisbii platform

Whether you're running a subscription-first business or offering a mix of one-time and recurring purchases, Frisbii gives you the tools to scale your revenue and streamline operations.

## Installation
1. Download the .zip file from https://github.com/reepay/frisbii-billing-prestashop/releases
2. Log in to your Prestashop Administrator panel 
3. Go to **Modules** -> **Module Manager** -> "Upload the module"
4. Once installed, go to **Module Manager** Find "Frisbii Subscription" -> Click "Configure"
5. Configure the plugin settings (API key, etc.) and click **Save**

## Requirements
 - with Prestashop versions >= 8.x (also might work on lower versions but has not been tested)
 - php >= 8.0

## Support
You can create issues on our repository. In case of specific problems with your account, please contact support@frisbii.com

## Changelog
v 1.1.2
- [Feature] - Subscription panel on the product page now auto-expands when a Frisbii plan is already assigned.
- [Fix] - Increased max supported Prestashop version from 1.7.99 to 9.99.99, fixing install failure on 9.1.4.
- [Fix] - Removed unrelated settings (API key, status, self-service, debug mode, Save button) from the embedded config form on the product page.
- [Fix] - Fixed broken Frisbii Subscription layout styling on the product edit page.
- [Fix] - Fixed subscription plans failing to load from Frisbii on the product form.
- [Fix] - Fixed "array offset on int" warning in getContent.php when Frisbii Billing and Frisbii Pay are both enabled, caused by hardcoded language variables conflicting with HelperForm's own.
- [Docs] - Updated Readme with detailed step-by-step installation guide for Prestashop.