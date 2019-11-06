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
                type: 'genpay_credit_card',
                component: 'GenComm_GenPay/js/view/payment/method-renderer/genpay-credit-card'
            }
        );
        return Component.extend({});
    }
);  