<?php

class Flexiown_Admin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
    }
    
    public function add_admin_menu() {
        // Main Flexiown menu
        add_menu_page(
            'Flexiown',                    // Page title
            'Flexiown',                    // Menu title
            'manage_options',              // Capability
            'flexiown',                    // Menu slug
            array($this, 'settings_page'), // Callback function
            'dashicons-store',             // Icon
            30                            // Position
        );
        
        // Settings submenu (default page)
        add_submenu_page(
            'flexiown',                    // Parent slug
            'Flexiown Settings',           // Page title
            'Settings',                    // Menu title
            'manage_options',              // Capability
            'flexiown',                    // Menu slug (same as parent for default)
            array($this, 'settings_page')  // Callback
        );
        
        // Check if store locations should be shown
        $options = get_option('woocommerce_flexiown_settings', array());
        if (isset($options['flexiown_store_locator']) && $options['flexiown_store_locator'] == 'yes') {
            add_submenu_page(
                'flexiown',                        // Parent slug
                'Store Locations',                 // Page title
                'Store Locations',                 // Menu title
                'manage_options',                  // Capability
                'flexiown-store-locations',        // Menu slug
                array($this, 'store_locations_page') // Callback
            );
        }
        
        // Check if agent mode should be shown
        if (isset($options['agent_mode']) && $options['agent_mode'] == 'yes') {
            add_submenu_page(
                'flexiown',                    // Parent slug
                'Agent Mode',                  // Page title
                'Agent Mode',                  // Menu title
                'manage_options',              // Capability
                'flexiown-agent-mode',         // Menu slug
                array($this, 'agent_mode_page') // Callback
            );
        }
    }
    
    public function admin_init() {
        // Register settings
        register_setting('flexiown_settings', 'woocommerce_flexiown_settings');
    }
    
    public function settings_page() {
        // Include the existing settings page
        if (file_exists(FLEXIOWN_PLUGIN_PATH . 'includes/admin/settings.php')) {
            include_once FLEXIOWN_PLUGIN_PATH . 'includes/admin/settings.php';
        } else {
            echo '<div class="wrap"><h1>Flexiown Settings</h1><p>Settings page not found.</p></div>';
        }
    }
    
    public function store_locations_page() {
        ?>
        <div class="wrap">
            <h1>Flexiown Store Locations</h1>
            <?php
            // Check if store selector class exists and show the admin interface
            if (class_exists('Store_Selector')) {
                $store_selector = new Store_Selector();
                if (method_exists($store_selector, 'admin_page')) {
                    $store_selector->admin_page();
                } else {
                    echo '<p>Store location management interface not available.</p>';
                }
            } else {
                echo '<p>Store location functionality not loaded.</p>';
            }
            ?>
        </div>
        <?php
    }
    
    public function agent_mode_page() {
        ?>
        <div class="wrap">
            <h1>Flexiown Agent Mode</h1>
            <?php
            // Include agent mode interface if it exists
            if (file_exists(FLEXIOWN_PLUGIN_PATH . 'includes/agent.php')) {
                // Load any agent-specific admin interface
                echo '<p>Agent mode is enabled. Agent functionality is active.</p>';
                
                // You can add agent-specific admin controls here
                echo '<h2>Agent Controls</h2>';
                echo '<p>Configure agent-specific settings and tools here.</p>';
                
                // Example: Show agent statistics or controls
                $this->show_agent_statistics();
                
            } else {
                echo '<p>Agent mode interface not found.</p>';
            }
            ?>
        </div>
        <?php
    }
    
    private function show_agent_statistics() {
        ?>
        <div class="card">
            <h3>Agent Statistics</h3>
            <p>Here you can add agent-specific statistics, controls, or settings.</p>
            <!-- Add your agent mode content here -->
        </div>
        <?php
    }
}

// Initialize the admin class
new Flexiown_Admin();