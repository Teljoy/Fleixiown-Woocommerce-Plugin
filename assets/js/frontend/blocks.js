const { registerPaymentMethod } = window.wc.wcBlocksRegistry;
const { getSetting } = window.wc.wcSettings;
const { decodeEntities } = window.wp.htmlEntities;
const { createElement } = window.wp.element;

const settings = getSetting('flexiown_data', {});

const canMakePayment = () => {
    return true;
};

// Create actual React elements using plugin settings with fallbacks
const labelElement = createElement('span', { style: { width: '100%' } }, 
    decodeEntities(settings.title || 'Flexiown')
);

const contentElement = createElement('div', { className: 'wc-block-components-payment-method-content' }, 
    decodeEntities(settings.description || 'Try It, Love It, Own It. You will be redirected to FlexiownPay to securely complete your payment.')
);

/**
 * Flexiown payment method config object.
 */
const Flexiown = {
    name: "flexiown",
    label: labelElement,
    content: contentElement,
    edit: contentElement,
    canMakePayment: canMakePayment,
    ariaLabel: decodeEntities(settings.title || 'Flexiown'),
    supports: {
        features: settings?.supports || ['products'],
    },
};

registerPaymentMethod(Flexiown);
