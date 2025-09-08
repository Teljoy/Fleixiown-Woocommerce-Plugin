<?php
/**
 * Flexiown Widget for Elementor
 *
 * @package Flexiown
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Flexiown Widget for Elementor
 */
class Flexiown_Widget_Elementor extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'flexiown_widget';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return esc_html__('Flexiown Payment Widget', 'flexiown');
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'fa fa-credit-card';
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
        return ['flexiown', 'payment', 'widget', 'finance'];
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
                'raw' => esc_html__('This widget displays the Flexiown payment information on product pages. It shows payment options and terms available for the current product.', 'flexiown'),
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
            'text_color',
            [
                'label' => esc_html__('Text Color', 'flexiown'),
                'type' => \Elementor\Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .flexiown-widget' => 'color: {{VALUE}}',
                ],
            ]
        );

        $this->add_group_control(
            \Elementor\Group_Control_Typography::get_type(),
            [
                'name' => 'typography',
                'selector' => '{{WRAPPER}} .flexiown-widget',
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
                    '{{WRAPPER}} .flexiown-widget' => 'text-align: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render the widget output on the frontend
     */
    protected function render() {
        echo '<div class="flexiown-widget">';
        echo do_shortcode('[flexiown_widget]');
        echo '</div>';
    }

    /**
     * Render the widget output in the editor
     */
    protected function content_template() {
        ?>
        <div class="flexiown-widget">
            <div class="elementor-panel-alert elementor-panel-alert-info">
                <?php echo esc_html__('Flexiown Payment Widget - This will display payment options on the frontend', 'flexiown'); ?>
            </div>
        </div>
        <?php
    }
}
