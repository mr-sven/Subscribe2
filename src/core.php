<?php

declare(strict_types=1);

namespace SMini;

require_once __DIR__ . '/Minify/CSS.php';

defined('ABSPATH') or die('NO!');

abstract class Core
{
    /**
     * @var array
     */
    protected $options = null;

    public function __construct()
    {
        Setup::prepare();
        add_action('plugins_loaded', [$this, 'loaded']);
        add_action('init', [$this, 'load_translations']);
    }

    public function load_translations()
    {
        load_plugin_textdomain('subscribe-mini', false, SMPLUGINDIR . '/languages');
    }

    public function loaded()
    {
        add_action('widgets_init', [$this, 'widget_init']);

        foreach (['new', 'draft', 'auto-draft', 'pending', 'private', 'future'] as $status) {
            add_action("{$status}_to_publish", [$this, 'publish']);
        }
    }

    public function widget_init()
    {
        require_once __DIR__ . "/widget.php";
        register_widget(Widget::class);
    }

    public function publish($post, $preview = null)
    {
        global $wpdb;
        $this->options ??= get_option(SMOPTIONS);

        if (! $post || $post->post_type !== 'post') {
            return $post;
        }

        if ($preview == null) {
            if ($post->post_password !== '' || $post->post_status !== 'publish') {
                return $post;
            }
        }

        if (function_exists('get_user_locale') && get_user_locale() !== get_locale()) {
            switch_to_locale(get_locale());
            $locale_switched = true;
        }

        if (isset($locale_switched) && true === $locale_switched) {
            switch_to_locale(get_user_locale());
        }

        $author     = get_userdata($post->post_author);
        $authorname = html_entity_decode(apply_filters('the_author', $author->display_name), ENT_QUOTES);

        if ($this->options[SM_SETTING_IMPRINT_PAGE] > 0) {
            $imprinturl = get_permalink($this->options[SM_SETTING_IMPRINT_PAGE]);
        } else {
            $imprinturl = get_option('home');
        }
        $imprintlink = '<a href="' . $imprinturl . '">' . esc_html__('Imprint / Dataprotection', 'subscribe-mini') . '</a>';

        $codes = [
            '{BLOGNAME}',
            '{BLOGLINK}',
            '{MYNAME}',
            '{EMAIL}',
            '{IMPRINTLINK}',
            '{IMPRINTURL}',
            '{AUTHORNAME}',
            '{DATE}',
            '{TIME}',
            '{PERMAURL}',
            '{PERMALINK}',
            '{TITLE}',
            '{TITLETEXT}',
        ];
        $replaces = [
            html_entity_decode(get_option('blogname'), ENT_QUOTES),
            get_option('home'),
            stripslashes(html_entity_decode(get_option('blogname'), ENT_QUOTES)),
            get_option('admin_email'),
            $imprintlink,
            $imprinturl,
            $authorname,
            get_the_time(get_option('date_format'), $post),
            get_the_time('', $post),
            get_permalink($post->ID),
            '<a href="' . get_permalink($post->ID) . '">' . get_permalink($post->ID) . '</a>',
            '<a href="' . get_permalink($post->ID) . '">' . html_entity_decode($post->post_title, ENT_QUOTES) . '</a>',
            html_entity_decode($post->post_title, ENT_QUOTES),
        ];

        // Get email subject.
        $subject = html_entity_decode(stripslashes(wp_kses(str_replace($codes, $replaces, $this->options[SM_SETTING_MAILHEADER]), '')));

        // Get the message template.
        $mailtext = $this->options[SM_SETTING_MAILTEXT];
        $mailtext = stripslashes(str_replace($codes, $replaces, $mailtext));

        $gallid  = '[gallery id="' . $post->ID . '"';
        $content = str_replace('[gallery', $gallid, $post->post_content);

        // Remove the autoembed filter to remove iframes from notification emails.
        if (get_option('embed_autourls')) {
            global $wp_embed;

            $priority = has_filter('the_content', array(&$wp_embed, 'autoembed'));
            if (false !== $priority) {
                remove_filter('the_content', array(&$wp_embed, 'autoembed'), $priority);
            }
        }

        $content = apply_filters('the_content', $content);
        $content = str_replace(']]>', ']]&gt', $content);

        $html_excerpt = trim($post->post_excerpt);
        if ('' === $html_excerpt) {
            // No excerpt, is there a <!--more--> ?
            if (false !== strpos($content, '<!--more-->')) {
                list($html_excerpt, $more) = explode('<!--more-->', $content, 2);

                // Balance HTML tags and then strip leading and trailing whitespace.
                $html_excerpt = trim(balanceTags($html_excerpt, true));
            } else {
                // no <!--more-->, so create excerpt.
                $html_excerpt = $this->create_excerpt($content, true);
            }
        }

        $site_url = get_site_url();
        $html_excerpt = preg_replace_callback('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', function ($matches) use ($site_url) {
            if (!str_starts_with($matches[1], $site_url)) {
                return '';
            }
            $relative_path = str_replace($site_url, '', $matches[1]);

            if (!str_starts_with($relative_path, '/')) {
                $relative_path = '/' . $relative_path;
            }
            $path = ABSPATH . $relative_path;

            $type = pathinfo($path, PATHINFO_EXTENSION);
            $data = file_get_contents($path);
            $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            return '<img src="' . $base64 . '" alt="" data-rel="' . $path . '"/>';
        }, $html_excerpt);

        $html_excerpt_body  = wpautop($mailtext);
        $html_excerpt_body  = str_replace('{POST}', $html_excerpt, $html_excerpt_body);


        if ($preview != null) {
            $this->mail([$preview], $subject, $html_excerpt_body, 'html');
        } else {
            $recipients = $wpdb->get_results("SELECT id,email FROM $wpdb->smini WHERE active='1'") ?? [];
            $this->mail($recipients, $subject, $html_excerpt_body, 'html');
        }
    }

    public function create_excerpt($text, $html = false)
    {
        $excerpt = (false === $html) ? trim(wp_strip_all_tags(strip_shortcodes($text))) : strip_shortcodes($text);
        $words = explode(' ', $excerpt, 56);

        if (count($words) > 55) {
            array_pop($words);
            array_push($words, '[...]');
        }

        $excerpt =  implode(' ', $words);
        if (true === $html) {
            // Balance HTML tags and then strip leading and trailing whitespace.
            $excerpt = trim(balanceTags($excerpt, true));
        }

        return $excerpt;
    }

    public function mail($recipients = [], $subject = '', $message = '', $type = 'text', $attachments = [])
    {
        if (empty($recipients) || empty($message)) {
            return;
        }

        // Replace any escaped html symbols in subject then apply filter.
        $subject = wp_strip_all_tags(html_entity_decode($subject, ENT_QUOTES));

        if ('html' === $type) {
            $headers = $this->headers('html');

            remove_all_filters('wp_mail_content_type');
            add_filter('wp_mail_content_type', [$this, 'html_email']);

            $mailContainer = $this->options[SM_SETTING_MAIL_CONTAINER];
            if (!empty($mailContainer) && stripos($mailContainer, '{}') !== false) {
                $message = str_replace('{}', $message, $mailContainer);
            }

            $minifier = new \MatthiasMullie\Minify\CSS(get_stylesheet_directory() . '/style.css');
            $style = $minifier->minify();
            $mailtext = <<<EOT
            <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
            <html>
              <head>
                <title>{$subject}</title>
                <style>{$style}</style>
                <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
              </head>
              <body>

                    {$message}

              </body>
            </html>
            EOT;
        } else {
            $headers = $this->headers('text');

            remove_all_filters('wp_mail_content_type');
            add_filter('wp_mail_content_type', [$this, 'plain_email']);

            $mailtext  = wp_strip_all_tags(html_entity_decode($message, ENT_NOQUOTES));
        }

        foreach ($recipients as $recipient) {
            $email = trim($recipient->email);

            // Sanity check -- make sure we have a valid email.
            if (false === sanitize_email($email) || empty($email)) {
                continue;
            }

            $unsubscribe_url = $this->get_unsubscribe_url($email, $recipient->id);
            if (!empty($unsubscribe_url)) {
                $headers['List-Unsubscribe'] = 'List-Unsubscribe: <' . $unsubscribe_url . '>';

                if ($type == 'html') {
                    $unsubscribe_url = '<a href="' . $unsubscribe_url . '">' . __('Unsubscribe', 'subscribe-mini') . '</a>';
                }

                $mailtextOut = str_replace('{UNSUBLINK}', $unsubscribe_url, $mailtext);
            }
            else {
                unset($headers['List-Unsubscribe']);
                $mailtextOut = str_replace('{UNSUBLINK}', '', $mailtext);
            }

            $status = wp_mail($email, $subject, $mailtextOut, $headers, $attachments);
        }
    }

    public function headers($type = 'text')
    {
        $myname  = html_entity_decode(get_option('blogname'), ENT_QUOTES);

        $myemail = $this->options[SM_SETTING_REPLY_EMAIL];
        if (empty($myemail)) {
            $myemail = get_option('admin_email');
        }

        $char_set = get_option('blog_charset');
        if (function_exists('mb_encode_mimeheader')) {
            $header['From']     = mb_encode_mimeheader($myname, $char_set, 'Q') . ' <' . $myemail . '>';
            $header['Reply-To'] = mb_encode_mimeheader($myname, $char_set, 'Q') . ' <' . $myemail . '>';
        } else {
            $header['From']     = $myname . ' <' . $myemail . '>';
            $header['Reply-To'] = $myname . ' <' . $myemail . '>';
        }

        $header['Return-Path'] = '<' . $myemail . '>';
        $header['List-ID']     = html_entity_decode(get_option('blogname'), ENT_QUOTES) . ' <' . strtolower(esc_html($_SERVER['SERVER_NAME'])) . '>';
        if ('html' === $type) {
            // To send HTML mail, the Content-Type header must be set.
            $header['Content-Type'] = get_option('html_type') . '; charset="' . $char_set . '"';
        } elseif ('text' === $type) {
            $header['Content-Type'] = 'text/plain; charset="' . $char_set . '"';
        }

        // Collapse the headers using $key as the header name.
        foreach ($header as $key => $value) {
            $headers[$key] = $key . ': ' . $value;
        }

        return $headers;
    }

    /**
     * Function to set HTML Email in wp_mail().
     *
     * @return string
     */
    public function html_email()
    {
        return 'text/html';
    }

    /**
     * Function to set plain text Email in wp_mail().
     *
     * @return string
     */
    public function plain_email()
    {
        return 'text/plain';
    }

    public function get_unsubscribe_url($email, $id) {

        if ($this->options[SM_SETTING_SUB_PAGE] > 0) {
            $page_url = get_page_link($this->options[SM_SETTING_SUB_PAGE]);
            $page_url = add_query_arg('smini', "0" . wp_hash($email) . $id, $page_url);
            return $page_url;
        }
        return "";
    }
}
