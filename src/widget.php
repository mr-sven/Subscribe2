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
            'description'                 => esc_html__('Sidebar Widget for Subscribe Mini', 'subscribe-mini'),
            'show_instance_in_rest'       => true,
            'customize_selective_refresh' => true,
        );

        $control_ops = array(
            'width'  => 250,
            'height' => 300,
        );

        parent::__construct(
            'smini_widget',
            esc_html__('Subscribe Mini Widget', 'subscribe-mini'),
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
        $options = get_option(SMOPTIONS);

        $title = empty($instance['title']) ? __('Subscribe Mini Widget', 'subscribe-mini') : $instance['title'];

        echo wp_kses_post($args['before_widget']);
        if (! empty($title)) {
            echo wp_kses_post($args['before_title']) . esc_attr($title) . wp_kses_post($args['after_title']);
        }

        $action = '';
        if ($options[SM_SETTING_SUB_PAGE] > 0) {
            $action = ' action="' . get_permalink($options[SM_SETTING_SUB_PAGE]) . '"';
        } elseif (is_numeric($args['id'])) {
            $action = ' action="' . get_permalink($args['id']) . '"';
        } elseif ('home' === $args['id']) {
            $action = ' action="' . get_site_url() . '"';
        }

        $value = __('Enter email address...', 'subscribe-mini');
?>
        <div class="smini_widget">
            <?= esc_html__('(Un)Subscribe to Posts', 'subscribe-mini'); ?>
            <form method="post" <?= $action ?>>
                <input type="hidden" name="ip" value="<?= esc_attr($_SERVER['REMOTE_ADDR']) ?>" />
                <span style="display:none !important">
                    <label for="firstname"><?= __('Leave This Blank:', 'subscribe-mini') ?></label><input type="text" id="firstname" name="firstname" />
                    <label for="lastname"><?= __('Leave This Blank Too:', 'subscribe-mini') ?></label><input type="text" id="lastname" name="lastname" />
                    <label for="uri"><?= __('Do Not Change This:', 'subscribe-mini') ?></label><input type="text" id="uri" name="uri" value="http://" />
                </span>
                <p>
                    <label for="s2email"><?= esc_html__('Your email:', 'subscribe-mini') ?></label><br>
                    <input type="email" name="email" id="s2email" value="<?= esc_attr($value) ?>" onfocus="if (this.value === '<?= $value ?>') {this.value = '';}" onblur="if (this.value === '') {this.value = '<?= $value ?>';}" />
                    <input type="submit" name="subscribe" value="<?= esc_html__('Subscribe', 'subscribe-mini') ?>" />&nbsp;<input type="submit" name="unsubscribe" value="<?= esc_html__('Unsubscribe', 'subscribe-mini') ?>" />
                </p>
            </form>
        </div>
    <?php
        echo wp_kses_post($args['after_widget']);
    }

    public function form($instance)
    {
        $defaults = [
            'title' => __('Subscribe Mini Widget', 'subscribe-mini'),
        ];

        $instance = wp_parse_args((array) $instance, $defaults);
        $title = htmlspecialchars($instance['title'], ENT_QUOTES);
    ?>
        <div>
            <p>
                <label for="<?= esc_attr($this->get_field_id('title')); ?>">
                    <?= esc_html__('Title', 'subscribe-mini'); ?>:
                    <input class="widefat" id="<?= esc_attr($this->get_field_id('title')); ?>" name="<?= esc_attr($this->get_field_name('title')); ?>" type="text" value="<?= esc_attr($title); ?>" />
                </label>
            </p>
        </div>
<?php
    }
}
