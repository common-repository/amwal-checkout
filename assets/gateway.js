/**
 * Handle Payment Gateway Selector.
 *
 * event: payment_method_selected
 */

(function ($) {
  'use strict'

  const amwalGateway = {
    /**
    * Hide the checkout button if the Amwal gateway is selected.
    *
    * @returns {void}
    */
    maybeHideCheckout: function () {
      const selectedPaymentMethod = $('.woocommerce-checkout input[name="payment_method"]:checked').attr('id')
      let placeOrder = $('input[type="submit"]')
      if (!placeOrder.length) {
        placeOrder = $('button[type="submit"]')
      }
      const checkoutButton = $('div.amwal-container')
      if (selectedPaymentMethod === 'payment_method_Amwal' && placeOrder) {
        placeOrder.hide()
        checkoutButton.show()
      } else {
        placeOrder.show()
        checkoutButton.hide()
      }
    }
  }

  $(document).ready(function () {
    amwalGateway.maybeHideCheckout()
  })

  $(document.body).on('payment_method_selected', function () {
    amwalGateway.maybeHideCheckout()
  })

  $(document.body).on('updated_checkout', function () {
    amwalGateway.maybeHideCheckout()
  })
})(jQuery)
