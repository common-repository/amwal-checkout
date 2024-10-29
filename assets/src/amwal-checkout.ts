import { type AmwalCheckoutButtonCustomEvent } from 'amwal-checkout-button'
import { type AmwalCheckoutStatus, type AmwalDismissalStatus, type IAddress, type ITransactionDetails } from 'amwal-checkout-button/dist/types/components/amwal-checkout-button/amwal-checkout-button'

declare global {
  interface HTMLElementEventMap {
    amwalCheckoutSuccess: AmwalCheckoutButtonCustomEvent<AmwalCheckoutStatus>
    updateOrderOnPaymentsuccess: AmwalCheckoutButtonCustomEvent<AmwalCheckoutStatus>
    amwalAddressUpdate: AmwalCheckoutButtonCustomEvent<IAddress>
    amwalDismissed: AmwalCheckoutButtonCustomEvent<AmwalDismissalStatus>
    amwalPrePayTrigger: AmwalCheckoutButtonCustomEvent<ITransactionDetails>
  }
  const AMWALWC_CONSTANTS: {
    transactionDetailsURL: string
    pluginVersion: string
    extraEvents: string
    siteURL: string
  }
}

function disableAmwalButtons (): void {
  const buttons = document.querySelectorAll('amwal-checkout-button')
  buttons.forEach((button) => {
    button.disabled = true
  })
}

const enableAmwalButton = (button: HTMLAmwalCheckoutButtonElement): void => {
  const checkout = new AmwalCheckout(button)
  checkout.registerEventListeners()
  if (checkout.position === 'amwal-product-page') {
    const variationForm = jQuery('form.variations_form')
    variationForm?.on('show_variation', function () {
      button.disabled = false
    })
    variationForm?.on('hide_variation', function () {
      button.disabled = true
    })
  }
}

function enableAmwalButtons (): void {
  const buttons = document.querySelectorAll('amwal-checkout-button')
  buttons.forEach(enableAmwalButton)
}

document.addEventListener('DOMContentLoaded', function () {
  enableAmwalButtons()
  // https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/legacy/js/frontend/cart-fragments.js#L150
})
// This change if to solve the issue of the button not being enabled after the page is loaded, in some scripts DOMContentLoaded is removed before the button is loaded
const documentBody = jQuery(document.body)
documentBody.on(AMWALWC_CONSTANTS.extraEvents, enableAmwalButtons)
documentBody.on('added_to_cart', () => {
  document.querySelectorAll('.amwal-product-page amwal-checkout-button').forEach(e => { e.remove() })
})

window.onbeforeunload = disableAmwalButtons

class AmwalCheckout {
  handlingPrePayTrigger = false
  handlingCheckoutPayTrigger = false
  busyUpdatingOrder = false
  receivedSuccess = false
  checkoutButton!: HTMLAmwalCheckoutButtonElement
  buttonId: string | null
  position: string | null
  orderContent: any
  genOrderId?: string
  backendURL: string | null
  pre_existing_order_id?: string
  buttonVersion: string | null

  constructor (button: HTMLAmwalCheckoutButtonElement) {
    this.checkoutButton = button
    this.buttonId = this.checkoutButton.getAttribute('ref-id')
    this.position = this.checkoutButton.getAttribute('position')
    this.orderContent = undefined
    this.busyUpdatingOrder = false
    this.receivedSuccess = false
    this.backendURL = AMWALWC_CONSTANTS.transactionDetailsURL
    this.pre_existing_order_id = this.checkoutButton?.getAttribute('checkout-order-id') ?? undefined
    this.buttonVersion = AMWALWC_CONSTANTS.pluginVersion
  }

  registerEventListeners (): void {
    const eventsRegistered = this.checkoutButton.getAttribute('events-registered')
    if (eventsRegistered) {
      return
    }
    const amwalCheckoutSuccess = (ev: AmwalCheckoutButtonCustomEvent<AmwalCheckoutStatus>): void => {
      this.receivedSuccess = true
      if (this.busyUpdatingOrder) {
        return
      }
      try {
        if (ev.detail.orderId && this.genOrderId) {
          window.location.href = AMWALWC_CONSTANTS.siteURL + '?amwal_order_created=' + this.genOrderId
        }
      } catch (error: unknown) {
        throw new Error(error?.toString())
      }
    }

    const updateOrderOnPaymentsuccess = (ev: AmwalCheckoutButtonCustomEvent<AmwalCheckoutStatus>): void => {
      this.busyUpdatingOrder = true
      if (ev.detail.orderId) {
        this.completeOrder()
          .then(() => {
            this.busyUpdatingOrder = false
            if (this.receivedSuccess && this.genOrderId) {
              window.location.href = AMWALWC_CONSTANTS.siteURL + '?amwal_order_created=' + this.genOrderId
            }
          })
          .catch(err => { throw new Error(err?.toString()) })
      }
    }

    const amwalAddressUpdate = (ev: AmwalCheckoutButtonCustomEvent<IAddress>): void => {
      const urlForUpdate = AMWALWC_CONSTANTS.siteURL + '/wp-json/wc/amwal/v2/checkout/update'
      postData(urlForUpdate, {
        amwal_cart_id: this.buttonId,
        address_details: ev.detail
      })
        .then(async res => await res.json())
        .then(data => {
          this.checkoutButton.setAttribute('discount', data.discount)
          this.checkoutButton.setAttribute('taxes', data.taxes)
          this.checkoutButton.setAttribute('amount', data.amount)
          this.checkoutButton.setAttribute('shipping-methods', JSON.stringify(data.shipping_methods))
          this.checkoutButton.shippingMethods = data.shipping_methods
          this.checkoutButton.dispatchEvent(new Event('amwalAddressAck'))
        })
        .catch(err => {
          this.checkoutButton.dispatchEvent(new CustomEvent('amwalAddressTriggerError', {
            detail: {
              description: 'Error in getting shipping methods',
              error: err?.toString()
            }
          }))
        })
    }

    const amwalDismissed = (event: AmwalCheckoutButtonCustomEvent<AmwalDismissalStatus>): void => {
      if (event.detail.orderId) {
        if (this.genOrderId) {
          this.completeOrder(event.detail.paymentSuccessful)
            .catch(err => { throw new Error(err?.toString()) })
        }
        this.checkoutButton.disabled = true
        this.cancelOrder(event.detail.orderId)
          .finally(() => {
            this.checkoutButton.disabled = false
            this.genOrderId = undefined
          })
      }
    }

    const amwalPrePayTrigger = (ev: AmwalCheckoutButtonCustomEvent<ITransactionDetails>): void => {
      if (this.handlingPrePayTrigger) return
      this.handlingPrePayTrigger = true
      if (this.position === 'amwal-before-checkout-form' && !this.pre_existing_order_id) {
        throw new Error('Unexpected error occurred while creating order')
      }
      const url = AMWALWC_CONSTANTS.siteURL + '/wp-json/wc/amwal/v2/order/create'
      postData(url, {
        amwal_cart_id: this.buttonId,
        transaction_details: ev.detail,
        amwal_order_id: this.pre_existing_order_id
      })
        .then(async res => {
          const data = await res.json()
          console.log('amwalPrePayTrigger', res, data)
          if (!res.ok) {
            throw new Error('Internal server error while creating order')
          }
          return data
        })
        .then(data => {
          this.genOrderId = data.order_id
          this.checkoutButton.setAttribute('amount', data.amount)
          this.checkoutButton.dispatchEvent(new CustomEvent('amwalPrePayTriggerAck', {
            detail: {
              order_total_amount: data.amount,
              order_id: data.order_id,
              card_bin_additional_discount_message: data.card_bin_additional_discount_message ?? '',
              card_bin_additional_discount: data.card_bin_additional_discount ?? 0,
              old_amount: data.old_amount ?? 0
            }
          }))
        }).catch(err => {
          this.checkoutButton.dispatchEvent(new CustomEvent('amwalPrePayTriggerError', {
            detail: {
              description: err?.toString()
            }
          }))
        }).finally(() => {
          this.handlingPrePayTrigger = false
        })
    }

    const amwalPreCheckoutTrigger = (ev: Event): void => {
      if (this.handlingCheckoutPayTrigger) return
      this.handlingCheckoutPayTrigger = true

      const createCheckoutPromise = this.position === 'amwal-product-page'
        ? submitAddToCart().then(async () => { await this.createCheckout() })
        : this.createCheckout()

      createCheckoutPromise.then(() => {
        const amount = this.checkoutButton?.getAttribute('amount')
        if (this.orderContent && this.checkoutButton && amount && parseFloat(amount) > 0) {
          this.checkoutButton.dispatchEvent(new CustomEvent('amwalPreCheckoutTriggerAck', {
            detail: {
              order_position: this.position,
              order_content: JSON.stringify(this.orderContent),
              plugin_version: this.buttonVersion ? 'woocommerce ' + this.buttonVersion : undefined
            }
          }))
        } else {
          throw new Error('Cannot Proceed With Amount Of Zero')
        }
      }).catch(err => {
        this.checkoutButton.dispatchEvent(new CustomEvent('amwalPreCheckoutTriggerError', {
          detail: {
            description: err?.toString()
          }
        }))
      }).finally(() => {
        this.handlingCheckoutPayTrigger = false
      })
    }

    this.checkoutButton.addEventListener('amwalCheckoutSuccess', amwalCheckoutSuccess, false)
    this.checkoutButton.addEventListener('updateOrderOnPaymentsuccess', updateOrderOnPaymentsuccess, false)
    this.checkoutButton.addEventListener('amwalAddressUpdate', amwalAddressUpdate, false)
    this.checkoutButton.addEventListener('amwalDismissed', amwalDismissed, false)
    this.checkoutButton.addEventListener('amwalPrePayTrigger', amwalPrePayTrigger, false)
    this.checkoutButton.addEventListener('amwalPreCheckoutTrigger', amwalPreCheckoutTrigger, false)

    this.checkoutButton.setAttribute('events-registered', 'true')
  }

  async createCheckout (): Promise<void> {
    const url = AMWALWC_CONSTANTS.siteURL + '/wp-json/wc/amwal/v2/checkout/create'
    const res = await postData(url, {
      amwal_cart_id: this.buttonId,
      pre_existing_order_id: this.pre_existing_order_id
    })
    const data = await res.json()
    this.checkoutButton.setAttribute('taxes', data.taxes)
    this.checkoutButton.setAttribute('amount', data.amount)
    this.checkoutButton.setAttribute('discount', data.discount)
    // this.checkoutButton.setAttribute('shipping-methods', JSON.stringify(data.shipping_methods))
    // this.checkoutButton.shippingMethods = data.shipping_methods
    this.orderContent = data.order_content
  }

  async cancelOrder (amwalTransactionID: string): Promise<Response | undefined> {
    if (this.position === 'amwal-product-page') {
      const url = AMWALWC_CONSTANTS.siteURL + '/wp-json/wc/amwal/v2/orders/cancel'
      return await postData(url, {
        amwal_cart_id: this.buttonId,
        amwal_transaction_id: amwalTransactionID
      })
    }
  }

  async completeOrder (assertTransactionStatus: boolean = true): Promise<Response> {
    const orderId = this.checkoutButton?.getAttribute('checkout-order-id') ?? this.genOrderId ?? ''
    const url = AMWALWC_CONSTANTS.siteURL + '/wp-json/wc/amwal/v2/order/complete'
    return await postData(url, {
      amwal_cart_id: this.buttonId,
      order_id: orderId,
      assert_transaction_status: assertTransactionStatus
    })
  }
}

const postData = async function (url: string, data: any): Promise<Response> {
  return await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(data) })
}

const submitAddToCart = async (): Promise<void> => {
  const cartForm = document.querySelector('form.cart')
  if (cartForm == null) throw new Error('Product form not found')
  const formURL = cartForm.getAttribute('action') ?? window.location.href
  if (!formURL) throw new Error('Product form URL not found')
  const formSubmitButton = document.querySelector<HTMLButtonElement>("form.cart button[type='submit']")
  if (!formSubmitButton) throw new Error('Submit button not found')
  const productId = formSubmitButton.value
  const cartFormData = new FormData(cartForm as HTMLFormElement)
  const formData = { 'add-to-cart': productId, ...Object.fromEntries(cartFormData.entries()) }
  await addToCart(formURL, formData)
}

const addToCart = async (formURL: string, formData: Record<string, string>): Promise<void> => {
  await new Promise<void>((resolve, reject) => {
    void jQuery.ajax({
      url: formURL,
      type: 'POST',
      data: formData,
      success: function (response) {
        if (response?.indexOf('woocommerce-error') > 0) {
          const responseDOM = new DOMParser().parseFromString(response, 'text/html')
          const list = responseDOM.querySelectorAll('.woocommerce-error li')
          const errorList: string[] = []
          list.forEach((errorEl) => {
            errorList.push(errorEl.textContent?.trim() ?? '')
          })
          if (errorList.length > 0) {
            reject(new Error(errorList.join('\n')))
            return
          }
        }
        resolve()
      },
      error: function () {
        reject(new Error('ajax adding to cart error'))
      }
    })
  })
}
