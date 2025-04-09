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

    public function form($instance)
    {
        $options = get_option('widget_subscribeMiniWidget');
        if (false === $options) {
            $defaults = [
                'title'             => __('Subscribe Mini Widget', SMLD),
            ];
        } else {
            $defaults = [
                'title'             => $options['title'],
            ];
            delete_option('widget_subscribeMiniWidget');
        }
        // Code to obtain old settings too.
        $instance = wp_parse_args((array) $instance, $defaults);

        $title             = htmlspecialchars($instance['title'], ENT_QUOTES);
        ?>
<div>
    <p>
        <label for="<?= esc_attr($this->get_field_id('title')); ?>">
            <?= esc_html__('Title', SMLD); ?>:
            <input class="widefat" id="<?= esc_attr($this->get_field_id('title')); ?>" name="<?= esc_attr($this->get_field_name('title')); ?>" type="text" value="<?= esc_attr($title); ?>" />
        </label>
    </p>
</div>
        <?php
    }
}
