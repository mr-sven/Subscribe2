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

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['title'] = wp_strip_all_tags(stripslashes($new_instance['title']));
        return $instance;
    }

    public function widget($args, $instance)
    {
        $title = empty($instance['title']) ? __('Subscribe Mini Widget', SMLD) : $instance['title'];

        echo wp_kses_post($args['before_widget']);
        if (! empty($title)) {
            echo wp_kses_post($args['before_title']) . esc_attr($title) . wp_kses_post($args['after_title']);
        }
        echo wp_kses_post($args['after_widget']);
    }

    public function form($instance)
    {
        $defaults = [
            'title' => __('Subscribe Mini Widget', SMLD),
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        $title = htmlspecialchars($instance['title'], ENT_QUOTES);
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
