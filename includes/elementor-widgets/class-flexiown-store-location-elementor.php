<?php
/**
 * Flexiown Store Location Dropdown for Elementor
 *
 * @package Flexiown
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flexiown Store Location Dropdown for Elementor
 */
class Flexiown_Store_Location_Elementor extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'flexiown_store_location';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('Flexiown Store Location', 'flexiown');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'fa fa-map-marker';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['flexiown'];
    }

    /**
     * Get widget keywords
     */
    public function get_keywords() {
        return ['flexiown', 'store', 'location', 'dropdown'];
    }

    /**
     * Register widget controls
     */
    protected function _register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => esc_html__('Content', 'flexiown'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'widget_info',
            [
                'label' => esc_html__('Widget Information', 'flexiown'),
                'type' => \Elementor\Controls_Manager::RAW_HTML,
                'raw' => esc_html__('This widget displays a dropdown for customers to select their preferred store location for Flexiown services.', 'flexiown'),
                'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => esc_html__('Style', 'flexiown'),
                'tab' => \Elementor\Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'dropdown_background',
            [
                'label' => esc_html__('Background Color', 'flexiown'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .flexiown-store-location select' => 'background-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'dropdown_text_color',
            [
                'label' => esc_html__('Text Color', 'flexiown'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .flexiown-store-location select' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_control(
            'dropdown_border_color',
            [
                'label' => esc_html__('Border Color', 'flexiown'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .flexiown-store-location select' => 'border-color: {{VALUE}}',
                ],
            ]
        );

        $this->add_responsive_control(
            'dropdown_width',
            [
                'label' => esc_html__('Width', 'flexiown'),
                'type' => \Elementor\Controls_Manager::SLIDER,
                'size_units' => ['%', 'px'],
                'range' => [
                    '%' => [
                        'min' => 10,
                        'max' => 100,
                    ],
                    'px' => [
                        'min' => 100,
                        'max' => 500,
                    ],
                ],
                'default' => [
                    'unit' => '%',
                    'size' => 100,
                ],
                'selectors' => [
                    '{{WRAPPER}} .flexiown-store-location select' => 'width: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'alignment',
            [
                'label' => esc_html__('Alignment', 'flexiown'),
                'type' => \Elementor\Controls_Manager::CHOOSE,
                'options' => [
                    'left' => [
                        'title' => esc_html__('Left', 'flexiown'),
                        'icon' => 'eicon-text-align-left',
                    ],
                    'center' => [
                        'title' => esc_html__('Center', 'flexiown'),
                        'icon' => 'eicon-text-align-center',
                    ],
                    'right' => [
                        'title' => esc_html__('Right', 'flexiown'),
                        'icon' => 'eicon-text-align-right',
                    ],
                ],
                'default' => 'left',
                'selectors' => [
                    '{{WRAPPER}} .flexiown-store-location' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the widget output on the frontend
     */
    protected function render() {
        echo '<div class="flexiown-store-location">';
        echo do_shortcode('[store_location_dropdown]');
        echo '</div>';
    }

    /**
     * Render the widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="flexiown-store-location">
            <div class="elementor-panel-alert elementor-panel-alert-info">
                <?php echo esc_html__('Flexiown Store Location Dropdown - This will display store selection on the frontend', 'flexiown'); ?>
            </div>
        </div>
        <?php
    }
}
