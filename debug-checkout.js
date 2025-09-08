// Add this to your browser console on the checkout page to check
console.log('Checkout type check:');
console.log('Has .wp-block-woocommerce-checkout:', document.querySelector('.wp-block-woocommerce-checkout') ? 'BLOCK CHECKOUT' : 'NOT FOUND');
console.log('Has [woocommerce_checkout]:', document.querySelector('form.checkout') ? 'CLASSIC CHECKOUT' : 'NOT FOUND');
console.log('Available payment gateways from WC blocks:', window.wc?.wcSettings?.getSetting('paymentMethodTypes'));
console.log('All WC settings:', window.wc?.wcSettings?.allSettings);
console.log('Payment method data:', window.wc?.wcSettings?.getSetting('paymentMethodData'));
console.log('All available payment gateways:', window.wc?.wcSettings?.getSetting('paymentGateways'));
