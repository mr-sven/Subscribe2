<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

class Frontend extends Core
{
    public function loaded()
    {
        parent::loaded();
        $this->options = get_option(SMOPTIONS);
        add_shortcode('subscribe-page', [$this, 'shortcode']);
    }

    public function shortcode($atts = [], $content = null, $shortcode_tag = '')
    {
        global $wpdb;

        // Anti spam sign up measure.
        if (isset($_POST['subscribe']) || isset($_POST['unsubscribe'])) {
            if (! empty($_POST['firstname']) || ! empty($_POST['lastname']) || (! empty($_POST['uri']) && 'http://' !== sanitize_url($_POST['uri']))) {
                // Looks like some invisible-to-user fields were changed; falsely report success.
                return '<p class="smini_message">' . esc_html__('A confirmation message is on its way!', 'subscribe-mini') . '</p>';
            }

            $email = sanitize_email($_POST['email']);
            if (false === $this->validate_email($email)) {
                return '<p class="smini_error">' . esc_html__('Sorry, but that does not look like an email address to me.', 'subscribe-mini') . '</p>';
            } elseif ($this->is_barred($email)) {
                return '<p class="smini_error">' . esc_html__('Sorry, email addresses at that domain are currently barred due to spam, please use an alternative email address.', 'subscribe-mini') . '</p>';
            } else {
                /*
                $ip = rest_is_ip_address($_POST['ip']) ? $_POST['ip'] : $this->get_remote_ip();
                if (is_int($this->lockout) && $this->lockout > 0) {
                    $date = current_datetime($this->lockout)->format('H:i:s.u');
                    $ips  = $wpdb->get_col(
                        $wpdb->prepare(
                            "SELECT ip FROM $wpdb->subscribe2 WHERE date = CURDATE() AND time > SUBTIME(CURTIME(), %s)",
                            $date
                        )
                    );

                    if (in_array($ip, $ips, true)) {
                        return __('Slow down, you move too fast.', 'subscribe-mini');
                    }
                }*/

                $active = $wpdb->get_var($wpdb->prepare("SELECT active FROM $wpdb->smini WHERE email = %s", $email));

                if (isset($_POST['subscribe'])) {
                    if ($active == null || $active == "0") {
                        if ($active == null) {
                            $wpdb->insert($wpdb->smini, ['email' => sanitize_email($email)]);
                        }
                        $status = $this->send_confirm('add', $email);

                        if ($status) {
                            return '<p class="smini_message">' . esc_html__('A confirmation message is on its way!', 'subscribe-mini') . '</p>';
                        } else {
                            return '<p class="smini_error">' . esc_html__('Sorry, there seems to be an error on the server. Please try again later.', 'subscribe-mini') . '</p>';
                        }
                    } else {
                        return '<p class="smini_error">' . esc_html__('That email address is already subscribed.', 'subscribe-mini') . '</p>';
                    }
                } elseif (isset($_POST['unsubscribe'])) {
                    // Is this email a subscriber?
                    if ($active == null) {
                        return '<p class="smini_error">' . esc_html__('That email address is not subscribed.', 'subscribe-mini') . '</p>';
                    } else {
                        $status = $this->send_confirm('del', $email);
                        if ($status) {
                            return '<p class="smini_message">' . esc_html__('A confirmation message is on its way!', 'subscribe-mini') . '</p>';
                        } else {
                            return '<p class="smini_error">' . esc_html__('Sorry, there seems to be an error on the server. Please try again later.', 'subscribe-mini') . '</p>';
                        }
                    }
                }
            }
        } elseif (isset($_GET['smini'])) {
            $code   = $_GET['smini'];
            $action = substr($code, 0, 1);
            $hash   = substr($code, 1, 32);
            $id     = intval(substr($code, 33));

            if ($id) {
                $email = sanitize_email($this->get_email($id));
                if (! $email || wp_hash($email) !== $hash) {
                    return '<p class="smini_error">' . esc_html__('No such email address is registered.', 'subscribe-mini') . '</p>';
                }
            } else {
                return '<p class="smini_error">' . esc_html__('No such email address is registered.', 'subscribe-mini') . '</p>';
            }

            $active = $wpdb->get_var($wpdb->prepare("SELECT active FROM $wpdb->smini WHERE email = %s", $email));

            if ('1' === $action) {
                if ('1' !== $active) {
                    $wpdb->update($wpdb->smini, ['active' => 1], ['id' => (int)$id]);
                    if ('subs' === $this->options[SM_SETTING_ADMIN_EMAIL] || 'both' === $this->options[SM_SETTING_ADMIN_EMAIL]) {
                        $this->admin_email('subscribe', $email);
                    }
                }
                return '<p class="smini_message">' . __('You have successfully subscribed!', 'subscribe-mini') . '</p>';
            } elseif ('0' === $action) {
                if ('0' !== $active) {
                    $wpdb->delete($wpdb->smini, ['id' => (int)$id]);
                    if ('unsubs' === $this->options[SM_SETTING_ADMIN_EMAIL] || 'both' === $this->options[SM_SETTING_ADMIN_EMAIL]) {
                        $this->admin_email('unsubscribe', $email);
                    }
                }
                return '<p class="smini_message">' . __('You have successfully unsubscribed!', 'subscribe-mini') . '</p>';
            }
        }

        return "";
    }

    public function validate_email($email)
    {
        // Check the formatting is correct.
        if (function_exists('filter_var') && false === filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        $domain = explode('@', $email, 2);
        if (function_exists('idn_to_ascii')) {
            $check_domain = idn_to_ascii($domain[1], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46);
        } else {
            $check_domain = $domain[1];
        }

        if (true === checkdnsrr($check_domain, 'MX')) {
            return $email;
        }

        return false;
    }

    public function is_barred($email = '')
    {
        if (empty($email)) {
            return false;
        }

        list($user, $domain) = explode('@', $email, 2);

        $domain = '@' . $domain;
        foreach (preg_split('/[\s,]+/', $this->options['barred']) as $barred_domain) {
            if (false !== strpos($barred_domain, '!')) {
                $url   = explode('.', str_replace('!', '', $barred_domain));
                $count = count($url);

                // Make sure our exploded domain has at least 2 components e.g. yahoo.*
                if ($count < 2) {
                    continue;
                }

                for ($i = 0; $i < $count; $i++) {
                    if ('*' === $url[$i]) {
                        unset($url[$i]);
                    }
                }

                $new_barred_domain = '@' . strtolower(trim(implode('.', $url)));
                if (false !== strpos($barred_domain, '*')) {
                    $new_barred_subdomain = '.' . strtolower(trim(implode('.', $url)));
                    if (false !== stripos($domain, $new_barred_domain) || false !== stripos($domain, $new_barred_subdomain)) {
                        return false;
                    }
                } else {
                    if (false !== stripos($domain, $new_barred_domain)) {
                        return false;
                    }
                }
            }

            if (false === strpos($barred_domain, '!') && false !== strpos($barred_domain, '*')) {
                // Wildcard and explictly allowed checking.
                $url   = explode('.', str_replace('!', '', $barred_domain));
                $count = count($url);

                // Make sure our exploded domain has at least 2 components e.g. yahoo.*
                if ($count < 2) {
                    continue;
                }

                for ($i = 0; $i < $count; $i++) {
                    if ('*' === $url[$i]) {
                        unset($url[$i]);
                    }
                }

                $new_barred_domain    = '@' . strtolower(trim(implode('.', $url)));
                $new_barred_subdomain = '.' . strtolower(trim(implode('.', $url)));
                if (false !== stripos($domain, $new_barred_domain) || false !== stripos($domain, $new_barred_subdomain)) {
                    return true;
                }
            } else {
                // Direct domain string comparison.
                $barred_domain = '@' . $barred_domain;
                if (strtolower($domain) === strtolower(trim($barred_domain))) {
                    return true;
                }
            }
        }

        return false;
    }

    public function get_remote_ip()
    {
        $remote_ip = false;

        // In order of preference, with the best ones for this purpose first.
        $address_headers = array(
            'REMOTE_ADDR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_CLUSTER_CLIENT_IP',
        );

        foreach ($address_headers as $header) {
            if (array_key_exists($header, $_SERVER)) {
                // HTTP_X_FORWARDED_FOR can contain a chain of comma-separated
                // addresses. The first one is the original client. It can't be
                // trusted for authenticity, but we don't need to for this purpose.
                $address_chain = explode(',', $_SERVER[$header]);
                $remote_ip     = trim($address_chain[0]);
                break;
            }
        }

        return $remote_ip;
    }

    public function send_confirm($action = '', $email = '')
    {
        if (! $email || empty($action)) {
            return false;
        }

        $id = $this->get_id($email);
        if (! $id) {
            return false;
        }

        $link = get_option('home');
        if ($this->options[SM_SETTING_SUB_PAGE] > 0) {
            $link = get_permalink($this->options[SM_SETTING_SUB_PAGE]);
        }

        $param = '';
        if ('add' === $action) {
            $param .= '1';
        } elseif ('del' === $action) {
            $param .= '0';
        }

        $param .= wp_hash($email);
        $param .= $id;

        $link = add_query_arg('smini', $param, $link);

        if ($this->options[SM_SETTING_IMPRINT_PAGE] > 0) {
            $imprinturl = get_permalink($this->options[SM_SETTING_IMPRINT_PAGE]);
        } else {
            $imprinturl = get_option('home');
        }
        $imprintlink = '<a href="' . $imprinturl . '">' . esc_html__('Imprint / Dataprotection', 'subscribe-mini') . '</a>';
        $link = '<a href="' . $link . '">' . $link . '</a>';

        $codes = [
            '{BLOGNAME}',
            '{BLOGLINK}',
            '{MYNAME}',
            '{EMAIL}',
            '{ACTION}',
            '{LINK}',
            '{IMPRINTLINK}',
            '{IMPRINTURL}',
        ];
        $replaces = [
            html_entity_decode(get_option('blogname'), ENT_QUOTES),
            get_option('home'),
            stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)),
            get_option('admin_email'),
            $action === 'add' ? __('Subscribe', 'subscribe-mini') : __('Unsubscribe', 'subscribe-mini'),
            $link,
            $imprintlink,
            $imprinturl,
        ];

        if ($action === 'add') {
            $message = $this->options[SM_SETTING_CONFIRMTEXT];
            $subject = $this->options[SM_SETTING_CONFIRMHEADER];
        } else {
            $message = $this->options[SM_SETTING_UNCONFIRMTEXT];
            $subject = $this->options[SM_SETTING_UNCONFIRMHEADER];
        }

        $message = str_replace($codes, $replaces, stripslashes($message));
        $subject = str_replace($codes, $replaces, $subject);
        $message = wpautop($message);
        list($subject, $message, $headers) = $this->prepare_mail($subject, $message, 'html');

        return wp_mail($email, $subject, $message, $headers);
    }

    public function get_id($email = '')
    {
        global $wpdb;

        if (! $email) {
            return false;
        }

        return $wpdb->get_var($wpdb->prepare("SELECT id FROM $wpdb->smini WHERE email=%s", $email));
    }

    public function get_email($id = 0)
    {
        global $wpdb;

        if (! $id) {
            return false;
        }

        return $wpdb->get_var($wpdb->prepare("SELECT email FROM $wpdb->smini WHERE id=%d", $id));
    }

    public function substitute_subscribe($string = '', $digest_post_ids = array())
    {
        if (empty($string)) {
            return;
        }
    }

    public function admin_email($action, $email)
    {
        if (! in_array($action, array('subscribe', 'unsubscribe'), true)) {
            return false;
        }

        $blogname = get_option('blogname');
        $subject  = empty($blogname) ? '[' . stripslashes(html_entity_decode($blogname, ENT_QUOTES)) . '] ' : '';
        if ('subscribe' === $action) {
            $subject .= __('New Subscription', 'subscribe-mini');
            $message  = $email . ' ' . __('subscribed to email notifications!', 'subscribe-mini');
        } elseif ('unsubscribe' === $action) {
            $subject .= __('New Unsubscription', 'subscribe-mini');
            $message  = $email . ' ' . __('unsubscribed from email notifications!', 'subscribe-mini');
        }

        $subject = html_entity_decode($subject, ENT_QUOTES);
        $role    = array(
            'fields' => array(
                'user_email',
            ),
            'role'   => 'administrator',
        );

        $wp_user_query = get_users($role);
        foreach ($wp_user_query as $user) {
            $recipients[] = $user->user_email;
        }

        $recipients = apply_filters('s2_admin_email', $recipients, $action);
        $headers    = $this->headers();

        // Send individual emails so we don't reveal admin emails to each other.
        foreach ($recipients as $recipient) {
            $status = wp_mail($recipient, $subject, $message, $headers);
        }
    }
}
