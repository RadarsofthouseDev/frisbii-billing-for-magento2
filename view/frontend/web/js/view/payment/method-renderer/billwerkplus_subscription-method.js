define(
    [
        'Magento_Checkout/js/view/payment/default',
        'mage/url'
    ],
    function (Component, url) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Radarsofthouse_BillwerkPlusSubscription/payment/form/subscription'
            },
            redirectAfterPlaceOrder: false,

            getCode: function() {
                return 'billwerkplus_subscription';
            },

            afterPlaceOrder: function() {
                window.location.replace(url.build("billwerkplussubscription/standard/redirect"));
            }
        });
    }
);
