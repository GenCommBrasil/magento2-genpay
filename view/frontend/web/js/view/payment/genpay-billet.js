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
                type: 'genpay_billet',
                component: 'GenComm_GenPay/js/view/payment/method-renderer/genpay-billet'
            }
        );
        return Component.extend({});
    }
);  