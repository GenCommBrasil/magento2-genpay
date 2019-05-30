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
                type: 'rakutenpay_credit_card',
                component: 'Rakuten_RakutenPay/js/view/payment/method-renderer/rakutenpay-credit-card'
            }
        );
        return Component.extend({});
    }
);  