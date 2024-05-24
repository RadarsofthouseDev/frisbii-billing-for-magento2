/*browser:true*/
/*global define*/
define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'billwerkplus_payments',
                component: 'Radarsofthouse_BillwerkPlusSubscription/js/view/payment/method-renderer/billwerkplus_payments'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);
