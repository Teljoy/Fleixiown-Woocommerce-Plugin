<?php 

$options = get_option('woocommerce_flexiown_settings', 'gets the option');

if (isset($options['enable_agent_mode']) && 'yes' === $options['enable_agent_mode']) {

    /**
     * FLEXIOWN SIDE MENU WITH AGENT MODE OPTION
     * this section shows a ui in the admin area that a logged in call center agent can use to search, create or manage customers and orders as well as pull products and see their own performance
     * 
     **/
    add_action('admin_menu', 'flexiown_add_agent_menu');
    add_action('admin_enqueue_scripts', 'flexiown_agent_enqueue_scripts');

    function flexiown_add_agent_menu()
    {
        add_menu_page(
            __('Flexiown Agent', 'woocommerce-gateway-flexiown'),
            __('Flexiown Agent', 'woocommerce-gateway-flexiown'),
            'manage_options',
            'flexiown-agent',
            'flexiown_agent_page',
            'dashicons-admin-users',
            56
        );
    }

    function flexiown_agent_enqueue_scripts($hook)
    {
        if ($hook !== 'toplevel_page_flexiown-agent') {
            return;
        }
        
        // Enqueue WordPress admin styles and scripts
        wp_enqueue_script('jquery');
        wp_enqueue_script('jquery-ui-tabs');
        wp_enqueue_script('jquery-ui-datepicker');
        wp_enqueue_style('wp-jquery-ui-dialog');
        
        // Add custom CSS
        wp_add_inline_style('wp-admin', '
            .flexiown-agent-container {
                max-width: 1400px;
                margin: 20px 0;
            }
            .flexiown-tabs {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .flexiown-tab-nav {
                background: #f1f1f1;
                border-bottom: 1px solid #ccd0d4;
                margin: 0;
                padding: 0;
                display: flex;
            }
            .flexiown-tab-nav li {
                margin: 0;
                list-style: none;
            }
            .flexiown-tab-nav a {
                display: block;
                padding: 15px 20px;
                text-decoration: none;
                color: #646970;
                border-right: 1px solid #ccd0d4;
                transition: all 0.2s ease;
            }
            .flexiown-tab-nav a:hover,
            .flexiown-tab-nav a.active {
                background: #fff;
                color: #135e96;
                border-bottom: 2px solid #135e96;
            }
            .flexiown-tab-content {
                padding: 20px;
                min-height: 600px;
            }
            .flexiown-search-box {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }
            .flexiown-search-row {
                display: flex;
                gap: 15px;
                margin-bottom: 15px;
                align-items: end;
            }
            .flexiown-search-field {
                flex: 1;
            }
            .flexiown-search-field label {
                display: block;
                margin-bottom: 5px;
                font-weight: 600;
                color: #1d2327;
            }
            .flexiown-search-field input,
            .flexiown-search-field select {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 4px;
                font-size: 14px;
            }
            .flexiown-btn {
                background: #2271b1;
                color: white;
                border: none;
                padding: 10px 20px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                text-decoration: none;
                display: inline-block;
                transition: background 0.2s ease;
            }
            .flexiown-btn:hover {
                background: #135e96;
                color: white;
            }
            .flexiown-btn-secondary {
                background: #6c757d;
            }
            .flexiown-btn-secondary:hover {
                background: #5a6268;
            }
            .flexiown-btn-success {
                background: #00a32a;
            }
            .flexiown-btn-success:hover {
                background: #008a20;
            }
            .flexiown-data-table {
                width: 100%;
                border-collapse: collapse;
                background: white;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                overflow: hidden;
            }
            .flexiown-data-table th {
                background: #f1f1f1;
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ccd0d4;
                font-weight: 600;
                color: #1d2327;
            }
            .flexiown-data-table td {
                padding: 12px;
                border-bottom: 1px solid #f0f0f1;
                vertical-align: middle;
            }
            .flexiown-data-table tr:hover {
                background: #f6f7f7;
            }
            .flexiown-status-badge {
                padding: 4px 8px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .status-active { background: #d1f2eb; color: #00695c; }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-completed { background: #d4edda; color: #155724; }
            .status-cancelled { background: #f8d7da; color: #721c24; }
            .flexiown-stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .flexiown-stat-card {
                background: white;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                text-align: center;
                box-shadow: 0 1px 1px rgba(0,0,0,.04);
            }
            .flexiown-stat-value {
                font-size: 32px;
                font-weight: bold;
                color: #2271b1;
                margin-bottom: 5px;
            }
            .flexiown-stat-label {
                color: #646970;
                font-size: 14px;
            }
            .flexiown-form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 20px;
            }
            .flexiown-form-section {
                background: #f8f9fa;
                border: 1px solid #ddd;
                border-radius: 4px;
                padding: 20px;
            }
            .flexiown-form-section h3 {
                margin-top: 0;
                color: #1d2327;
                border-bottom: 1px solid #ccd0d4;
                padding-bottom: 10px;
            }
            .flexiown-actions {
                display: flex;
                gap: 10px;
                margin-top: 20px;
            }
            .flexiown-pagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 20px;
                padding: 15px 0;
                border-top: 1px solid #ccd0d4;
            }
            .flexiown-no-results {
                text-align: center;
                padding: 40px;
                color: #646970;
                font-style: italic;
            }
            .flexiown-quick-actions {
                background: white;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 15px;
                margin-bottom: 20px;
            }
            .flexiown-quick-actions h3 {
                margin-top: 0;
                margin-bottom: 15px;
            }
            .flexiown-action-buttons {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
            }
        ');
    }

    function flexiown_agent_page()
    {
        // Get sample data for demonstration
        $customers = flexiown_get_sample_customers();
        $orders = flexiown_get_sample_orders();
        $products = flexiown_get_sample_products();
        $agent_stats = flexiown_get_agent_stats();
        
        ?>
        <div class="wrap flexiown-agent-container">
            <h1><?php _e('Flexiown Agent Dashboard', 'woocommerce-gateway-flexiown'); ?></h1>
            
            <div class="flexiown-tabs">
                <ul class="flexiown-tab-nav">
                    <li><a href="#tab-dashboard" class="active"><?php _e('Dashboard', 'woocommerce-gateway-flexiown'); ?></a></li>
                    <li><a href="#tab-customers"><?php _e('Customers', 'woocommerce-gateway-flexiown'); ?></a></li>
                    <li><a href="#tab-orders"><?php _e('Orders', 'woocommerce-gateway-flexiown'); ?></a></li>
                    <li><a href="#tab-products"><?php _e('Products', 'woocommerce-gateway-flexiown'); ?></a></li>
                    <li><a href="#tab-reports"><?php _e('Reports', 'woocommerce-gateway-flexiown'); ?></a></li>
                </ul>

                <!-- Dashboard Tab -->
                <div id="tab-dashboard" class="flexiown-tab-content">
                    <div class="flexiown-quick-actions">
                        <h3><?php _e('Quick Actions', 'woocommerce-gateway-flexiown'); ?></h3>
                        <div class="flexiown-action-buttons">
                            <a href="#" class="flexiown-btn flexiown-btn-success"><?php _e('Create New Customer', 'woocommerce-gateway-flexiown'); ?></a>
                            <a href="#" class="flexiown-btn"><?php _e('Create New Order', 'woocommerce-gateway-flexiown'); ?></a>
                            <a href="#" class="flexiown-btn flexiown-btn-secondary"><?php _e('Search Customer', 'woocommerce-gateway-flexiown'); ?></a>
                            <a href="#" class="flexiown-btn flexiown-btn-secondary"><?php _e('Track Order', 'woocommerce-gateway-flexiown'); ?></a>
                        </div>
                    </div>

                    <div class="flexiown-stats-grid">
                        <div class="flexiown-stat-card">
                            <div class="flexiown-stat-value"><?php echo $agent_stats['customers_today']; ?></div>
                            <div class="flexiown-stat-label"><?php _e('Customers Helped Today', 'woocommerce-gateway-flexiown'); ?></div>
                        </div>
                        <div class="flexiown-stat-card">
                            <div class="flexiown-stat-value"><?php echo $agent_stats['orders_today']; ?></div>
                            <div class="flexiown-stat-label"><?php _e('Orders Processed Today', 'woocommerce-gateway-flexiown'); ?></div>
                        </div>
                        <div class="flexiown-stat-card">
                            <div class="flexiown-stat-value">R<?php echo number_format($agent_stats['sales_today']); ?></div>
                            <div class="flexiown-stat-label"><?php _e('Sales Volume Today', 'woocommerce-gateway-flexiown'); ?></div>
                        </div>
                        <div class="flexiown-stat-card">
                            <div class="flexiown-stat-value"><?php echo $agent_stats['calls_today']; ?></div>
                            <div class="flexiown-stat-label"><?php _e('Calls Handled Today', 'woocommerce-gateway-flexiown'); ?></div>
                        </div>
                    </div>

                    <h3><?php _e('Recent Activity', 'woocommerce-gateway-flexiown'); ?></h3>
                    <table class="flexiown-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('Time', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Type', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Customer', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Description', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Status', 'woocommerce-gateway-flexiown'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>14:32</td>
                                <td>Order</td>
                                <td>Sarah Johnson</td>
                                <td>Created order #1234 - iPhone 14 Pro</td>
                                <td><span class="flexiown-status-badge status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>14:18</td>
                                <td>Customer</td>
                                <td>Mike Peters</td>
                                <td>Updated customer information</td>
                                <td><span class="flexiown-status-badge status-completed">Completed</span></td>
                            </tr>
                            <tr>
                                <td>13:45</td>
                                <td>Support</td>
                                <td>Lisa Brown</td>
                                <td>Resolved payment issue for order #1233</td>
                                <td><span class="flexiown-status-badge status-completed">Completed</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Customers Tab -->
                <div id="tab-customers" class="flexiown-tab-content" style="display: none;">
                    <div class="flexiown-search-box">
                        <h3><?php _e('Search Customers', 'woocommerce-gateway-flexiown'); ?></h3>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Name', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter customer name...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Email', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="email" placeholder="<?php _e('Enter email address...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Phone', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="tel" placeholder="<?php _e('Enter phone number...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn"><?php _e('Search', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Customer ID', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter customer ID...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('City', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter city...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Status', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value=""><?php _e('All Statuses', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="active"><?php _e('Active', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="inactive"><?php _e('Inactive', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn flexiown-btn-success"><?php _e('Create New Customer', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                    </div>

                    <table class="flexiown-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('ID', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Name', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Email', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Phone', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Location', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Orders', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Total Spent', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Status', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Actions', 'woocommerce-gateway-flexiown'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($customers as $customer): ?>
                            <tr>
                                <td><?php echo $customer['id']; ?></td>
                                <td><strong><?php echo $customer['name']; ?></strong></td>
                                <td><?php echo $customer['email']; ?></td>
                                <td><?php echo $customer['phone']; ?></td>
                                <td><?php echo $customer['location']; ?></td>
                                <td><?php echo $customer['orders']; ?></td>
                                <td>R<?php echo number_format($customer['total_spent']); ?></td>
                                <td><span class="flexiown-status-badge status-<?php echo $customer['status']; ?>"><?php echo ucfirst($customer['status']); ?></span></td>
                                <td>
                                    <a href="#" class="flexiown-btn" style="padding: 5px 10px; font-size: 12px;"><?php _e('View', 'woocommerce-gateway-flexiown'); ?></a>
                                    <a href="#" class="flexiown-btn flexiown-btn-secondary" style="padding: 5px 10px; font-size: 12px;"><?php _e('Edit', 'woocommerce-gateway-flexiown'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="flexiown-pagination">
                        <div><?php _e('Showing 1-10 of 156 customers', 'woocommerce-gateway-flexiown'); ?></div>
                        <div>
                            <button class="flexiown-btn flexiown-btn-secondary"><?php _e('Previous', 'woocommerce-gateway-flexiown'); ?></button>
                            <button class="flexiown-btn"><?php _e('Next', 'woocommerce-gateway-flexiown'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Orders Tab -->
                <div id="tab-orders" class="flexiown-tab-content" style="display: none;">
                    <div class="flexiown-search-box">
                        <h3><?php _e('Search Orders', 'woocommerce-gateway-flexiown'); ?></h3>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Order ID', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter order number...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Customer Name', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter customer name...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Date From', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="date">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Date To', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="date">
                            </div>
                        </div>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Status', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value=""><?php _e('All Statuses', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="pending"><?php _e('Pending', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="processing"><?php _e('Processing', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="completed"><?php _e('Completed', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="cancelled"><?php _e('Cancelled', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Payment Method', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value=""><?php _e('All Methods', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="flexiown"><?php _e('Flexiown', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="card"><?php _e('Credit Card', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="cash"><?php _e('Cash', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn"><?php _e('Search Orders', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn flexiown-btn-success"><?php _e('Create New Order', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                    </div>

                    <table class="flexiown-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('Order', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Date', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Customer', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Products', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Total', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Payment', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Status', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Actions', 'woocommerce-gateway-flexiown'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo $order['date']; ?></td>
                                <td><?php echo $order['customer']; ?></td>
                                <td><?php echo $order['products']; ?></td>
                                <td><strong>R<?php echo number_format($order['total']); ?></strong></td>
                                <td><?php echo $order['payment']; ?></td>
                                <td><span class="flexiown-status-badge status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span></td>
                                <td>
                                    <a href="#" class="flexiown-btn" style="padding: 5px 10px; font-size: 12px;"><?php _e('View', 'woocommerce-gateway-flexiown'); ?></a>
                                    <a href="#" class="flexiown-btn flexiown-btn-secondary" style="padding: 5px 10px; font-size: 12px;"><?php _e('Edit', 'woocommerce-gateway-flexiown'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="flexiown-pagination">
                        <div><?php _e('Showing 1-10 of 89 orders', 'woocommerce-gateway-flexiown'); ?></div>
                        <div>
                            <button class="flexiown-btn flexiown-btn-secondary"><?php _e('Previous', 'woocommerce-gateway-flexiown'); ?></button>
                            <button class="flexiown-btn"><?php _e('Next', 'woocommerce-gateway-flexiown'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Products Tab -->
                <div id="tab-products" class="flexiown-tab-content" style="display: none;">
                    <div class="flexiown-search-box">
                        <h3><?php _e('Search Products', 'woocommerce-gateway-flexiown'); ?></h3>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Product Name', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter product name...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('SKU', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="text" placeholder="<?php _e('Enter SKU...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Category', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value=""><?php _e('All Categories', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="smartphones"><?php _e('Smartphones', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="laptops"><?php _e('Laptops', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="tablets"><?php _e('Tablets', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="accessories"><?php _e('Accessories', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn"><?php _e('Search Products', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                        <div class="flexiown-search-row">
                            <div class="flexiown-search-field">
                                <label><?php _e('Price From', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="number" placeholder="<?php _e('Min price...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Price To', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="number" placeholder="<?php _e('Max price...', 'woocommerce-gateway-flexiown'); ?>">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Stock Status', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value=""><?php _e('All Stock', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="instock"><?php _e('In Stock', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="outofstock"><?php _e('Out of Stock', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="onbackorder"><?php _e('On Backorder', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label>&nbsp;</label>
                                <button class="flexiown-btn flexiown-btn-secondary"><?php _e('Export Results', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                    </div>

                    <table class="flexiown-data-table">
                        <thead>
                            <tr>
                                <th><?php _e('Image', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Product', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('SKU', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Category', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Price', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Stock', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Sales', 'woocommerce-gateway-flexiown'); ?></th>
                                <th><?php _e('Actions', 'woocommerce-gateway-flexiown'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td><div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-size: 12px; color: #666;">IMG</div></td>
                                <td><strong><?php echo $product['name']; ?></strong></td>
                                <td><?php echo $product['sku']; ?></td>
                                <td><?php echo $product['category']; ?></td>
                                <td><strong>R<?php echo number_format($product['price']); ?></strong></td>
                                <td><?php echo $product['stock']; ?></td>
                                <td><?php echo $product['sales']; ?></td>
                                <td>
                                    <a href="#" class="flexiown-btn" style="padding: 5px 10px; font-size: 12px;"><?php _e('View', 'woocommerce-gateway-flexiown'); ?></a>
                                    <a href="#" class="flexiown-btn flexiown-btn-success" style="padding: 5px 10px; font-size: 12px;"><?php _e('Add to Order', 'woocommerce-gateway-flexiown'); ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="flexiown-pagination">
                        <div><?php _e('Showing 1-10 of 234 products', 'woocommerce-gateway-flexiown'); ?></div>
                        <div>
                            <button class="flexiown-btn flexiown-btn-secondary"><?php _e('Previous', 'woocommerce-gateway-flexiown'); ?></button>
                            <button class="flexiown-btn"><?php _e('Next', 'woocommerce-gateway-flexiown'); ?></button>
                        </div>
                    </div>
                </div>

                <!-- Reports Tab -->
                <div id="tab-reports" class="flexiown-tab-content" style="display: none;">
                    <div class="flexiown-form-grid">
                        <div class="flexiown-form-section">
                            <h3><?php _e('Performance Summary', 'woocommerce-gateway-flexiown'); ?></h3>
                            <div class="flexiown-stats-grid">
                                <div class="flexiown-stat-card">
                                    <div class="flexiown-stat-value">247</div>
                                    <div class="flexiown-stat-label"><?php _e('Total Customers This Month', 'woocommerce-gateway-flexiown'); ?></div>
                                </div>
                                <div class="flexiown-stat-card">
                                    <div class="flexiown-stat-value">89</div>
                                    <div class="flexiown-stat-label"><?php _e('Orders This Month', 'woocommerce-gateway-flexiown'); ?></div>
                                </div>
                                <div class="flexiown-stat-card">
                                    <div class="flexiown-stat-value">R487,650</div>
                                    <div class="flexiown-stat-label"><?php _e('Sales This Month', 'woocommerce-gateway-flexiown'); ?></div>
                                </div>
                                <div class="flexiown-stat-card">
                                    <div class="flexiown-stat-value">324</div>
                                    <div class="flexiown-stat-label"><?php _e('Calls This Month', 'woocommerce-gateway-flexiown'); ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="flexiown-form-section">
                            <h3><?php _e('Generate Report', 'woocommerce-gateway-flexiown'); ?></h3>
                            <div class="flexiown-search-field">
                                <label><?php _e('Report Type', 'woocommerce-gateway-flexiown'); ?></label>
                                <select>
                                    <option value="daily"><?php _e('Daily Summary', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="weekly"><?php _e('Weekly Summary', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="monthly"><?php _e('Monthly Summary', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="customer-activity"><?php _e('Customer Activity', 'woocommerce-gateway-flexiown'); ?></option>
                                    <option value="sales-performance"><?php _e('Sales Performance', 'woocommerce-gateway-flexiown'); ?></option>
                                </select>
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('Date Range', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="date">
                            </div>
                            <div class="flexiown-search-field">
                                <label><?php _e('To', 'woocommerce-gateway-flexiown'); ?></label>
                                <input type="date">
                            </div>
                            <div class="flexiown-actions">
                                <button class="flexiown-btn"><?php _e('Generate Report', 'woocommerce-gateway-flexiown'); ?></button>
                                <button class="flexiown-btn flexiown-btn-secondary"><?php _e('Export to CSV', 'woocommerce-gateway-flexiown'); ?></button>
                            </div>
                        </div>
                    </div>

                    <div class="flexiown-search-box">
                        <h3><?php _e('Top Products This Month', 'woocommerce-gateway-flexiown'); ?></h3>
                        <table class="flexiown-data-table">
                            <thead>
                                <tr>
                                    <th><?php _e('Rank', 'woocommerce-gateway-flexiown'); ?></th>
                                    <th><?php _e('Product', 'woocommerce-gateway-flexiown'); ?></th>
                                    <th><?php _e('Sales', 'woocommerce-gateway-flexiown'); ?></th>
                                    <th><?php _e('Revenue', 'woocommerce-gateway-flexiown'); ?></th>
                                    <th><?php _e('Growth', 'woocommerce-gateway-flexiown'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>1</strong></td>
                                    <td>iPhone 14 Pro Max</td>
                                    <td>23 units</td>
                                    <td>R87,400</td>
                                    <td style="color: green;">+15%</td>
                                </tr>
                                <tr>
                                    <td><strong>2</strong></td>
                                    <td>Samsung Galaxy S23</td>
                                    <td>18 units</td>
                                    <td>R54,000</td>
                                    <td style="color: green;">+8%</td>
                                </tr>
                                <tr>
                                    <td><strong>3</strong></td>
                                    <td>MacBook Air M2</td>
                                    <td>12 units</td>
                                    <td>R48,000</td>
                                    <td style="color: red;">-3%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.flexiown-tab-nav a').click(function(e) {
                e.preventDefault();
                var target = $(this).attr('href');
                
                // Update active tab
                $('.flexiown-tab-nav a').removeClass('active');
                $(this).addClass('active');
                
                // Show target content
                $('.flexiown-tab-content').hide();
                $(target).show();
            });
        });
        </script>
        <?php
    }

    // Sample data functions for demonstration
    function flexiown_get_sample_customers()
    {
        return [
            ['id' => 1001, 'name' => 'Sarah Johnson', 'email' => 'sarah@email.com', 'phone' => '+27 82 123 4567', 'location' => 'Cape Town', 'orders' => 5, 'total_spent' => 15400, 'status' => 'active'],
            ['id' => 1002, 'name' => 'Mike Peters', 'email' => 'mike@email.com', 'phone' => '+27 83 234 5678', 'location' => 'Johannesburg', 'orders' => 2, 'total_spent' => 8900, 'status' => 'active'],
            ['id' => 1003, 'name' => 'Lisa Brown', 'email' => 'lisa@email.com', 'phone' => '+27 84 345 6789', 'location' => 'Durban', 'orders' => 8, 'total_spent' => 23100, 'status' => 'active'],
            ['id' => 1004, 'name' => 'David Wilson', 'email' => 'david@email.com', 'phone' => '+27 85 456 7890', 'location' => 'Pretoria', 'orders' => 1, 'total_spent' => 3200, 'status' => 'pending'],
            ['id' => 1005, 'name' => 'Emma Davis', 'email' => 'emma@email.com', 'phone' => '+27 86 567 8901', 'location' => 'Port Elizabeth', 'orders' => 12, 'total_spent' => 45600, 'status' => 'active'],
        ];
    }

    function flexiown_get_sample_orders()
    {
        return [
            ['id' => 1234, 'date' => '2024-03-15', 'customer' => 'Sarah Johnson', 'products' => 'iPhone 14 Pro, Case', 'total' => 18999, 'payment' => 'Flexiown', 'status' => 'completed'],
            ['id' => 1235, 'date' => '2024-03-15', 'customer' => 'Mike Peters', 'products' => 'Samsung Galaxy S23', 'total' => 15499, 'payment' => 'Credit Card', 'status' => 'processing'],
            ['id' => 1236, 'date' => '2024-03-14', 'customer' => 'Lisa Brown', 'products' => 'MacBook Air M2', 'total' => 24999, 'payment' => 'Flexiown', 'status' => 'completed'],
            ['id' => 1237, 'date' => '2024-03-14', 'customer' => 'David Wilson', 'products' => 'AirPods Pro', 'total' => 4299, 'payment' => 'Cash', 'status' => 'pending'],
            ['id' => 1238, 'date' => '2024-03-13', 'customer' => 'Emma Davis', 'products' => 'iPad Pro, Apple Pencil', 'total' => 16799, 'payment' => 'Flexiown', 'status' => 'cancelled'],
        ];
    }

    function flexiown_get_sample_products()
    {
        return [
            ['name' => 'iPhone 14 Pro Max', 'sku' => 'IPH14PM-256', 'category' => 'Smartphones', 'price' => 21999, 'stock' => 25, 'sales' => 45],
            ['name' => 'Samsung Galaxy S23', 'sku' => 'SGS23-128', 'category' => 'Smartphones', 'price' => 15499, 'stock' => 18, 'sales' => 32],
            ['name' => 'MacBook Air M2', 'sku' => 'MBA-M2-256', 'category' => 'Laptops', 'price' => 24999, 'stock' => 12, 'sales' => 28],
            ['name' => 'iPad Pro 11"', 'sku' => 'IPD11-256', 'category' => 'Tablets', 'price' => 16999, 'stock' => 8, 'sales' => 19],
            ['name' => 'AirPods Pro 2nd Gen', 'sku' => 'APP2-WHT', 'category' => 'Accessories', 'price' => 4299, 'stock' => 45, 'sales' => 67],
        ];
    }

    function flexiown_get_agent_stats()
    {
        return [
            'customers_today' => 12,
            'orders_today' => 8,
            'sales_today' => 48750,
            'calls_today' => 15
        ];
    }

}