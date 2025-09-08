<?php
/**
 * Flexiown Elementor Integration
 *
 * This file handles Elementor integration for Flexiown widgets
 * Adds Flexiown section with widget and store location dropdown widgets
 *
 * @package Flexiown
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main Flexiown Elementor Integration Class
 */
class Flexiown_Elementor_Integration {

    /**
     * Initialize the Elementor integration
     */
    public static function init() {
        // Check if Elementor is active
        if (!did_action('elementor/loaded')) {
            add_action('elementor/loaded', [__CLASS__, 'init']);
            return;
        }

        // Initialize after Elementor is fully loaded
        add_action('elementor/init', [__CLASS__, 'elementor_init']);
        add_action('elementor/widgets/widgets_registered', [__CLASS__, 'register_widgets']);
        add_action('elementor/elements/categories_registered', [__CLASS__, 'add_widget_categories']);
    }

    /**
     * Initialize Elementor integration
     */
    public static function elementor_init() {
        // Load widget classes only when Elementor is ready
        self::load_widget_classes();
    }

    /**
     * Load widget classes
     */
    private static function load_widget_classes() {
        if (!class_exists('Flexiown_Widget_Elementor')) {
            require_once __DIR__ . '/elementor-widgets/class-flexiown-widget-elementor.php';
        }
        if (!class_exists('Flexiown_Store_Location_Elementor')) {
            require_once __DIR__ . '/elementor-widgets/class-flexiown-store-location-elementor.php';
        }
    }

    /**
     * Add Flexiown widget category to Elementor
     *
     * @param \Elementor\Elements_Manager $elements_manager
     */
    public static function add_widget_categories($elements_manager) {
        $elements_manager->add_category(
            'flexiown',
            [
                'title' => esc_html__('Flexiown', 'flexiown'),
                'icon' => 'fa fa-shopping-cart',
            ]
        );
    }

    /**
     * Register Flexiown widgets
     */
    public static function register_widgets() {
        // Only register widgets if Elementor classes are available
        if (!class_exists('\Elementor\Widget_Base')) {
            return;
        }

        // Ensure widget classes are loaded
        self::load_widget_classes();

        // Register the Flexiown Widget
        if (class_exists('Flexiown_Widget_Elementor')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Flexiown_Widget_Elementor());
        }
        
        // Register the Store Location Dropdown Widget
        if (class_exists('Flexiown_Store_Location_Elementor')) {
            \Elementor\Plugin::instance()->widgets_manager->register_widget_type(new Flexiown_Store_Location_Elementor());
        }
    }
}

// Initialize the Elementor integration
Flexiown_Elementor_Integration::init();
