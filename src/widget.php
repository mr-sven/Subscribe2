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
        $options = get_option(SMOPTIONS);

        $title = empty($instance['title']) ? __('Subscribe Mini Widget', SMLD) : $instance['title'];

        echo wp_kses_post($args['before_widget']);
        if (! empty($title)) {
            echo wp_kses_post($args['before_title']) . esc_attr($title) . wp_kses_post($args['after_title']);
        }

        $action = '';
        if (is_numeric($args['id'])) {
            $action = ' action="' . get_permalink($args['id']) . '"';
        } elseif ('home' === $args['id']) {
            $action = ' action="' . get_site_url() . '"';
        } elseif ($options[SM_SETTING_SUB_PAGE] > 0) {
            $action = ' action="' . get_permalink($options[SM_SETTING_SUB_PAGE]) . '"';
        }

        $value = __('Enter email address...', SMLD);
?>
        <div class="smini_widget">
            <?= esc_html__('(Un)Subscribe to Posts', SMLD); ?>
            <form method="post" <?= $action ?>>
                <input type="hidden" name="ip" value="<?= esc_attr($_SERVER['REMOTE_ADDR']) ?>" />
                <span style="display:none !important">
                    <label for="firstname"><?= __('Leave This Blank:', SMLD) ?></label><input type="text" id="firstname" name="firstname" />
                    <label for="lastname"><?= __('Leave This Blank Too:', SMLD) ?></label><input type="text" id="lastname" name="lastname" />
                    <label for="uri"><?= __('Do Not Change This:', SMLD) ?></label><input type="text" id="uri" name="uri" value="http://" />
                </span>
                <p>
                    <label for="s2email"><?= esc_html__('Your email:', SMLD) ?></label><br>
                    <input type="email" name="email" id="s2email" value="<?= esc_attr($value) ?>" onfocus="if (this.value === '<?= $value ?>') {this.value = '';}" onblur="if (this.value === '') {this.value = '<?= $value ?>';}" />
                    <input type="submit" name="subscribe" value="<?= esc_html__('Subscribe', SMLD) ?>" />&nbsp;<input type="submit" name="unsubscribe" value="<?= esc_html__('Unsubscribe', SMLD) ?>" />
                </p>
            </form>
        </div>
    <?php
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
