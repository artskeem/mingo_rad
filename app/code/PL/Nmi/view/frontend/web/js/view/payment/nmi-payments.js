
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
                type: 'nmi_directpost',
                component: 'PL_Nmi/js/view/payment/method-renderer/nmi-method'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);