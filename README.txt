=== Amwal Checkout  ===
Contributors: amwalcheckout
Tags: amwal, checkout, woocommerce, woocommerce payment, woocommerce checkout, one click checkout, 1click checkout, amwal checkout
Requires at least: 6.0.2
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 1.0.61
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html


== Description ==

Amwal offers a 1-click biometric checkout experience that increases your sales conversions, decreases cart abandonment, and reduces fraud.
The experience works by combining identity-verification, fraud prevention and payment processing all through a single API.

== Installation ==
You can find a complete installation guide in our [developer documentation page](https://amwal.readme.io/).


== Frequently Asked Questions ==

= Do you share the shopper's email address with the merchant?
When the shopper completes a checkout, all contact & shipping info is shared with the merchant.

= I need help installing Simpler Checkout to my website =
Please email us at dev@amwal.tech and we'll guide you through the process as soon as possible.

= I have another question not listed here = 
You can find a complete list of [Frequently Asked Questions](https://www.amwal.tech/faqs).

== Changelog ==
== 1.0.61 ==
* remove status on-hold for unpaid orders

== 1.0.60 ==
* add option to make street 2 in address required or optional

== 1.0.59 ==
* add discount ribbon to button in case of discount enabled
* performance improvements
* redirect guest to thank you page after payment

== 1.0.58 ==
* hotfix

== 1.0.57 ==
* bug fixes
* support discounts from 'Woo Discount Rules' plugin
* add option to save the address in the virtual products

== 1.0.56 ==
* bug fixes

= 1.0.54 =
* add extra fields to the shipping address options in amwal settings

= 1.0.53 =
* fix css overriding
* enhance report error
* fix amwal promo code bug

= 1.0.52 =
* support Wordpress version 6.4
* button behaviour enhancements
* add promo snippet
* add extra style filed
* support Arabic language

= 1.0.51 =
* cleanup
* use cdn instead of building packages

= 1.0.50 =
* add cron job to the settings

= 1.0.49 =
* fix bugs

= 1.0.48 =
* cron job to check transaction status
* fix bugs
* discount and coupons
* enhance admin settings

= 1.0.47 =
* fix bugs
* add custom action for check transaction status
* handle decimal rounding

= 1.0.47 =
* fix bugs
* add custom action for check transaction status

= 1.0.46 =
* change payment gateway name to Quick Checkout
* performance improvements

= 1.0.45 =
* fix bugs

= 1.0.44 =
* fix bugs
* postcode optional countries
* initial user info ( firstname, lastname, phone number, address )
* exclude product (categories / tags) from installment options
* extra js field in setting to handle extra js events

= 1.0.43 =
* fix bugs

= 1.0.42 =
* adding failure reason 

= 1.0.41 =
* add failure reason to the order notes

= 1.0.41 =
* add exclude shipping method option
* add installment options url to redirect users to checkout page in case of installments

= 1.0.40 =
* amwal-checkout-button version 0.0.46

= 1.0.39 =
* Shortcode for product / checkout button
* add supported payment brands
* add description to the payment gateway
* amwal-checkout-button version 0.0.45
* refund
* add exclude shipping methods to settings

= 1.0.38 =
* fix bugs
* amwal-checkout-button version 0.0.43

= 1.0.37 =
* amwal-checkout-button version 0.0.41
* assets refactor
* fix bugs

= 1.0.36 =
* amwal-checkout-button version 0.0.40
* added allowed cities
* add loading animation

= 1.0.35 =
* amwal-checkout-button version 0.0.39
* fix tax calculations
* fix product checkout
* add order details endpoint
* fix cart checkout
* fix shipping for virtual products
* fix loading button

= 1.0.34=
* fix mini cart spinning
* fix shipping method saving

= 1.0.33=
* hot fix loading javascript in product page

= 1.0.32=
* fix cancel order in checkout page

= 1.0.31=
* auto render button by default
* stop reload after button dismissed
* send order content to the backend

= 1.0.30=
* refactor

= 1.0.29=
* fix defer amwalCheckoutInit script
* amwal-checkout-button version 0.0.37
* order delete with validations


= 1.0.28=
* fix amwal-checkout javascript

= 1.0.27=
* fix shipping methods on update address

= 1.0.26=
* fix bugs
* amwal-checkout-button version 0.0.36

= 1.0.25=
* send meta data by ref_id
* amwal-checkout-button version 0.0.35

= 1.0.24=
* handle custom shipping states
* fix bugs

= 1.0.23=
* report errors

= 1.0.22=
* handle bundles

= 1.0.21 =
* fix bugs

= 1.0.20 =
* fix style bugs
* fix checkout calculations

= 1.0.19 =
* fix custom fee

= 1.0.18 =
* fix bugs
* amwal-checkout-button version 0.0.34

= 1.0.17 =
* fix test mode
* amwal-checkout-button version 0.0.30

= 1.0.16 =
* amwal-checkout-button version 0.0.29

= 1.0.15 =
* fix cart shipping methods
* add hide shipping methods in plugin settings
* remove amwal-checkout-button from checkout page

= 1.0.14 =
* amwal-checkout-button version 0.0.28

= 1.0.13 =
* fix bugs with PHP version 7.4

= 1.0.12 =
* amwal payment gateway placed in order-pay
* amwal-checkout-button version 0.0.27
* bug fixes

= 1.0.11 =
* fix shipping calculations in cart

= 1.0.10 =
* bug fixes
* amwal-checkout-button version 0.0.26

= 1.0.9 =
* enable debug
* remove deprecated functions

= 1.0.8 =
* add Amwal as a payment gateway in checkout page

= 1.0.7 =
* amwal-checkout-button version 0.0.24

= 1.0.6 =
* add coupons
* adding validations to checkout form
* amwal-checkout-button version 0.0.23

= 1.0.5 =
* add phone number in order billing / shipping
* display taxes in the first step of the checkout process
* amwal-checkout-button version 0.0.22

= 1.0.4 =
* fix amwal-checkout-button css overlapping
* amwal-checkout-button version 0.0.21

= 1.0.3 =
* amwal-checkout-button version 0.0.20

= 1.0.2 =
* Peroformance improvements and bug fixes

= 1.0.1 =
* Use a Web Component to render the amwal-checkout-button
* Add separate option to control if checkout button gets rendered in the cart view
* Add configuration option to hide the product page button if cart contains at least one item
* Add product attributes to amwal integration for variable products
* Optionally include customer email during quotation
* Using web component instantate, hosted at https://www.npmjs.com/package/amwal-checkout-button.

== Upgrade Notice ==

= 1.0.0 =
This version fixes a security related bug.  Upgrade immediately.
