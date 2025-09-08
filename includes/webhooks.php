<?php

/**
 * Register a custom REST API endpoint to retrieve logs and handle responses from api
 * URL: /wp-json/flexiown/v1/logbook
 * Method: GET
 * Purpose: Retrieve latest logs
 */
//ENDPOINTS
function flexiown_register_stores_endpoint()
{
    register_rest_route('fo/v1', '/stores', array(
        'methods' => 'GET',
        'callback' => 'flexiown_get_stores',
        'permission_callback' => '__return_true', // Allow public access for demonstration
        'args' => array(
            'per_page' => array(
                'description' => 'Number of stores to retrieve',
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'sanitize_callback' => 'absint'
            ),
            'page' => array(
                'description' => 'Page number for pagination',
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
            'search' => array(
                'description' => 'Search term to filter books',
                'type' => 'string',
                'validate_callback' => function ($param, $request, $key) {
                    return is_string($param);
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
        )
    ));
}
add_action('rest_api_init', 'flexiown_register_stores_endpoint');

function flexiown_register_logbook_webhook_endpoint()
{
    register_rest_route('fo/v1', '/logbook', array(
        'methods' => 'GET',
        'callback' => 'flexiown_fetch_logbook',
        'permission_callback' => 'flexiown_webhook_permissions',
        'args' => array()
    ));
}
add_action('rest_api_init', 'flexiown_register_logbook_webhook_endpoint');

function flexiown_register_orders_endpoint()
{
    register_rest_route('fo/v1', '/orders', array(
        'methods' => 'GET',
        'callback' => 'flexiown_get_orders',
        'permission_callback' => 'flexiown_webhook_permissions',
        'args' => array(
            'status' => array(
                'description' => 'Filter orders by status',
                'type' => 'string',
                'default' => '',
                'validate_callback' => function ($param, $request, $key) {
                    // Valid WooCommerce order statuses:
                    // pending, processing, on-hold, completed, cancelled, refunded, failed
                    $valid_statuses = array('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed');
                    return empty($param) || in_array($param, $valid_statuses);
                },
                'sanitize_callback' => 'sanitize_text_field'
            ),
            'per_page' => array(
                'description' => 'Number of orders to retrieve',
                'type' => 'integer',
                'default' => 10,
                'minimum' => 1,
                'maximum' => 100,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) && $param > 0 && $param <= 100;
                },
                'sanitize_callback' => 'absint'
            ),
            'page' => array(
                'description' => 'Page number for pagination',
                'type' => 'integer',
                'default' => 1,
                'minimum' => 1,
                'validate_callback' => function ($param, $request, $key) {
                    return is_numeric($param) && $param > 0;
                },
                'sanitize_callback' => 'absint'
            ),
        )
    ));
}
add_action('rest_api_init', 'flexiown_register_orders_endpoint');


// CALLBACKS


function flexiown_get_stores(WP_REST_Request $request)
{
    // Extract parameters from request
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');
    $search = $request->get_param('search');

    // Build query arguments
    $args = array(
        'post_type' => 'store_location',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'post_status' => 'publish'
    );

    // Add search functionality
    if (!empty($search)) {
        $args['s'] = $search;
    }


    // Execute the query
    $store_query = new WP_Query($args);
    $stores = $store_query->posts;

    // Handle empty results
    if (empty($stores)) {
        return new WP_Error('no_store_locations', 'No store locations found', array('status' => 404));
    }

    // Format the response data
    $data = array();
    foreach ($stores as $store) {
        // Get custom meta fields

        $data[] = array(
            'id' => $store->ID,
            'title' => $store->post_title,
            'content' => apply_filters('the_content', $store->post_content),
            'date' => $store->post_date,
            'modified' => $store->post_modified,
            'status' => $store->post_status,
            'permalink' => get_permalink($store->ID)
        );
    }

    // Build response with pagination info
    $response = rest_ensure_response($data);

    // Add pagination headers
    $response->header('X-WP-Total', $store_query->found_posts);
    $response->header('X-WP-TotalPages', $store_query->max_num_pages);

    return $response;
}

function flexiown_get_orders(WP_REST_Request $request)
{
    // Extract parameters from request
    $status = $request->get_param('status');
    $per_page = $request->get_param('per_page');
    $page = $request->get_param('page');

    // Build query arguments
    $args = array(
        'limit' => $per_page,
        'page' => $page,
        'orderby' => 'date',
        'order' => 'DESC',
        'payment_method' => 'flexiown', // Only return orders paid with Flexiown
    );

    // Add status filter if provided
    // Valid statuses: pending, processing, on-hold, completed, cancelled, refunded, failed
    if (!empty($status)) {
        $args['status'] = 'wc-' . $status; // WooCommerce prefixes statuses with 'wc-'
    }

    // Get orders using WooCommerce function
    $orders = wc_get_orders($args);

    // Handle empty results
    if (empty($orders)) {
        return new WP_Error('no_orders', 'No Flexiown orders found', array('status' => 404));
    }

    // Format the response data
    $data = array();
    foreach ($orders as $order) {
        $order_data = array(
            'id' => $order->get_id(),
            'order_number' => $order->get_order_number(),
            'status' => $order->get_status(),
            'currency' => $order->get_currency(),
            'total' => $order->get_total(),
            'subtotal' => $order->get_subtotal(),
            'tax_total' => $order->get_total_tax(),
            'shipping_total' => $order->get_shipping_total(),
            'payment_method' => $order->get_payment_method(),
            'payment_method_title' => $order->get_payment_method_title(),
            'date_created' => $order->get_date_created()->date('Y-m-d H:i:s'),
            'date_modified' => $order->get_date_modified()->date('Y-m-d H:i:s'),
            'customer_id' => $order->get_customer_id(),
            'billing' => array(
                'first_name' => $order->get_billing_first_name(),
                'last_name' => $order->get_billing_last_name(),
                'email' => $order->get_billing_email(),
                'phone' => $order->get_billing_phone(),
                'address_1' => $order->get_billing_address_1(),
                'address_2' => $order->get_billing_address_2(),
                'city' => $order->get_billing_city(),
                'state' => $order->get_billing_state(),
                'postcode' => $order->get_billing_postcode(),
                'country' => $order->get_billing_country(),
            ),
            'shipping' => array(
                'first_name' => $order->get_shipping_first_name(),
                'last_name' => $order->get_shipping_last_name(),
                'address_1' => $order->get_shipping_address_1(),
                'address_2' => $order->get_shipping_address_2(),
                'city' => $order->get_shipping_city(),
                'state' => $order->get_shipping_state(),
                'postcode' => $order->get_shipping_postcode(),
                'country' => $order->get_shipping_country(),
            ),
            'flexiown_custom_fields' => array(
                'flexiown_redirect_url' => get_post_meta($order->get_id(), 'flexiown_redirect_url', true),
                'flexiown_store_location' => get_post_meta($order->get_id(), 'flexiown_store_location', true),
                'flexiown_transaction_id' => get_post_meta($order->get_id(), 'flexiown_transaction_id', true),
                'flexiown_trust_seed' => get_post_meta($order->get_id(), 'flexiown_trust_seed', true),
                'is_vat_exempt' => get_post_meta($order->get_id(), 'is_vat_exempt', true),
            ),
            'line_items' => array()
        );

        // Add line items
        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            $order_data['line_items'][] = array(
                'id' => $item_id,
                'name' => $item->get_name(),
                'product_id' => $item->get_product_id(),
                'variation_id' => $item->get_variation_id(),
                'quantity' => $item->get_quantity(),
                'subtotal' => $item->get_subtotal(),
                'total' => $item->get_total(),
                'sku' => $product ? $product->get_sku() : '',
            );
        }

        $data[] = $order_data;
    }

    // Build response
    $response = rest_ensure_response($data);

    // Add pagination info (approximate since wc_get_orders doesn't return total count easily)
    $response->header('X-WP-Total-Returned', count($orders));

    return $response;
}

function flexiown_fetch_logbook(WP_REST_Request $request)
{
    // Get timestamp parameter (optional)
    $timestamp = $request->get_param('timestamp');
    
    // CALL THE FUNCTION TO GET LOG FILES
    $log_files = get_log_files();
    if (empty($log_files)) {
        return new WP_Error('no_logs', 'No log files found', array('status' => 404));
    }
    
    // OPEN THE LATEST LOG FILE AND PARSE AND RETURN ITS CONTENT AS JSON
    $latest_log_file = end($log_files); // This is now the full file path
    
    if (!file_exists($latest_log_file)) {
        return new WP_Error('file_not_found', 'Log file not found: ' . $latest_log_file, array('status' => 404));
    }
    
    $log_contents = file_get_contents($latest_log_file);
    if ($log_contents === false) {
        return new WP_Error('read_error', 'Could not read log file', array('status' => 500));
    }
    
    $log_lines = explode("\n", $log_contents);
    $filtered_logs = [];
    
    foreach ($log_lines as $line) {
        if (empty(trim($line))) continue; // Skip empty lines
        
        // Only process lines that contain flexiown-related keywords
        $flexiown_keywords = ['flexiown', 'Flexiown', 'FLEXIOWN', 'gateway', 'payment', 'api'];
        $contains_flexiown = false;
        foreach ($flexiown_keywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                $contains_flexiown = true;
                break;
            }
        }
        
        if (!$contains_flexiown) {
            continue; // Skip non-flexiown logs
        }
        
        // WooCommerce log format: YYYY-MM-DD HH:MM:SS LEVEL message
        // Example: 2025-09-08 10:30:45 INFO This is a log message
        if (preg_match('/^(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\s+(\w+)\s+(.*)/', $line, $matches)) {
            // If timestamp filter is provided, check it
            if ($timestamp) {
                $log_date = strtotime($matches[1]);
                if ($log_date < strtotime($timestamp)) {
                    continue; // Skip logs older than timestamp
                }
            }
            
            $filtered_logs[] = array(
                'date' => $matches[1],
                'level' => $matches[2], 
                'message' => trim($matches[3])
            );
        } else {
            // Fallback for other formats or continuation lines (but still flexiown-related)
            $filtered_logs[] = array(
                'date' => '',
                'level' => 'UNKNOWN',
                'message' => trim($line)
            );
        }
    }

    return rest_ensure_response(array_values($filtered_logs));
}


function get_log_files()
{
    $log_files = array();
    
    // Get WooCommerce logger
    $logger = wc_get_logger();
    
    // Get the log directory from WooCommerce
    $log_dir = trailingslashit(wp_upload_dir()['basedir']) . 'wc-logs/';
    
    // Alternative method if above doesn't work
    if (!is_dir($log_dir)) {
        // Try to get from WC constants
        if (defined('WC_LOG_DIR')) {
            $log_dir = trailingslashit(WC_LOG_DIR);
        } else {
            // Fallback to default WooCommerce location
            $upload_dir = wp_upload_dir();
            $log_dir = trailingslashit($upload_dir['basedir']) . 'wc-logs/';
        }
    }

    // Get all flexiown log files in the directory
    $files = glob($log_dir . '*flexiown*.log');

    // Loop through the files and add them to the log files array
    foreach ($files as $file) {
        $log_files[basename($file)] = $file; // Store full path
    }

    return $log_files;
}


//HEADERS


function flexiown_webhook_permissions(WP_REST_Request $request)
{
    $options = get_option('woocommerce_flexiown_settings', 'gets the option');
    $api_key = $request->get_header('X-API-Key');
    if (isset($options['merchant_api_key']) && $options['merchant_api_key'] === $api_key) {
        return true;
    } else {
        return true;
        // enable after demo
        //return new WP_Error('forbidden', 'API key not configured or invalid', array('status' => 403));
    }
}
