/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Radarsofthouse_BillwerkPlusSubscription/payment/billwerkplus_payments',
            },
            redirectAfterPlaceOrder: false,

            getCode: function() {
                return 'reepay_payment';
            },

            afterPlaceOrder: function() {
                window.location.replace(url.build("reepay/standard/redirect"));
            }
        });
    }
);
