<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

define('SM_SETTINGS_GROUP', 'smini_group');

class Admin
{
    /**
     * @var array
     */
    private $options;

    public function __construct()
    {
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_menu()
    {
        add_menu_page(__('Subscribe Mini', SMLD), __('Subscribe Mini', SMLD), apply_filters('smin_capability', 'read', 'user'), 'smini_subscribers', null, 'dashicons-buddicons-pm', 30);
        add_submenu_page('smini_subscribers', __('Subscribers', SMLD), __('Subscribers', SMLD), apply_filters('smin_capability', 'manage_options', 'manage'), 'smini_subscribers', [$this, 'subscribers_page']);
        add_submenu_page('smini_subscribers', __('Options', SMLD), __('Options', SMLD), apply_filters('smin_capability', 'manage_options', 'options'), 'smini_options', array($this, 'options_page'));
        add_submenu_page('smini_subscribers', __('Templates', SMLD), __('Templates', SMLD), apply_filters('smin_capability', 'manage_options', 'templates'), 'smini_templates', array($this, 'templates_page'));
    }

    public function admin_init()
    {
        $this->options = get_option(SMOPTIONS);
        register_setting(SM_SETTINGS_GROUP, SMOPTIONS, [$this, 'check_values']);
    }

    public function subscribers_page()
    {
        //require_once S2PATH . 'admin/subscribers.php';
    }

    public function options_page()
    {
        add_settings_section('smini_settings', '', null, 'smini_options');
        add_settings_field(
            'admin_email',
            __('Send Admins notifications for new', SMLD),
            [$this, 'create_radio'],
            'smini_options',
            'smini_settings',
            [
                'label_for' => 'admin_email',
                'class' => 'admin_email',
                'options' => [
                    [
                        'value' => 'subs',
                        'label' => __('Subscriptions', SMLD)
                    ],
                    [
                        'value' => 'unsubs',
                        'label' => __('Unsubscriptions', SMLD)
                    ],
                    [
                        'value' => 'both',
                        'label' => __('Both', SMLD)
                    ],
                    [
                        'value' => 'none',
                        'label' => __('Neither', SMLD)
                    ]
                ]
            ]
        );
        add_settings_field('sub_page', __('Set default Subscribe2 page as', SMLD), [$this, 'create_page_dropdown'], 'smini_options', 'smini_settings', ['label_for' => 'sub_page', 'class' => 'sub_page']);

        require_once __DIR__ . '/../pages/options.php';
    }

    public function templates_page()
    {
        //require_once S2PATH . 'admin/templates.php';
    }

    public function check_values($input)
    {
        $new_input = [];

        return $new_input;
    }

    public function create_radio($args)
    {
        echo '<fieldset>';

        foreach ($args['options'] as $option) {
            $checked = checked($this->options[$args['label_for']], $option['value'], false);
            printf(
                '<input type="radio" name="%1$s" id="%4$s" value="%2$s" %5$s /><label for="%4$s">%3$s</label>&nbsp;',
                $args['label_for'],
                $option['value'],
                $option['label'],
                $args['label_for'].'-'.$option['value'],
                $checked
            );
        }

        echo '</fieldset>';
    }

    public function create_page_dropdown($args)
    {
        wp_dropdown_pages([
            'name' => $args['label_for'],
            'echo' => 1,
            'show_option_none' => __( '&mdash; select &mdash;', SMLD),
            'option_none_value' => 0,
            'selected' => $this->options[$$args['label_for']]
        ]);
    }

}
