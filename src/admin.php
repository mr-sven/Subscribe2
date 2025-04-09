<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

define('SM_SETTINGS_GROUP', 'smini_group');

class Admin extends Core
{
    private const OPTIONS_PAGE = 'smini_options';
    private const OPTIONS_SECTION = 'smini_settings';
    /**
     * @var array
     */
    private $options;

    public function loaded()
    {
        parent::loaded();
        add_action('admin_menu', [$this, 'admin_menu']);
        add_action('admin_init', [$this, 'admin_init']);
    }

    public function admin_menu()
    {
        add_menu_page(__('Subscribe Mini', SMLD), __('Subscribe Mini', SMLD), apply_filters('smin_capability', 'read', 'user'), 'smini_subscribers', null, 'dashicons-buddicons-pm', 30);
        $subscribers_page = add_submenu_page('smini_subscribers', __('Subscribers', SMLD), __('Subscribers', SMLD), apply_filters('smin_capability', 'manage_options', 'manage'), 'smini_subscribers', [$this, 'subscribers_page']);
        add_action("load-" . $subscribers_page, [$this, 'subscribers_page_load']);
        add_submenu_page('smini_subscribers', __('Options', SMLD), __('Options', SMLD), apply_filters('smin_capability', 'manage_options', 'options'), static::OPTIONS_PAGE, array($this, 'options_page'));
        add_submenu_page('smini_subscribers', __('Templates', SMLD), __('Templates', SMLD), apply_filters('smin_capability', 'manage_options', 'templates'), 'smini_templates', array($this, 'templates_page'));
    }

    public function admin_init()
    {
        $this->options = get_option(SMOPTIONS, [
            SM_SETTING_ADMIN_EMAIL => 'subs',
            SM_SETTING_SUB_PAGE => 0,
            SM_SETTING_UNSUB_PAGE => 0,
            SM_SETTING_BARRED => '',
            SM_SETTING_MAILTEXT => __("{BLOGNAME} has posted a new item, '{TITLE}'\n\n{POST}\n\nYou may view the latest post at\n{PERMALINK}\n\nYou received this e-mail because you asked to be notified when new updates are posted.\nBest regards,\n{MYNAME}\n{EMAIL}", SMLD),
            SM_SETTING_MAILHEADER => '[{BLOGNAME}] {TITLE}',
            SM_SETTING_CONFIRMTEXT => __("{BLOGNAME} has received a request to {ACTION} for this email address. To complete your request please click on the link below:\n\n{LINK}\n\nIf you did not request this, please feel free to disregard this notice!\n\nThank you,\n{MYNAME}.", SMLD),
            SM_SETTING_CONFIRMHEADER => '[{BLOGNAME}] ' . __('Please confirm your request', SMLD)
        ]);
        register_setting(SM_SETTINGS_GROUP, SMOPTIONS, [$this, 'check_values']);
    }

    public function subscribers_page_load()
    {
        if (isset($_REQUEST['action']) && '-1' !== $_REQUEST['action']) {
            $this->subscribers_page_action($_REQUEST['action']);
        }

        $args = array(
            'label'   => __('Number of subscribers per page: ', SMLD),
            'default' => 25,
            'option'  => 'subscribers_per_page',
        );

        add_screen_option('per_page', $args);
    }

    public function subscribers_page_action($action)
    {
        global $wpdb;

        if (!in_array($action, ['delete_all', 'delete', 'add'], true)) {
            return;
        }

        if (!isset($_REQUEST['_wpnonce']) ||
            empty($_REQUEST['_wpnonce']) ||
            !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-subscribers')) {
            return;
        }

        switch ($action)
        {
            case 'delete_all':
                if (isset($_REQUEST['element']) && is_array($_REQUEST['element'])) {
                    foreach ($_REQUEST['element'] as $id) {
                        $wpdb->delete($wpdb->smini, ['id' => (int)$id]);
                    }
                }
                break;

            case 'delete':
                $wpdb->delete($wpdb->smini, ['id' => (int)$_REQUEST['element']]);
                wp_redirect(admin_url('admin.php?page=smini_subscribers'));
                break;

            case 'add':
                if (isset($_REQUEST['addresses'])) {
                    $addresses = explode("\n", $_REQUEST['addresses']);
                    foreach ($addresses as $address) {
                        $wpdb->insert($wpdb->smini, ['email' => sanitize_email($address), 'active' => 1, 'active_ts' => current_time('mysql')]);
                    }
                }
                break;
        }
    }

    public function subscribers_page()
    {
        require_once __DIR__ . '/subscribers-table.php';
        require_once __DIR__ . '/../pages/subscribers.php';
    }

    public function options_page()
    {
        add_settings_section(static::OPTIONS_SECTION, '', null, static::OPTIONS_PAGE);
        add_settings_field(
            SM_SETTING_ADMIN_EMAIL,
            __('Send Admins notifications for new', SMLD),
            [$this, 'create_radio'],
            static::OPTIONS_PAGE,
            static::OPTIONS_SECTION,
            [
                'label_for' => SMOPTIONS . '[' . SM_SETTING_ADMIN_EMAIL . ']',
                'key' => SM_SETTING_ADMIN_EMAIL,
                'class' => SM_SETTING_ADMIN_EMAIL,
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
        add_settings_field(
            SM_SETTING_SUB_PAGE,
            __('Set default Subscribe Mini page as', SMLD),
            [$this, 'create_page_dropdown'],
            static::OPTIONS_PAGE,
            static::OPTIONS_SECTION,
            [
                'label_for' => SMOPTIONS . '[' . SM_SETTING_SUB_PAGE . ']',
                'key' => SM_SETTING_SUB_PAGE,
                'class' => SM_SETTING_SUB_PAGE
            ]
        );
        add_settings_field(
            SM_SETTING_UNSUB_PAGE,
            __('Set Subscribe Mini unsubscribe page', SMLD),
            [$this, 'create_page_dropdown'],
            static::OPTIONS_PAGE,
            static::OPTIONS_SECTION,
            [
                'label_for' => SMOPTIONS . '[' . SM_SETTING_UNSUB_PAGE . ']',
                'key' => SM_SETTING_UNSUB_PAGE,
                'class' => SM_SETTING_UNSUB_PAGE
            ]
        );
        add_settings_field(
            SM_SETTING_BARRED,
            __('Barred Domains', SMLD),
            [$this, 'create_textarea'],
            static::OPTIONS_PAGE,
            static::OPTIONS_SECTION,
            [
                'label_for' => SMOPTIONS . '[' . SM_SETTING_BARRED . ']',
                'key' => SM_SETTING_BARRED,
                'class' => SM_SETTING_BARRED,
                'hint' => [
                    __('Enter domains to bar for public subscriptions, wildcards (*) and exceptions (!) are allowed', SMLD),
                    __('Use a new line for each entry and omit the "@" symbol, for example !email.com, hotmail.com, yahoo.*', SMLD)
                ]
            ]
        );

        require_once __DIR__ . '/../pages/options.php';
    }

    public function templates_page()
    {
        require_once __DIR__ . '/../pages/templates.php';
    }

    public function check_values($input)
    {
        $new_input = [];

        $all_settings = [
            SM_SETTING_ADMIN_EMAIL,
            SM_SETTING_SUB_PAGE,
            SM_SETTING_UNSUB_PAGE,
            SM_SETTING_BARRED,
            SM_SETTING_MAILTEXT,
            SM_SETTING_MAILHEADER,
            SM_SETTING_CONFIRMTEXT,
            SM_SETTING_CONFIRMHEADER
        ];

        $num_settings = [
            SM_SETTING_SUB_PAGE,
            SM_SETTING_UNSUB_PAGE
        ];

        $textarea_settings = [
            SM_SETTING_BARRED,
            SM_SETTING_MAILTEXT,
            SM_SETTING_CONFIRMTEXT
        ];

        $textbox_settings = [
            SM_SETTING_MAILHEADER,
            SM_SETTING_CONFIRMHEADER,
            SM_SETTING_ADMIN_EMAIL
        ];

        foreach ($all_settings as $key) {
            if (isset($this->options[$key])) {
                $new_input[$key] = $this->options[$key];
            }

            if (in_array($key, $num_settings, true)) {
                // Numerical inputs fixed for old option names.
                if (is_numeric($input[$key]) && intval($input[$key]) >= 0) {
                    $new_input[$key] = intval($input[$key]);
                }
            } elseif (in_array($key, $textarea_settings, true)) {
                if (isset($input[$key])) {
                    $new_input[$key] = sanitize_textarea_field($input[$key]);
                }
            } elseif (in_array($key, $textbox_settings, true)) {
                if (isset($input[$key])) {
                    $new_input[$key] = sanitize_text_field($input[$key]);
                }
            }
        }

        return $new_input;
    }

    public function create_radio($args)
    {
        echo '<fieldset>';

        foreach ($args['options'] as $option) {
            $checked = checked($this->options[$args['key']], $option['value'], false);
            printf(
                '<input type="radio" name="%1$s" id="%2$s" value="%3$s" %4$s /><label for="%2$s">%5$s</label>&nbsp;',
                $args['label_for'], // name
                $args['key'] . '-' . $option['value'], // id
                $option['value'], // value
                $checked, // checked
                esc_html($option['label']), // label
            );
        }

        echo '</fieldset>';
    }

    public function create_page_dropdown($args)
    {
        wp_dropdown_pages([
            'name' => $args['label_for'],
            'echo' => 1,
            'show_option_none' => __('&mdash; select &mdash;', SMLD),
            'option_none_value' => 0,
            'selected' => $this->options[$args['key']]
        ]);
    }

    public function create_textarea($args)
    {
        $textarea = '<textarea name="' . $args['label_for'] . '" id="' . $args['label_for'] . '" rows="4" cols="60" style="width: 98%;">';
        $textarea .= esc_textarea($this->options[$args['key']]);
        $textarea .= '</textarea>';

        if (isset($args['hint'])) {
            foreach ($args['hint'] as $hint) {
                $textarea .= '<p class="description">' . esc_html($hint) . '</p>';
            }
        }

        echo $textarea;
    }
}
