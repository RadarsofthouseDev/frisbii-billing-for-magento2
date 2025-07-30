# Changelog
## Version 1.0.17 on 25 July 2025
- Fixed an error on the Edit Product page.

## Version 1.0.16 on 25 June 2025
- Updated MobilePay Subscription payment method to change based on the quote currency.

## Version 1.0.15 on 23 June 2025
- Rebranded Billwerk+ Optimize to Frisbii Billing
- Fixed an error related to the applyRules() method on Magento 2.4.7 and later.
- Magento Coding Standard

## Version 1.0.13 on 16 May 2025
- Fixed the error when displaying subscriptions with a trial period

## Version 1.0.12 on 2 January 2025
- Removed unsupported payment methods.
- Updated the descriptions of two fields.

## Version 1.0.11 on 2 December 2024
- Supported Billwerk+ Discount: Implemented new options, "Billwerk+ Coupon Code" and "Billwerk+ Discount Handle" in the Magento cart price rule settings.
- Displayed the subscription frequency next to the product price of subscription products.

## Version 1.0.10 on 12 November 2024
- Fixed the error for guzzlehttp/guzzle 7.8.2+
- Added validation for optional fields in subscription plan response to prevent errors when values are empty

## Version 1.0.9 on 15 October 2024
- Fixed the error when saving the module configuration in the store or website scopes.

## Version 1.0.8 on 10 September 2024
- Name change payment method "Vipps Recurring" to "Vipps MobilePay Recurring"
- Warning message added for "MobilePay Subscription" to encourage switch to using "Vipps MobilePay Recurring" instead

## Version 1.0.7 on 19 August 2024
- Fixed the error: "Identical operator === is not used for testing the return value of strpos function"

## Version 1.0.6 on 19 August 2024
- Billwerk+ Add-ons can be matched with Magento simple products.
- Renamed the module to "Billwerk+ Optimize".
- Added an admin notice for changes to the API key configuration.
- Restricted the payment method to "billwerkplus_subscription" when a Billwerk Subscription Product is in the cart.
- Added terms and conditions on the checkout page for Billwerk Subscription Products.

## Version 1.0.5 on 12 June 2024
- Implemented validation to ensure multiple subscription products are not added to the cart.

## Version 1.0.4 on 4 June 2024
- Fixed the error when checkout with multiple addresses

## Version 1.0.3 on 29 May 2024
- Fixed customer error with an empty first name and last name.
- Fixed the email sending error when checkout as a guest and the email does not exist.

## Version 1.0.2 on 28 May 2024
- Removed the return type hint.
- Fixed the error from undefined variables.
- Updated the edit link on the customer grid.

## Version 1.0.1 on 24 May 2024
- Fixed incorrect tax and discount in confirmation email.

## Version 1.0.0 on 23 May 2024
- Initial release with core features and functionality.