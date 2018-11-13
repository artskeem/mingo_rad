/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Checkout/js/view/payment/default',
        'jquery',
        "mage/validation"
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'Greenmoney_Greenmoney/payment/greenmoney',
                routingnumber: '',
                accountnumber: ''
            },
            initObservable: function () {
                this._super().observe('routingnumber');
                this._super().observe('accountnumber');
                return this;
            },
            getData: function () {
                return {
                    "method": this.item.method,
                    "additional_data":{'accountnumber':this.accountnumber(),'routingnumber':this.routingnumber()}
                };

            },
            validate: function () {
                var form = 'form[data-role=greenmoney-form]';
                return $(form).validation() && $(form).validation('isValid');
            }
        });
    }
);
