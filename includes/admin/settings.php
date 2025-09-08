<?php
mb_internal_encoding('UTF-8');
require_once FLEXIOWN_PLUGIN_PATH . 'includes/util.php';

class WC_Gateway_Flexiown extends WC_Payment_Gateway
{
    protected $environments;
    protected $version;
    public $method_title;
    public $method_description;
    public $icon;
    protected $url;
    protected $validate_url;
    protected $status_url;
    protected $merchant_url;
    protected $api_url;
    protected $merchant_api_key;
    protected $debug_email;
    protected $available_countries;
    protected $available_currencies;
    public $supports;
    public $title;
    protected $response_url;
    protected $send_debug_email;
    public $description;
    public $enabled;
    protected $enable_logging;
    protected $verify_redirect_params;
    protected $enable_product_widget;
    protected $enable_cart_warnings;
    protected $flexiown_stock_hold;
    protected $flexiown_headers;
    protected $logger;
    public $flexiown_logger;
    public static $log = true;
    public static $log_enabled = false;

    /**
     * Constructor for the gateway.
     */
    public function __construct()
    {

        $this->id                             = 'flexiown';
        $this->version                        = FLEXIOWN_VERSION;
        $this->method_title                   = __('Flexiown', 'flexiown');
        $this->method_description             = __('Use Flexiown to process payments for WooCommerce.', 'flexiown');
        $this->icon                           = FLEXIOWN_PLUGIN_URL . "/assets/media/flexiown_logo_small.png";
        $this->debug_email                    = $this->get_option('admin_email');
        $this->available_countries            = array('ZA');
        $this->available_currencies           = (array)apply_filters('woocommerce_gateway_flexiown_available_currencies', array('ZAR'));
        $this->supports                       = array('products');
        $this->merchant_api_key               = $this->get_option('merchant_api_key');
        $this->title                          = $this->get_option('title') ? $this->get_option('title') : __('Flexiown', 'woo_flexiown');
        $this->debug_email                    = $this->get_option('debug_email', get_option('admin_email'));
        $this->response_url                   = add_query_arg('wc-api', 'WC_Gateway_Flexiown', home_url('/'));
        $this->send_debug_email               = 'yes' === $this->get_option('send_debug_email');
        $this->description                    = $this->get_option('description') ? $this->get_option('description') : __('Try It, Love It, Own It. You will be redirected to FlexiownPay to securely complete your payment.', 'woo_flexiown');
        $this->enabled                        = 'yes' === $this->get_option('enabled') ? 'yes' : 'no';
        $this->enable_logging                 = 'yes' === $this->get_option('enable_logging');
        $this->verify_redirect_params         = 'yes' === $this->get_option('verify_redirect_params');
        $this->enable_product_widget          = 'yes' === $this->get_option('enable_product_widget');
        $this->flexiown_stock_hold            = $this->get_option('flexiown_stock_hold') ?? 10080;
        $this->enable_cart_warnings           = $this->get_option('enable_cart_warnings');

        //setup admin area
        $this->init_form_fields();
        // Load stock hold option
        if (is_admin() && current_user_can('manage_options')) {
            $this->verify_client_status();
        }
        $this->init_environment_config();
        $this->init_settings();




        add_action('admin_notices', array($this, 'flexiown_admin_notices'));
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_receipt_flexiown', array($this, 'receipt_page'));
        add_action('woocommerce_api_wc_gateway_flexiown', array($this, 'check_api_response'));
        // add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_api_response'));
        add_filter('woocommerce_available_payment_gateways', array(
            $this,
            'check_cart_line_item_validity'
        ), 99, 1);
    }


    private function get_safe_user_agent()
    {
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'WordPress';
        return sanitize_text_field(substr($user_agent, 0, 255)); // Limit length
    }

    /**
     * Get standard API headers for all Flexiown API requests
     *
     * @param array $additional_headers Optional additional headers to merge
     * @return array Standard headers array
     */
    private function get_api_headers($additional_headers = array())
    {
        $default_headers = array(
            'Content-Type' => 'application/json',
            'api-key' => $this->get_option('merchant_api_key'),
            'api-version' => $this->version,
            'user-agent' => $this->get_safe_user_agent(),
            'environment' => '{"woocommerce_version": "' . WC_VERSION . '","php_version":"' . phpversion() . '"}'
        );

        return array_merge($default_headers, $additional_headers);
    }

    /**
     * Get standard API request arguments
     *
     * @param string $method HTTP method (GET, POST, etc.)
     * @param string $body Request body (optional)
     * @param array $additional_headers Additional headers (optional)
     * @return array Complete wp_remote_* arguments
     */
    private function get_api_args($method = 'GET', $body = null, $additional_headers = array())
    {
        $args = array(
            'method' => strtoupper($method),
            'headers' => $this->get_api_headers($additional_headers),
            'timeout' => 30,
        );

        if ($body !== null) {
            $args['body'] = $body;
        }

        return $args;
    }



    /**
     * Determine if the gateway still requires setup.
     *
     * @return bool
     */
    public function needs_setup()
    {
        return !$this->get_option('merchant_api_key');
    }

    /**
     * Check if the gateway is available for use.
     *
     * @return bool
     */
    public function is_available()
    {
        if ('yes' === $this->enabled) {
            $errors = $this->check_requirements();
            return 0 === count($errors);
        }
        return parent::is_available();
    }


    /**
     * Initialise Gateway Settings Form Fields
     *
     * @since 1.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title'       => __('Enable/Disable', 'woocommerce-gateway-flexiown'),
                'label'       => __('Enable flexiown', 'woocommerce-gateway-flexiown'),
                'type'        => 'checkbox',
                'description' => __('This controls whether or not this gateway is enabled within WooCommerce.', 'woocommerce-gateway-flexiown'),
                'default'     => 'no',        // User should enter the required information before enabling the gateway.
                'desc_tip'    => true,
            ),
            'title' => array(
                'title'       => __('Title', 'woocommerce-gateway-flexiown'),
                'type'        => 'text',
                'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-gateway-flexiown'),
                'default'     => __('flexiown', 'woocommerce-gateway-flexiown'),
                'desc_tip'    => true,
            ),
            'flexiown_stock_hold' => array(
                'title'             => __('Hold stock (minutes) override', 'woocommerce'),
                'desc'              => __('Hold stock (for unpaid orders) for x minutes. When this limit is reached, the pending order will be cancelled. Leave blank to disable.', 'woocommerce'),
                'id'                => 'woocommerce_hold_stock_minutes',
                'type'              => 'number',
                'custom_attributes' => array(
                    'min'  => 0,
                ),
                'css'               => 'width: 80px;',
                'default'           => '2000',
                'autoload'          => false,
                'class'             => 'manage_stock_field',
            ),
            'description' => array(
                'title'       => __('Description', 'woocommerce-gateway-flexiown'),
                'type'        => 'text',
                'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-gateway-flexiown'),
                'default'     => '',
                'desc_tip'    => true,
            ),
            'staging' => array(
                'title'       => __('Flexiown Staging', 'woocommerce-gateway-flexiown'),
                'type'        => 'checkbox',
                'description' => __('Place the payment gateway in development mode.', 'woocommerce-gateway-flexiown'),
                'default'     => 'yes',
            ),
            'merchant_api_key' => array(
                'title'       => __('Api Key', 'woocommerce-gateway-flexiown'),
                'type'        => 'password',
                'description' => __('This is the merchant ID, received from flexiown.', 'woocommerce-gateway-flexiown'),
                'default'     => '',
            ),
            'enable_product_widget' => array(
                'title' => __('Product Page Widget', 'woo_flexiown'),
                'type' => 'checkbox',
                'label' => __('Enable Product Page Widget', 'woo_flexiown'),
                'default' => 'no',
            ),
            'flexiown_on_cart' => [
                'title' => 'Display on Cart',
                'label' => 'Enable',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
                'desc_tip' => true,
            ],
            'flexiown_store_locator' => [
                'title' => 'Enable Store Locations',
                'label' => 'Enable',
                'type' => 'checkbox',
                'default' => 'no',
                'description' =>  __('This will enable a new post type for store locations. To add this to your ui, use the following shortcode [store_location_dropdown] .', 'woo_flexiown'),
            ],
            'flexiown_cart_as_combined' => [
                'title' => 'Set cart rate based on total instead of lowest',
                'label' => 'Enable',
                'type' => 'checkbox',
                'description' => '',
                'default' => 'no',
                'desc_tip' => true,
            ],
            'is_using_page_builder' => array(
                'title' => __('Product Page Widget using any page builder', 'woo_flexiown'),
                'type' => 'checkbox',
                'label' => __('Enable Product Page Widget using page builder', 'woo_flexiown'),
                'default' => 'no',
                'description' => __('If you use a page builder plugin, the above payment info can be placed using a shortcode instead of relying on hooks. Use [flexiown_widget] within a product page.', 'woo_flexiown')

            ),
            'enable_cart_warnings' => array(
                'title'   => __('Enable Cart warning system', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Enable Cart warning system.', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
            'send_debug_email' => array(
                'title'   => __('Send Debug Emails', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Send debug e-mails for transactions through the flexiown gateway (sends on successful transaction as well).', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
            'debug_email' => array(
                'title'       => __('Who Receives Debug E-mails?', 'woocommerce-gateway-flexiown'),
                'type'        => 'text',
                'description' => __('The e-mail address to which debugging error e-mails are sent when in test mode.', 'woocommerce-gateway-flexiown'),
                'default'     => get_option('admin_email'),
            ),
            'enable_logging' => array(
                'title'   => __('Enable Logging', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Enable transaction logging for gateway.', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
            'enable_product_barcode' => array(
                'title'   => __('Enable Product Barcode', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Enable product barcode field on product edit page.', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
            'disable_guest_order_persistence' => array(
                'title'   => __('Disable Guest Order Persistence', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Disable persistent cart for guest users.', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
            'enable_agent_mode' => array(
                'title'   => __('Enable Agent Mode', 'woocommerce-gateway-flexiown'),
                'type'    => 'checkbox',
                'label'   => __('Enable agent mode for the gateway.', 'woocommerce-gateway-flexiown'),
                'default' => 'no',
            ),
        );
    }



    public static function get_order_prop($order, $prop)
    {
        switch ($prop) {
            case 'order_total':
                $getter = array($order, 'get_total');
                break;
            default:
                $getter = array($order, 'get_' . $prop);
                break;
        }

        return is_callable($getter) ? call_user_func($getter) : $order->{$prop};
    }


    /**
     * Init Environment Options
     *
     * @since 1.2.3
     */
    public function init_environment_config()
    {
        //    $this->flexiown_log('trigger en: ' . print_r($this->get_option('staging'), true), false);
        require('config.php');
        if ($this->get_option('staging') == 'yes') {
            $this->environments = $environments["staging"];
        } else {
            $this->environments = $environments["production"];
        }

        $this->url                            = $this->environments['api_url'] . 'payment/create';
        $this->validate_url                   = $this->environments['api_url'] . 'product';
        $this->status_url                     = $this->environments['api_url'] . 'status/';
        $this->merchant_url                   = $this->environments['api_url'] . 'merchant/';
        $this->api_url                        = $this->environments['api_url'];
    }

    private function verify_client_status()
    {
        // return false if no api key is set
        if (empty($this->get_option('merchant_api_key'))) {
            return update_option("woocommerce_hold_stock_minutes", $this->flexiown_stock_hold);
        }

        $verify_merchant = wp_remote_get(
            $this->merchant_url,
            $this->get_api_args('GET')
        );


        // $this->flexiown_log('environmentt: ' . print_r($this->merchant_url, true), false);
        $status = json_decode(wp_remote_retrieve_body($verify_merchant));
        // $this->flexiown_log('verify merchant status: ' . print_r($status->completion_period_expiry, true), false);

        if (is_wp_error($status)) {
            update_option("woocommerce_hold_stock_minutes", $this->flexiown_stock_hold);
            return $this->flexiown_stock_hold;
        }

        if ($status->completion_period_expiry !== null) {
            // covert seconds to minutes
            $completion_period_expiry = round($status->completion_period_expiry / 60);

            update_option("woocommerce_hold_stock_minutes", $completion_period_expiry);
            $this->update_option("flexiown_stock_hold", $completion_period_expiry);
            return $completion_period_expiry;
        } else {
            update_option("woocommerce_hold_stock_minutes", $this->flexiown_stock_hold);
            return $this->flexiown_stock_hold;
        }
    }


    /**
     * Log system processes.
     * @since 1.0.0
     */
    public function flexiown_log($message, $send_mail)
    {
        //log the item if valid
        if ('yes' === $this->get_option('staging') || $this->enable_logging) {
            if (empty($this->flexiown_logger)) {
                $this->flexiown_logger = new WC_Logger();
            }
            $this->flexiown_logger->add('flexiown', $message);
        }
        //send debug mail if valid
        if ($this->send_debug_email === 'yes' && $send_mail) {
            wp_mail($this->debug_email, 'Flexiown Plugin: Debug or Error Notification', $message);
        }
    }


    /**
     *  Show possible admin notices
     */
    public function flexiown_admin_notices()
    {

        if (!current_user_can('manage_options')) {
            return;
        }
        // Get requirement errors.
        $errors_to_show = $this->check_requirements();

        // If everything is in place, don't display it.
        if (!count($errors_to_show)) {
            return;
        }

        // If the gateway isn't enabled, don't show it.
        if ("no" ===  $this->enabled) {
            return;
        }

        // Use transients to display the admin notice once after saving values.
        if (!get_transient('wc-gateway-flexiown-admin-notice-transient')) {
            set_transient('wc-gateway-flexiown-admin-notice-transient', 1, 1);

            echo '<div class="notice notice-error is-dismissible"><p>'
                . __('To use flexiown as a payment provider, you need to fix the problems below:', 'woocommerce-gateway-flexiown') . '</p>'
                . '<ul style="list-style-type: disc; list-style-position: inside; padding-left: 2em;">'
                . array_reduce($errors_to_show, function ($errors_list, $error_item) {
                    $errors_list = $errors_list . PHP_EOL . ('<li>' . $error_item . '</li>');
                    return $errors_list;
                }, '')
                . '</ul></p></div>';
        }
    }

    /**
     * check_requirements()
     *
     * Check if this gateway is enabled and available in the base currency being traded with.
     *
     * @since 1.0.0
     * @return array
     */
    public function check_requirements()
    {

        $woocommerce_settings = get_option('woocommerce_flexiown_settings');
        //$time_set 			= null !== $this->get_option('flexiown_hold_stock_minutes') ? $this->get_option('flexiown_hold_stock_minutes') : $woocommerce_settings["woocommerce_hold_stock_minutes"];
        //$recommended_time = isset($this->flexiown_hold_stock_recommended_minutes) ? $this->flexiown_hold_stock_recommended_minutes : $woocommerce_settings["woocommerce_hold_stock_minutes"];
        $errors = [
            // Check if the store currency is supported by Flexiown
            !in_array(get_woocommerce_currency(), $this->available_currencies) ? 'Your store uses a currency that Flexiown doesnt support yet' : null,
            // Check if user entered the merchant ID
            empty($this->get_option('merchant_api_key'))  ? 'You forgot to fill your merchant ID' : null,
            // Check the stock hold time
            //'yes' == $this->get_option('staging') ? sprintf(__('Flexiown test mode is still enabled, Click <strong><a href="%s">here</a></strong> to disable it when you want to start accepting live payment on your site.', 'woo-flexiown'), esc_url(admin_url('admin.php?page=wc-settings&tab=checkout&section=flexiown'))) : null

        ];

        return array_filter($errors);
    }

    private function build_product_list($orderitems)
    {
        $items = array();
        $i = 0;
        foreach ($orderitems as $item) {
            $i++;
            // get SKU
            if ($item['variation_id']) {
                if (function_exists("wc_get_product")) {
                    $product = wc_get_product($item['variation_id']);
                } else {
                    $product = new WC_Product($item['variation_id']);
                }
            } else {
                if (function_exists("wc_get_product")) {
                    $product = wc_get_product($item['product_id']);
                } else {
                    $product = new WC_Product($item['product_id']);
                }
            }
            $product = array(
                'name' => $item['name'],
                'sku' => $product->get_sku(),
                'quantity' => $item['qty'],
                'price' => number_format(($item['line_subtotal'] / $item['qty']), 2, '.', ''),
                'description' => "string",
                'brand' => "string",
                'merchant_product_id' => $item['product_id'],
                'vendor' => array(
                    'vendor_id' => "string",
                    'url' => "string",
                    'name' => "string"
                ),
                'images' => array(
                    wp_get_attachment_image_src(get_post_thumbnail_id($item['product_id']), 'single-post-thumbnail')[0]
                ),
                'barcodes' => array(
                    get_post_meta(self::get_order_prop($product, 'id'), 'flexiown_barcode', true)
                ),
                'categories' => array(),
                'properties' => array(
                    array(
                        'key' => "string",
                        'value' => "string"
                    )
                )
            );

            // $jsonString = json_encode($product, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $items[] = $product;
        }
        return $items;
    }

    /**
     * Process the payment and return the result
     * - redirects the customer to the pay page
     *
     * @param int $order_id
     *
     * @since 1.0.0
     * @return array
     */
    public function process_payment($order_id)
    {

        if (function_exists("wc_get_order")) {
            $order = wc_get_order($order_id);
        } else {
            $order = new WC_Order($order_id);
        }

        //Process here
        $orderitems = $order->get_items();

        if (count($orderitems)) {
            $items = $this->build_product_list($orderitems);
        }

        //calculate total shipping amount
        if (method_exists($order, 'get_shipping_total')) {
            //WC 3.0
            $shipping_total = $order->get_shipping_total();
        } else {
            //WC 2.6.x
            $shipping_total = $order->get_total_shipping();
        }

        $OrderBody = $this->transaction_payload($order, $items, $order_id, $shipping_total);

        $this->flexiown_log('POST Order request: ' . print_r($OrderBody, true), true);

        $order_response = wp_remote_post(
        $this->url, 
        $this->get_api_args('POST', $OrderBody)
        );

        $order_body = json_decode(wp_remote_retrieve_body($order_response));
        $this->flexiown_log('POST Order response: ' . print_r($order_body, true), true);
        $this->flexiown_log('POST Order response url: ' . print_r($this->url, true), true);

        if (is_wp_error($order_body)) {
            $order->add_order_note(__('Some Errors have occurred. Payment couldn\'t proceed.', 'woo_flexiown'));
            wc_add_notice(__('Sorry, there was a problem preparing your payment.', 'woo_flexiown'), 'error');
            return array(
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        if ($order_body->success !== null && $order_body->success == true) {
            //add the id
            $order->update_meta_data('flexiown_transaction_id', $order_body->id);
            $order->save_meta_data();
            //$this->flexiown_log('POST base order: ' . print_r($order, true));
            // $this->flexiown_log('POST Order response ID: ' . print_r($order_body->id, true));

            //lets store the redirect url for later
            $order->update_meta_data('flexiown_redirect_url', $order_body->redirect_url);
            $order->save_meta_data();

            //store location of store if available
            $store_id = get_option('active_store_location');
            if ($store_id) {
                $store = get_post($store_id);
                $order->update_meta_data('flexiown_store_location', $store->post_title);
                $order->save_meta_data();
            }



            $redirectURL = $order_body->redirect_url;

            return array(
                'result' => 'success',
                'redirect' => $redirectURL
            );
        } else {
            if ($this->send_debug_email === 'yes') {
                $this->flexiown_log('Sending email notification', false);
                // Send an email
                // $subject = 'Flexiown Create Cart error: ';
                $body =
                    "Hi,\n\n" .
                    "An invalid Flexiown transaction on your website requires attention\n" .
                    "Order ID: " . $order_id . "\n" .
                    "------------------------------------------------------------\n";
                if ($order_body->errors !== null) {
                    foreach ($order_body->errors as $key => $error) {
                        $body .= $key . " : " . $error[0] . "\n";
                    }
                }

                $this->flexiown_log($body, true);
            }
            $order->add_order_note(__('Some Errors have occurred. Payment couldn\'t proceed.', 'woo_flexiown'));
            wc_add_notice(__('Sorry, there was a problem preparing your payment.', 'woo_flexiown'), 'error');
            return array(
                'result' => 'failure',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }
    }

    public function transaction_payload($order, $items, $order_id, $shipping_total)
    {
        // Sanitize all customer data
        $customer_data = array(
            'first_name' => sanitize_text_field($order->billing_first_name),
            'last_name' => sanitize_text_field($order->billing_last_name),
            'email' => sanitize_email($order->billing_email),
            'mobile' => sanitize_text_field($order->billing_phone)
        );

        // Sanitize shipping address
        $shipping_address = array(
            'type' => 'residential',
            'building' => sanitize_text_field($order->shipping_address_1) . ' ',
            'street' => sanitize_text_field($order->shipping_address_2),
            'suburb' => sanitize_text_field($order->shipping_city),
            'city' => sanitize_text_field($order->shipping_city),
            'province' => sanitize_text_field($order->shipping_state),
            'country' => sanitize_text_field($order->shipping_country),
            'postal_code' => sanitize_text_field($order->shipping_postcode),
            'confirmed' => false
        );

        // Sanitize billing address
        $billing_address = array(
            'type' => 'residential',
            'building' => sanitize_text_field($order->billing_address_1) . ' ',
            'street' => sanitize_text_field($order->billing_address_2),
            'suburb' => sanitize_text_field($order->billing_city),
            'city' => sanitize_text_field($order->billing_city),
            'province' => sanitize_text_field($order->billing_state),
            'country' => sanitize_text_field($order->billing_country),
            'postal_code' => sanitize_text_field($order->billing_postcode),
            'confirmed' => false
        );

        // Create the seed value base exactly as before
        $seed_value_base = json_encode($items, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        foreach ($items as $item) {
            $seed_value_base .= $item[0];
        }

        // Store the trust seed in order meta (same as before)
        $order->update_meta_data('flexiown_trust_seed', base64_encode($seed_value_base));
        $order->save_meta_data();

        // Get active store location (same as before)
        $active_store_location = '';
        $store_id = get_option('active_store_location');
        if ($store_id) {
            $store = get_post($store_id);
            $active_store_location = $store->post_title;
        }

        // Create redirects object with sanitized data
        $redirects = array(
            'order_id' => sanitize_text_field($order_id),
            'trust_value' => hash('md5', $order_id),
            'trust_seed' => get_post_meta(self::get_order_prop($order, 'id'), 'flexiown_trust_seed', true),
            'success_redirect_url' => $this->get_return_url($order) . '&order_id=' . $order_id . '&wc-api=WC_Gateway_Flexiown',
            'failure_redirect_url' => $this->get_return_url($order) . '&status=cancelled&wc-api=WC_Gateway_Flexiown',
            'final_amount' => number_format($order->get_total(), 2, '.', ''),
            'tax_amount' => $order->get_total_tax(),
            'shipping_amount' => $shipping_total,
            'discount' => '0',
            'merchant_store' => sanitize_text_field($active_store_location)
        );

        // Build the complete order data structure
        $order_data = array(
            'customer' => $customer_data,
            'shipping_address' => $shipping_address,
            'billing_address' => $billing_address,
            'products' => $items, // This is already an array from build_product_list()
            'redirects' => $redirects
        );

        // Return JSON exactly as before but now securely encoded
        return json_encode($order_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }


    /**
     * Reciept page.
     *
     * Display text and a button to direct the user to Flexiown.
     *
     * @since 1.0.0
     */
    public function receipt_page($order_id)
    {
        $this->flexiown_log('Receipt page: ', true);
        if (function_exists("wc_get_order")) {
            $order = wc_get_order($order_id);
        } else {
            $order = new WC_Order($order_id);
        }
        echo '<p>' . __('Thank you for your order, please click the button below to pay with Flexiown.', 'woocommerce-gateway-flexiown') . '</p>';
        print_r($order);
        exit;
    }


    /**
     * Check Flexiown API response or request
     * we will either receive a redirect from the payment processor or they will trigger this at a later time
     *
     * @since 1.0.0
     */
    public function check_api_response()
    {
        $this->flexiown_log('Checkout page triggered: ', true);

        //01: first we must ensure the status and trust value is present and our request is from flexiown
        if ($this->verify_redirect_params === 'yes') {
            if (!$_GET['status'] || !$_GET['trust_signature'] || !$_GET['order_id']) {
                $this->flexiown_log('Security Warning: missing query params' . print_r($_GET, true), true);
                $this->flexiown_log('Security Warning: attempt to trigger payment processor' . print_r($_SERVER, true), true);
                wp_redirect('/');
                exit;
            }
        }


        //02: fetch the transaction
        $order = wc_get_order($_GET['order_id']);
        $this->flexiown_log('Verifying transaction status part 01 ' . print_r(self::get_order_prop($order, 'status')), true);

        //03: verify transaction against flexiown api
        $state = $this->validate_transaction_status($order);
        if (!$state) {
            $this->flexiown_log('Verifying transaction failed ' . print_r($state), true);
            wp_redirect('/');
            exit;
        }

        //04: verify transaction status against the received status
        // if((self::get_order_prop( $order, 'status' ) == 'confirmed') || (self::get_order_prop( $order, 'status' ) == $_GET['status'])){
        // 	//transaction already completed or at the same level as status update so exit
        // 	wp_redirect('/');
        // 	exit;
        // }


        //05: verify the signature :: return the ! after testing
        if (!$this->validate_signature($state->trust_signature, $order)) {
            //the signature has failed
            $this->flexiown_log('Security Warning: an incorrect signature was attempted on order ' . $_GET['order_id'] . "\n" .
                print_r($_SERVER, true), true);
            wp_redirect('/');
            exit;
        }

        //06: process the transaction based on the new status
        if ($state->status === 'confirmed' || $state->status === 'complete') {
            $this->handle_api_payment_complete($state, $order);
        } elseif ($state->status  === 'failed') {
            $this->handle_api_payment_failed($state, $order);
        } elseif ($state->status  === 'pending') {
            $this->handle_api_payment_pending($state, $order);
        } elseif ($state->status  === 'cancelled') {
            $this->handle_api_payment_cancelled($state, $order);
        }

        //05: complete the process
        $payment_page = $this->get_return_url($order) . '&status=' . $state->status;
        wp_redirect($payment_page);
        exit;
        header('HTTP/1.0 200 OK');
        flush();
    }


    /**
     * This function handles payment complete request by Flexiown.
     * @version 1.4.3 Subscriptions flag
     *
     * @param array $data should be from the Gatewy API callback.
     * @param WC_Order $order
     */
    private function handle_api_payment_complete($state, $order)
    {
        $this->flexiown_log('state: ' . print_r($state, true), false);
        $this->flexiown_log('order ID: ' . print_r($state->order_id, true), false);
        $this->flexiown_log('signature: ' . print_r($state->trust_signature, true), false);
        $this->flexiown_log('Payment Processed as Complete' . print_r($order, true), false);
        $order->add_order_note(sprintf(__('Payment approved. Flexiown Order signature: ' . $state->trust_signature . ' ', 'woo_flexiown')));
        $order->update_meta_data('flexiown_transaction_signed', $state->trust_signature);
        WC()->cart->empty_cart();
        //wc_empty_cart();
        $order->payment_complete($state->order_id);
        $vendor_name    = get_bloginfo('name', 'display');
        $vendor_url     = home_url('/');
        $body =
            "Hi,\n\n"
            . "A Flexiown transaction has been completed on your website\n"
            . "------------------------------------------------------------\n"
            . 'Site: ' . esc_html($vendor_name) . ' (' . esc_url($vendor_url) . ")\n"
            . 'Flexiown Trust Signature: ' . esc_html($state->trust_signature) . "\n";
        $this->flexiown_log($body, true);
    }

    /**
     * This function handles payment complete request by Flexiown.
     * @version 1.4.3 Subscriptions flag
     *
     * @param array $data should be from the Gatewy API callback.
     * @param WC_Order $order
     */
    private function handle_api_payment_failed($state, $order)
    {
        $this->flexiown_log('Payment Processed as failed', false);
        $order->add_order_note(sprintf(__('Flexiown payment declined. Order Signature from Flexiown: ' . $state->trust_signature . ' ', 'woo_flexiown')));
        $order->update_status('failed');
    }

    /**
     * This function handles payment pending response by Flexiown.
     * @version 1.4.3 Subscriptions flag
     *
     * @param array $data should be from the Gatewy API callback.
     * @param WC_Order $order
     */
    private function handle_api_payment_pending($state, $order)
    {
        $this->flexiown_log('Payment Processed as pending', false);
        $this->flexiown_log('state: ' . print_r($state, true), false);
        $order->update_status('pending');
        WC()->cart->empty_cart();
    }

    /**
     * This function handles payment cancelled response by Flexiown.
     * @version 1.4.3 Subscriptions flag
     *
     * @param array $data should be from the Gatewy API callback.
     * @param WC_Order $order
     */
    private function handle_api_payment_cancelled($state, $order)
    {
        $this->flexiown_log('Payment Processed as cancelled', false);
        $order->add_order_note(sprintf(__('Flexiown payment is pending approval. Flexiown Order ID: ' . $state->trust_signature . ' ', 'woo_flexiown')));
        $order->update_status('cancelled');
    }


    /**
     * validate_signature()
     *
     * Validate the signature against the returned data.
     *
     * @param array $data
     * @param string $signature
     * @since 1.0.0
     * @return string
     */
    public function validate_signature($signature, $order)
    {
        $result = $this->generate_signature($order) === $signature;
        $this->flexiown_log('Signature = ' . $this->generate_signature($order), false);
        return $result;
    }


    /**
     * verify transaction status
     *
     * Validate the received order_id against flexiown api
     *
     * @param array $data
     * @param string $signature
     * @since 1.0.0
     * @return string
     */
    public function validate_transaction_status($order)
    {

        $transaction_id = get_post_meta(self::get_order_prop($order, 'id'), 'flexiown_transaction_id', true);
        $verify_transaction = wp_remote_get(
            $this->status_url . $transaction_id,
            $this->get_api_args('GET')
        );
        $status = json_decode(wp_remote_retrieve_body($verify_transaction));
        $this->flexiown_log('transaction status: ' . print_r($status, true), false);
        if (is_wp_error($status)) {
            return false;
        }
        //is this a valid response
        // if ($status->statusCode && $status->statusCode == 404) {
        // 	return false;
        // }
        //does the query string and the product lookup id match
        if ($status->order_id != self::get_order_prop($order, 'id')) {
            return false;
        }

        // verify the url signature and the endpoint signature match

        // return response
        return $status;
    }


    public function generate_signature($order)
    {
        //01 create trust_value
        $trust_value = hash('md5', self::get_order_prop($order, 'id'));
        //02 create trust_seed
        $trust_seed = get_post_meta(self::get_order_prop($order, 'id'), 'flexiown_trust_seed', true);
        //for testing return true
        return hash('sha256', $trust_value . '' . $trust_seed);
    }


    /**
     * Check whether the cart line items are flexiown approved
     *
     * @param  array $gateways Enabled gateways
     * @return  array Enabled gateways, possibly with Flexiown removed
     * @since 1.0.0
     */
    public function check_cart_line_item_validity($gateways)
    {
        if (is_admin() || !is_checkout()) {
            return $gateways;
        }
        global $woocommerce;

        if (isset($woocommerce->cart->cart_contents) && count($woocommerce->cart->cart_contents) >= 1) {
            $showFlexiown = true;
            $response = $this->api_bulk_product_lookup($woocommerce->cart->cart_contents);

            foreach ($response as $item) {
                if ($item->accepted == false) {
                    $showFlexiown = false;
                }
            }



            if (!$showFlexiown) {
                unset($gateways['flexiown']);
            }
        }

        return $gateways;
    }


    public function generate_product_payload($obj)
    {
        //removed for testing
        // "description": "' . esc_html($data['description']) . '",
        // "short_description": "' . esc_html($data['short_description']) . '",

        $data = $obj->get_data();
        $item = '{
			"name": "' . esc_html($data['name']) . '",
			"brand": "string",
			"merchant_product_id":"' . $data['id'] . '",
			"quantity": "1",
			"vendor": {
			  "vendor_id": "string",
			  "url": "string",
			  "name": "string"
			},
			"images": [
			  "' . wp_get_attachment_image_src($data['image_id'])[0] . '" 
			],
			"url": "string",
			"price": "' . $data['price'] . '",
			"sku": "' . $data['sku'] . '",
			"barcodes": [
			  "' . get_post_meta($data['id'], 'flexiown_barcode', true)  . '"
			],
			"categories": [';
        foreach ($data['category_ids'] as $cat) {
            $item .= '
				  {
					"id": "' . $cat . '",
					"name": "' . get_the_category_by_ID($cat) . '",
					"url": "string"
				  },';
        }
        $item = rtrim($item, ",");
        $item .= '],
			"properties": [
			  {
				"key": "string",
				"value": "string"
			  }
			]
		  }
		';
        return $item;
    }

    public function api_product_lookup($payload, $product_id)
    {
        $payload = $this->generate_product_payload($payload);
        $this->flexiown_log('POST product lookup: ' . print_r($payload, true), false);

        $response = wp_remote_post($this->validate_url, array(
            'body' => $payload,
            'headers' => array(
                'Content-Type' => 'application/json',
                'api-key' => $this->get_option('merchant_api_key'),
                'api-version' => $this->version,
                'user-agent' => $this->get_safe_user_agent(),
                'environment' => '{"woocommerce_version": "' . WC_VERSION . '","php_version":"' . phpversion() . '"}'
            ),
            'timeout' => 30
        ));

        $result = json_decode(wp_remote_retrieve_body($response));
        $this->flexiown_log('POST lookup response: ' . print_r($result, true), false);

        if (is_wp_error($result)) {
            return false;
        }
        if (isset($result->accepted) && $result->accepted == true) {
            return $result;
        } elseif (isset($result->accepted) && $result->accepted == false) {
            return false;
        } else {
            $this->flexiown_log('Sending error email notification for Flexiown Product Lookup error:', false);
            $body =
                "Hi,\n\n" .
                "An invalid Flexiown lookup on your website requires attention\n" .
                "Product ID: " . $product_id . "\n" .
                "------------------------------------------------------------\n";
            //$body .= $result->message !== null?$result->message:'';
            $this->flexiown_log($body, true);

            return false;
        }
    }

    public function api_bulk_product_lookup($list)
    {

        $payload = '[';
        foreach ($list as $item) {
            $payload .= $this->generate_product_payload($item['data']) . ',';
        }
        $payload = rtrim($payload, ",");
        $payload .= ']';


        $response = wp_remote_post(
        $this->validate_url, 
        $this->get_api_args('POST', $payload)
        );

        $result = json_decode(wp_remote_retrieve_body($response));
        $this->flexiown_log('POST lookup response: ' . print_r($result, true), false);


        if (is_wp_error($result)) {
            return false;
        } else {
            return $result;
        }
    }

    public function order_status_change_update($order)
    {
        //verify product list is the same
        //Process here
        $orderitems = $order->get_items();

        if (count($orderitems)) {
            $items = $this->build_product_list($orderitems);
        }

        $seed_value_base = '';
        foreach ($items as $item) {
            $seed_value_base .= $item[0];
        }
        $alt_trust_value = base64_encode($seed_value_base);

        $payload = '{
			"status": "' . self::get_order_prop($order, 'status') . '",
			"trust_value": "' . hash('md5', self::get_order_prop($order, 'id')) . '",
			"trust_seed": "' . get_post_meta(self::get_order_prop($order, 'id'), 'flexiown_trust_seed', true) . '",
			"alt_trust_value": "' . $alt_trust_value . '"
		}';

        $transaction_id = get_post_meta(self::get_order_prop($order, 'id'), 'flexiown_transaction_id', true);
        $response = wp_remote_post(
            $this->status_url . $transaction_id, 
            $this->get_api_args('POST', $payload)
        );

        $this->flexiown_log('POST transaction status change: ' . print_r($payload, true), false);

        $result = json_decode(wp_remote_retrieve_body($response));
        if (is_wp_error($result)) {
            $this->flexiown_log('Sending error email notification for Flexiown Product Lookup error:', false);
            $body =
                "Hi,\n\n" .
                "A failure occured when attempting to notify flexiown of an order status change\n" .
                "Order ID: " . self::get_order_prop($order, 'id') . "\n" .
                "------------------------------------------------------------\n";
            $this->flexiown_log($body, true);

            return false;
        }
    }
};
