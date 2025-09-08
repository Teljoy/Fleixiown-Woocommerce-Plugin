<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Flexiown Blocks integration
 */
final class WC_Gateway_Flexiown_Blocks_Support extends AbstractPaymentMethodType {

    /**
     * The gateway instance.
     */
    private $gateway;

    /**
     * Payment method name/id/slug.
     */
    protected $name = 'flexiown';

    /**
     * Initializes the payment method type.
     */
    public function initialize() {
        $this->settings = get_option('woocommerce_flexiown_settings', []);
        $gateways       = WC()->payment_gateways->payment_gateways();
        $this->gateway  = $gateways[$this->name];
    }

    /**
     * Returns if this payment method should be active. If false, the scripts will not be enqueued.
     */
    public function is_active() {
        return $this->gateway && $this->gateway->is_available();
    }

    /**
     * Returns an array of scripts/handles to be registered for this payment method.
     */
    public function get_payment_method_script_handles() {
        $script_path       = '/assets/js/frontend/blocks.js';
        $script_asset_path = FLEXIOWN_PLUGIN_PATH . '/assets/js/frontend/blocks.asset.php';
        $script_asset      = file_exists($script_asset_path)
            ? require($script_asset_path)
            : array(
                'dependencies' => array(),
                'version'      => FLEXIOWN_VERSION
            );
        $script_url        = FLEXIOWN_PLUGIN_URL . $script_path;

        wp_register_script(
            'wc-flexiown-payments-blocks',
            $script_url,
            $script_asset['dependencies'],
            $script_asset['version'],
            true
        );

        if (function_exists('wp_set_script_translations')) {
            wp_set_script_translations('wc-flexiown-payments-blocks', 'woocommerce-gateway-flexiown', FLEXIOWN_PLUGIN_PATH . '/languages/');
        }

        return ['wc-flexiown-payments-blocks'];
    }

    /**
     * Returns an array of key=>value pairs of data made available to the payment methods script.
     */
    public function get_payment_method_data() {
        return [
            'title'       => $this->gateway ? $this->gateway->title : 'Flexiown',
            'description' => $this->gateway ? $this->gateway->description : 'Try It, Love It, Own It. You will be redirected to FlexiownPay to securely complete your payment.',
            'supports'    => $this->gateway ? $this->gateway->supports : ['products']
        ];
    }
}
