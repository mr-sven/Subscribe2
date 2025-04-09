<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

class Widget extends \WP_Widget
{
    public function __construct()
    {
        $widget_ops = array(
            'classname'                   => 'smini_widget',
            'description'                 => esc_html__('Sidebar Widget for Subscribe2', SMLD),
            'show_instance_in_rest'       => true,
            'customize_selective_refresh' => true,
        );

        $control_ops = array(
            'width'  => 250,
            'height' => 300,
        );

        parent::__construct(
            'smini_widget',
            esc_html__('Subscribe Mini Widget', SMLD),
            $widget_ops,
            $control_ops
        );
    }
}