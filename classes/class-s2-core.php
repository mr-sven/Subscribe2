<?php
require_once S2PATH . 'traits/ShortcodeTrait.php';
require_once S2PATH . 'classes/Minify/CSS.php';
use MatthiasMullie\Minify;
/**
 * Block editor handler class.
 */
class S2_Core {

    use Shortcode;

    /**
     * Subscribe options.
     *
     * @var array
     */
    public $subscribe2_options = array();

    /**
     * Check for block editor.
     *
     * @var array
     */
    public $block_editor = false;

    /**
     * State variable for affect processing.
     *
     * @var int
     */
    public $filtered = 0;

    /**
     * State variable for affect processing.
     *
     * @var int|null
     */
    public $post_count;

    /**
     * Post title used for substitute() function.
     *
     * @var string|null
     */
    public $post_title;

    /**
     * Post title used for substitute() function.
     *
     * @var string|null
     */
    public $post_title_text;

    /**
     * Post permalink used for substitute() function.
     *
     * @var string|null
     */
    public $permalink;

    /**
     * Post date used for substitute() function.
     *
     * @var string|null
     */
    public $post_date;

    /**
     * Post time used for substitute() function.
     *
     * @var string|null
     */
    public $post_time;

    /**
     * State myname used for substitute() function.
     *
     * @var string|null
     */
    public $myname;

    /**
     * State myemail used for substitute() function.
     *
     * @var string|null
     */
    public $myemail;

    /**
     * State author used for substitute() function.
     *
     * @var string|null
     */
    public $authorname;

    /**
     * Post category names used for substitute() function.
     *
     * @var array|null
     */
    public $post_cat_names;

    /**
     * Post tag names used for substitute() function.
     *
     * @var array|null
     */
    public $post_tag_names;
    public $script_debug;
    public $word_wrap;
    public $excerpt_length;
    public $site_switching;
    public $clean_interval;
    public $lockout;
    public $wp_release;
    public $preview_email;

    public function prepare_export( $subscribers ) {
        // Virtual
    }

    /**
     * Load plugin translations.
     *
     * @return void
     */
    public function load_translations() {
        load_plugin_textdomain( 'subscribe2', false, S2DIR );
        load_plugin_textdomain( 'subscribe2', false, S2DIR . 'languages/' );

        $locale = ( is_admin() && function_exists( 'get_user_locale' ) ) ? get_user_locale() : get_locale();
        $mofile = WP_LANG_DIR . '/subscribe2-' . apply_filters( 'plugin_locale', $locale, 'subscribe2' ) . '.mo';
        if ( file_exists( $mofile ) && is_readable( $mofile ) ) {
            load_textdomain( 'subscribe2', $mofile );
        }

        $mofile = WP_LANG_DIR . '/plugins/subscribe2-' . apply_filters( 'plugin_locale', $locale, 'subscribe2' ) . '.mo';
        if ( file_exists( $mofile ) && is_readable( $mofile ) ) {
            load_textdomain( 'subscribe2', $mofile );
        }
    }

    /**
     * Performs string substitutions for subscribe2 mail tags.
     *
     * @param string|null $string
     * @param array       $digest_post_ids
     *
     * @return mixed|void|null
     */
    public function substitute( $string = '', $digest_post_ids = array() ) {
        if ( empty( $string ) ) {
            return;
        }

        $string = str_replace( '{BLOGNAME}', html_entity_decode( get_option( 'blogname' ), ENT_QUOTES ), $string );
        $string = str_replace( '{BLOGLINK}', get_option( 'home' ), $string );
        $string = str_replace( '{TITLE}', stripslashes( $this->post_title ), $string );
        $string = str_replace( '{TITLETEXT}', stripslashes( $this->post_title_text ), $string );
        $string = str_replace( '{PERMAURL}', $this->get_tracking_link( $this->permalink ), $string );
        $link   = '<a href="' . $this->get_tracking_link( $this->permalink ) . '">' . $this->get_tracking_link( $this->permalink ) . '</a>';
        $string = str_replace( '{PERMALINK}', $link, $string );

        if ( strstr( $string, '{TINYLINK}' ) ) {
            $response = wp_safe_remote_get( 'http://tinyurl.com/api-create.php?url=' . rawurlencode( $this->get_tracking_link( $this->permalink ) ) );
            $tinylink = ! is_wp_error( $response ) ? wp_remote_retrieve_body( $response ) : '';

            if ( false !== $tinylink ) {
                $tlink  = '<a href="' . $tinylink . '">' . $tinylink . '</a>';
                $string = str_replace( '{TINYLINK}', $tlink, $string );
            } else {
                $string = str_replace( '{TINYLINK}', $link, $string );
            }
        }

        $string = str_replace( '{DATE}', $this->post_date, $string );
        $string = str_replace( '{TIME}', $this->post_time, $string );
        $string = str_replace( '{MYNAME}', stripslashes( $this->myname ), $string );
        $string = str_replace( '{EMAIL}', $this->myemail, $string );
        $string = str_replace( '{AUTHORNAME}', stripslashes( $this->authorname ), $string );
        $string = str_replace( '{CATS}', $this->post_cat_names, $string );
        $string = str_replace( '{TAGS}', $this->post_tag_names, $string );
        $string = str_replace( '{COUNT}', $this->post_count, $string );

        if ( ! empty( $digest_post_ids ) ) {
            return apply_filters( 's2_custom_keywords', $string, $digest_post_ids );
        } else {
            return apply_filters( 's2_custom_keywords', $string );
        }
    }

    /**
     * Delivers email to recipients in HTML or plaintext.
     *
     * @param array  $recipients
     * @param string $subject
     * @param string $message
     * @param string $type
     * @param array  $attachments
     *
     * @return bool|mixed|void
     */
    public function mail( $recipients = array(), $subject = '', $message = '', $type = 'text', $attachments = array() ) {
        if ( empty( $recipients ) || empty( $message ) ) {
            return;
        }

        // Replace any escaped html symbols in subject then apply filter.
        $subject = wp_strip_all_tags( html_entity_decode( $subject, ENT_QUOTES ) );
        $subject = apply_filters( 's2_email_subject', $subject );

        if ( 'html' === $type ) {
            $headers = $this->headers( 'html' );

            remove_all_filters( 'wp_mail_content_type' );
            add_filter( 'wp_mail_content_type', array( $this, 'html_email' ) );

            if ( 'yes' === $this->subscribe2_options['stylesheet'] ) {
                $minifier = new Minify\CSS(get_stylesheet_directory().'/style.css');
                $style = $minifier->minify();
                $mailtext = apply_filters( 's2_html_email', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head><title>' . $subject . '</title><style>'.$style.'</style><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body>' . $message . '</body></html>', $subject, $message ); // phpcs:ignore WordPress.WP.EnqueuedResources
            } else {
                $mailtext = apply_filters( 's2_html_email', '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head><title>' . $subject . '</title></head><body>' . $message . '</body></html>', $subject, $message );
            }
        } else {
            $headers = $this->headers( 'text' );

            remove_all_filters( 'wp_mail_content_type' );
            add_filter( 'wp_mail_content_type', array( $this, 'plain_email' ) );

            $message  = wp_strip_all_tags( html_entity_decode( $message, ENT_NOQUOTES ) );
            $mailtext = apply_filters( 's2_plain_email', $message );
        }

        // Construct BCC headers for sending or send individual emails.
        $bcc = '';
        natcasesort( $recipients );
        if ( function_exists( 'wpmq_mail' ) || 1 === $this->subscribe2_options['bcclimit'] || 1 === count( $recipients ) ) {
            // BCCLimit is 1 so send individual emails or we only have 1 recipient.
            foreach ( $recipients as $recipient ) {
                $recipient = trim( $recipient );

                // Sanity check -- make sure we have a valid email.
                if ( false === sanitize_email( $recipient ) || empty( $recipient ) ) {
                    continue;
                }

                // Parse unsubscribe shortcode.
                $mailtext = $this->parse_unsubscribe_link( $mailtext, $recipient, $type );

                // Use the mail queue provided we are not sending a preview.
                if ( function_exists( 'wpmq_mail' ) && ! isset( $this->preview_email ) ) {
                    $status = wp_mail( $recipient, $subject, $mailtext, $headers, $attachments, 0 );
                } else {
                    $status = wp_mail( $recipient, $subject, $mailtext, $headers, $attachments );
                }
            }

            return true;
        } elseif ( 0 === $this->subscribe2_options['bcclimit'] ) {
            // We're using BCCLimit.
            foreach ( $recipients as $recipient ) {
                $recipient = trim( $recipient );

                // Sanity check -- make sure we have a valid email.
                if ( false === is_email( $recipient ) ) {
                    continue;
                }

                // And NOT the sender's email, since they'll get a copy anyway.
                if ( ! empty( $recipient ) && $this->myemail !== $recipient ) {
                    $bcc .= empty( $bcc ) ? "Bcc: $recipient" : ", $recipient"; // Bcc Headers now constructed by phpmailer class
                }
            }

            $headers .= "$bcc\n";
        } else {
            // We're using BCCLimit.
            $count = 1;
            $batch = array();
            foreach ( $recipients as $recipient ) {
                $recipient = trim( $recipient );

                // Sanity check -- make sure we have a valid email.
                if ( false === is_email( $recipient ) ) {
                    continue;
                }

                // And NOT the sender's email, since they'll get a copy anyway.
                if ( ! empty( $recipient ) && $this->myemail !== $recipient ) {
                    $bcc .= empty( $bcc ) ? "Bcc: $recipient" : ", $recipient"; // Bcc Headers now constructed by phpmailer class
                }

                if ( $this->subscribe2_options['bcclimit'] === $count ) {
                    $count   = 0;
                    $batch[] = $bcc;
                    $bcc     = '';
                }

                $count++;
            }

            // Add any partially completed batches to our batch array.
            if ( ! empty( $bcc ) ) {
                $batch[] = $bcc;
            }
        }

        // Rewind the array, just to be safe.
        reset( $recipients );

        // Ensure body is wrapped at 78 characters for RFC 5322.
        $mailtext = wordwrap( $mailtext, $this->word_wrap, "\n" );

        // Actually send mail.
        if ( ! empty( $batch ) ) {
            foreach ( $batch as $bcc ) {
                $newheaders = $headers . "$bcc\n";
                $status     = wp_mail( $this->myemail, $subject, $mailtext, $newheaders, $attachments );
            }
        } else {
            $status = wp_mail( $this->myemail, $subject, $mailtext, $headers, $attachments );
        }

        return $status;
    }

    /**
     * Construct standard set of email headers.
     *
     * @param string $type
     *
     * @return string
     */
    public function headers( $type = 'text' ) {
        if ( empty( $this->myname ) || empty( $this->myemail ) ) {
            if ( 'blogname' === $this->subscribe2_options['sender'] ) {
                $this->myname  = html_entity_decode( get_option( 'blogname' ), ENT_QUOTES );
                $this->myemail = get_option( 'admin_email' );
            } else {
                $admin         = $this->get_userdata( $this->subscribe2_options['sender'] );
                $this->myname  = html_entity_decode( $admin->display_name, ENT_QUOTES );
                $this->myemail = $admin->user_email;

                // Fail safe to ensure sender details are not empty.
                if ( empty( $this->myname ) ) {
                    $this->myname = html_entity_decode( get_option( 'blogname' ), ENT_QUOTES );
                }

                if ( empty( $this->myemail ) ) {
                    // Get the site domain and get rid of www.
                    $sitename = strtolower( esc_html( $_SERVER['SERVER_NAME'] ) );
                    if ( 'www.' === substr( $sitename, 0, 4 ) ) {
                        $sitename = substr( $sitename, 4 );
                    }

                    $this->myemail = 'wordpress@' . $sitename;
                }
            }
        }

        $char_set = get_option( 'blog_charset' );
        if ( function_exists( 'mb_encode_mimeheader' ) ) {
            $header['From']     = mb_encode_mimeheader( $this->myname, $char_set, 'Q' ) . ' <' . $this->myemail . '>';
            $header['Reply-To'] = mb_encode_mimeheader( $this->myname, $char_set, 'Q' ) . ' <' . $this->myemail . '>';
        } else {
            $header['From']     = $this->myname . ' <' . $this->myemail . '>';
            $header['Reply-To'] = $this->myname . ' <' . $this->myemail . '>';
        }

        $header['Return-Path'] = '<' . $this->myemail . '>';
        $header['List-ID']     = html_entity_decode( get_option( 'blogname' ), ENT_QUOTES ) . ' <' . strtolower( esc_html( $_SERVER['SERVER_NAME'] ) ) . '>';
        if ( 'html' === $type ) {
            // To send HTML mail, the Content-Type header must be set.
            $header['Content-Type'] = get_option( 'html_type' ) . '; charset="' . $char_set . '"';
        } elseif ( 'text' === $type ) {
            $header['Content-Type'] = 'text/plain; charset="' . $char_set . '"';
        }

        // Apply header filter to allow on-the-fly amendments.
        $header = apply_filters( 's2_email_headers', $header );
        // Collapse the headers using $key as the header name.
        foreach ( $header as $key => $value ) {
            $headers[ $key ] = $key . ': ' . $value;
        }

        $headers  = implode( "\n", $headers );
        $headers .= "\n";

        return $headers;
    }

    /**
     * Parse unsubscribe link.
     *
     * @param string $content
     * @param string $recipient
     *
     * @return string|string[]
     */
    public function parse_unsubscribe_link( $content, $recipient, $type ) {
        if ( empty( $this->subscribe2_options['s2_unsub_page'] ) ) {
            return str_replace('{UNSUBLINK}', '', $content );
        }

        $page_url  = get_page_link( $this->subscribe2_options['s2_unsub_page'] );
        $query     = parse_url( $page_url, PHP_URL_QUERY );
        $page_url .= ( ( $query ? '&' : '?' ) . 's2_unsub=' . base64_encode( $recipient ) );

        if ($type == 'html') {
            $page_url = '<a href="' . $page_url . '">' . __( 'Unsubscribe', 'subscribe2' ) . '</a>';
        }

        return str_replace('{UNSUBLINK}', $page_url, $content );
    }

    /**
     * Function to set HTML Email in wp_mail().
     *
     * @return string
     */
    public function html_email() {
        return 'text/html';
    }

    /**
     * Function to set plain text Email in wp_mail().
     *
     * @return string
     */
    public function plain_email() {
        return 'text/plain';
    }

    /**
     * Function to add UTM tracking details to links.
     *
     * @param $link
     *
     * @return mixed|string|void
     */
    public function get_tracking_link( $link ) {
        if ( empty( $link ) ) {
            return;
        }

        $delimiter = '';
        if ( ! empty( $this->subscribe2_options['tracking'] ) ) {
            $delimiter .= ( strpos( $link, '?' ) > 0 ) ? '&' : '?';
            $tracking   = $this->subscribe2_options['tracking'];
            if ( strpos( $tracking, '{ID}' ) ) {
                $id       = url_to_postid( $link );
                $tracking = str_replace( '{ID}', $id, $tracking );
            }

            if ( strpos( $tracking, '{TITLE}' ) ) {
                $id       = url_to_postid( $link );
                $title    = rawurlencode( htmlentities( get_the_title( $id ), 1 ) );
                $tracking = str_replace( '{TITLE}', $title, $tracking );
            }

            return $link . $delimiter . $tracking;
        }

        return $link;
    }

    /**
     * Sends an email notification of a new post.
     *
     * @param object $post
     * @param string $preview
     *
     * @return mixed|void
     */
    public function publish( $post, $preview = '' ) {
        if ( ! $post ) {
            return $post;
        }

        if ( empty( $preview ) ) {
            // We aren't sending a Preview to the current user so carry out checks.
            $s2mail = get_post_meta( $post->ID, '_s2mail', true );
            if ( ( isset( $_POST['s2_meta_field'] ) && 'no' === sanitize_key( $_POST['s2_meta_field'] ) ) || 'no' === strtolower( trim( $s2mail ) ) ) {
                return $post;
            }

            // Is the current post of a type that should generate a notification email?
            // Uses s2_post_types filter to allow for custom post types in WP 3.0
            if ( 'yes' === $this->subscribe2_options['pages'] ) {
                $s2_post_types = array( 'page', 'post' );
            } else {
                $s2_post_types = array( 'post' );
            }

            $s2_post_types = apply_filters( 's2_post_types', $s2_post_types );
            if ( ! in_array( $post->post_type, $s2_post_types, true ) ) {
                return $post;
            }

            // Are we sending notifications for password protected posts?
            if ( 'no' === $this->subscribe2_options['password'] && '' !== $post->post_password ) {
                return $post;
            }

            // Is the post assigned to a format for which we should not be sending posts
            $post_format      = get_post_format( $post->ID );
            $excluded_formats = explode( ',', $this->subscribe2_options['exclude_formats'] );
            if ( false !== $post_format && in_array( $post_format, $excluded_formats, true ) ) {
                return $post;
            }

            $s2_taxonomies = apply_filters( 's2_taxonomies', array( 'category' ) );
            $post_cats     = wp_get_object_terms(
                $post->ID,
                $s2_taxonomies,
                array(
                    'fields' => 'ids',
                )
            );

            // Fail gracefully if we have a post but no category assigned or a taxonomy error.
            if ( is_wp_error( $post_cats ) || ( empty( $post_cats ) && 'post' === $post->post_type ) ) {
                return $post;
            }

            $check = false;
            // Is the current post assigned to any categories,
            // Which should not generate a notification email?
            foreach ( explode( ',', $this->subscribe2_options['exclude'] ) as $cat ) {
                if ( in_array( (int) $cat, $post_cats, true ) ) {
                    $check = true;
                }
            }

            if ( $check ) {
                // Hang on -- can registered users subscribe to excluded categories?
                if ( '0' === $this->subscribe2_options['reg_override'] ) {
                    // Nope? okay, let's leave.
                    return $post;
                }
            }

            // Are we sending notifications for Private posts?
            // Action is added if we are, but double check option and post status.
            if ( 'yes' === $this->subscribe2_options['private'] && 'private' === $post->post_status ) {
                // Don't send notification to public users.
                $check = true;
            }

            // Lets collect our subscribers.
            $public = array();
            if ( ! $check ) {
                // If this post is assigned to an excluded category, or is a private post then
                // don't send public subscribers a notification
                $public = $this->get_public();
            }

            if ( 'page' === $post->post_type ) {
                $post_cats_string = implode(
                    ',',
                    get_terms(
                        'category',
                        array(
                            'fields' => 'ids',
                            'get'    => 'all',
                        )
                    )
                );
            } else {
                $post_cats_string = implode( ',', $post_cats );
            }

            $registered = $this->get_registered( "cats=$post_cats_string" );
            // Do we have subscribers?
            if ( empty( $public ) && empty( $registered ) ) {
                // If not, no sense doing anything else.
                return $post;
            }
        } else {
            // Make sure we prime the taxonomy variable for preview posts.
            $s2_taxonomies = apply_filters( 's2_taxonomies', array( 'category' ) );
        }

        // get_the_time() uses the current locale of the admin user which may differ from the site locale.
        if ( function_exists( 'get_user_locale' ) && get_user_locale() !== get_locale() ) {
            switch_to_locale( get_locale() );
            $locale_switched = true;
        }

        // We set these class variables so that we can avoid,
        // passing them in function calls a little later.
        $this->post_title      = '<a href="' . $this->get_tracking_link( get_permalink( $post->ID ) ) . '">' . html_entity_decode( $post->post_title, ENT_QUOTES ) . '</a>';
        $this->post_title_text = html_entity_decode( $post->post_title, ENT_QUOTES );
        $this->permalink       = get_permalink( $post->ID );
        $this->post_date       = get_the_time( get_option( 'date_format' ), $post );
        $this->post_time       = get_the_time( '', $post );

        if ( isset( $locale_switched ) && true === $locale_switched ) {
            switch_to_locale( get_user_locale() );
        }

        $author           = get_userdata( $post->post_author );
        $this->authorname = html_entity_decode( apply_filters( 'the_author', $author->display_name ), ENT_QUOTES );

        // Do we send as admin or post author?
        if ( 'author' === $this->subscribe2_options['sender'] ) {
            // Get author details.
            $user          = &$author;
            $this->myemail = $user->user_email;
            $this->myname  = html_entity_decode( $user->display_name, ENT_QUOTES );
        } elseif ( 'blogname' === $this->subscribe2_options['sender'] ) {
            $this->myemail = get_option( 'admin_email' );
            $this->myname  = html_entity_decode( get_option( 'blogname' ), ENT_QUOTES );
        } else {
            // Get admin details.
            $user          = $this->get_userdata( $this->subscribe2_options['sender'] );
            $this->myemail = $user->user_email;
            $this->myname  = html_entity_decode( $user->display_name, ENT_QUOTES );
        }

        $this->post_cat_names = implode(
            ', ',
            wp_get_object_terms(
                $post->ID,
                $s2_taxonomies,
                array(
                    'fields' => 'names',
                )
            )
        );

        $this->post_tag_names = implode(
            ', ',
            wp_get_post_tags(
                $post->ID,
                array(
                    'fields' => 'names',
                )
            )
        );

        // Get email subject.
        $subject = html_entity_decode( stripslashes( wp_kses( $this->substitute( $this->subscribe2_options['notification_subject'] ), '' ) ) );

        // Get the message template.
        $mailtext = apply_filters( 's2_email_template', $this->subscribe2_options['mailtext'] );
        $mailtext = stripslashes( $this->substitute( $mailtext ) );

        $plaintext = $post->post_content;
        $plaintext = strip_shortcodes( $plaintext );

        $plaintext = preg_replace( '/<s[^>]*>(.*)<\/s>/Ui', '', $plaintext );
        $plaintext = preg_replace( '/<strike[^>]*>(.*)<\/strike>/Ui', '', $plaintext );
        $plaintext = preg_replace( '/<del[^>]*>(.*)<\/del>/Ui', '', $plaintext );

        // Fix for how the Block Editor stores lists.
        if ( true === $this->block_editor ) {
            $plaintext = str_replace( '</li><', "</li>\n<", $plaintext );
        }

        // Add filter here so $plaintext can be filtered to correct for layout needs.
        $plaintext   = apply_filters( 's2_plaintext', $plaintext );
        $excerpttext = $plaintext;

        if ( strstr( $mailtext, '{REFERENCELINKS}' ) ) {
            $mailtext        = str_replace( '{REFERENCELINKS}', '', $mailtext );
            $plaintext_links = '';
            $i               = 0;

            while ( preg_match( '/<a([^>]*)>(.*)<\/a>/Ui', $plaintext, $matches ) ) {
                if ( preg_match( '/href="([^"]*)"/', $matches[1], $link_matches ) ) {
                    $plaintext_links .= sprintf( "[%d] %s\r\n", ++$i, $link_matches[1] );
                    $link_replacement = sprintf( '%s [%d]', $matches[2], $i );
                } else {
                    $link_replacement = $matches[2];
                }

                $plaintext = preg_replace( '/<a[^>]*>(.*)<\/a>/Ui', $link_replacement, $plaintext, 1 );
            }
        }

        $plaintext = trim( wp_strip_all_tags( $plaintext ) );
        if ( isset( $plaintext_links ) && ! empty( $plaintext_links ) ) {
            $plaintext .= "\r\n\r\n" . trim( $plaintext_links );
        }

        $gallid  = '[gallery id="' . $post->ID . '"';
        $content = str_replace( '[gallery', $gallid, $post->post_content );

        // Remove the autoembed filter to remove iframes from notification emails.
        if ( get_option( 'embed_autourls' ) ) {
            global $wp_embed;

            $priority = has_filter( 'the_content', array( &$wp_embed, 'autoembed' ) );
            if ( false !== $priority ) {
                remove_filter( 'the_content', array( &$wp_embed, 'autoembed' ), $priority );
            }
        }

        $content = apply_filters( 'the_content', $content );
        $content = str_replace( ']]>', ']]&gt', $content );

        $excerpt = trim( $post->post_excerpt );
        if ( empty( $excerpt ) ) {
            // No excerpt, is there a <!--more--> ?
            if ( false !== strpos( $excerpttext, '<!--more-->' ) ) {
                list( $excerpt, $more ) = explode( '<!--more-->', $excerpttext, 2 );

                // Strip tags and trailing whitespace.
                $excerpt = trim( wp_strip_all_tags( $excerpt ) );
            } else {
                // no <!--more-->, so create excerpt.
                $excerpt = $this->create_excerpt( $excerpttext );
            }
        }

        $html_excerpt = trim( $post->post_excerpt );
        if ( '' === $html_excerpt ) {
            // No excerpt, is there a <!--more--> ?
            if ( false !== strpos( $content, '<!--more-->' ) ) {
                list( $html_excerpt, $more ) = explode( '<!--more-->', $content, 2 );

                // Balance HTML tags and then strip leading and trailing whitespace.
                $html_excerpt = trim( balanceTags( $html_excerpt, true ) );
            } else {
                // no <!--more-->, so create excerpt.
                $html_excerpt = $this->create_excerpt( $content, true );
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
            return '<img src="' . $base64 . '" alt="" data-rel="'.$path.'"/>';
        }, $html_excerpt);

        // Remove excess white space from with $excerpt and $plaintext.
        $excerpt   = preg_replace( '/[ ]+/', ' ', $excerpt );
        $plaintext = preg_replace( '/[ ]+/', ' ', $plaintext );

        // Prepare mail body texts.
        //$plain_excerpt_body = str_replace( '{POST}', $excerpt, $mailtext );
        //$plain_body         = str_replace( '{POST}', $plaintext, $mailtext );
        //$html_body          = str_replace( "\r\n", "<br>\r\n", $mailtext );
        //$html_body          = str_replace( '{POST}', $content, $html_body );
        $html_excerpt_body  = str_replace( "\r\n", "<br>\r\n", $mailtext );
        $html_excerpt_body  = str_replace( '{POST}', $html_excerpt, $html_excerpt_body );

        if ( ! empty( $preview ) ) {
            $this->preview_email = true;
            $this->myemail       = $preview;
            //$this->myname        = __( 'Plain Text Excerpt Preview', 'subscribe2' );

            //$this->mail( array( $preview ), $subject, $plain_excerpt_body );

            //$this->myname = __( 'Plain Text Full Preview', 'subscribe2' );
            //$this->mail( array( $preview ), $subject, $plain_body );

            $this->myname = __( 'HTML Excerpt Preview', 'subscribe2' );
            $this->mail( array( $preview ), $subject, $html_excerpt_body, 'html' );

            //$this->myname = __( 'HTML Full Preview', 'subscribe2' );
            //$this->mail( array( $preview ), $subject, $html_body, 'html' );
        } else {
            // Registered Subscribers first.
            // First we send plaintext summary emails.
            //$recipients = $this->get_registered( "cats=$post_cats_string&format=excerpt&author=$post->post_author" );
            //$recipients = apply_filters( 's2_send_plain_excerpt_subscribers', $recipients, $post->ID );
            //$this->mail( $recipients, $subject, $plain_excerpt_body );

            // Next we send plaintext full content emails.
            //$recipients = $this->get_registered( "cats=$post_cats_string&format=post&author=$post->post_author" );
            //$recipients = apply_filters( 's2_send_plain_fullcontent_subscribers', $recipients, $post->ID );
            //$this->mail( $recipients, $subject, $plain_body );

            // Next we send html excerpt content emails.
            $recipients = $this->get_registered( "cats=$post_cats_string&format=html_excerpt&author=$post->post_author" );
            $recipients = apply_filters( 's2_send_html_excerpt_subscribers', $recipients, $post->ID );
            $this->mail( $recipients, $subject, $html_excerpt_body, 'html' );

            // Next we send html full content emails.
            //$recipients = $this->get_registered( "cats=$post_cats_string&format=html&author=$post->post_author" );
            //$recipients = apply_filters( 's2_send_html_fullcontent_subscribers', $recipients, $post->ID );
            //$this->mail( $recipients, $subject, $html_body, 'html' );

            // And finally we send to Public Subscribers.
            //$recipients = apply_filters( 's2_send_public_subscribers', $public, $post->ID );
            //$this->mail( $recipients, $subject, $plain_excerpt_body, 'text' );

            $recipients = apply_filters( 's2_send_html_excerpt_public_subscribers', $public, $post->ID );
            $this->mail( $recipients, $subject, $html_excerpt_body, 'html' );
        }
    }

    /**
     * Function to create excerpts for emailing.
     *
     * @param string $text
     * @param bool   $html
     *
     * @return string
     */
    public function create_excerpt( $text, $html = false ) {
        $excerpt_on_words = apply_filters( 's2_excerpt_on_words', true );

        $excerpt = ( false === $html ) ? trim( wp_strip_all_tags( strip_shortcodes( $text ) ) ) : strip_shortcodes( $text );
        if ( true !== $excerpt_on_words ) {
            $words = preg_split( '//u', $excerpt, $this->excerpt_length + 1 );
        } else {
            $words = explode( ' ', $excerpt, $this->excerpt_length + 1 );
        }

        if ( count( $words ) > $this->excerpt_length ) {
            array_pop( $words );
            array_push( $words, '[...]' );
        }

        $excerpt = true !== $excerpt_on_words ? implode( '', $words ) : implode( ' ', $words );
        if ( true === $html ) {
            // Balance HTML tags and then strip leading and trailing whitespace.
            $excerpt = trim( balanceTags( $excerpt, true ) );
        }

        return $excerpt;
    }

    /**
     * Send confirmation email to a public subscriber.
     *
     * @param string $action
     * @param bool   $is_remind
     *
     * @return bool|mixed|void
     */
    public function send_confirm( $action = '', $is_remind = false ) {
        if ( 1 === $this->filtered ) {
            return true;
        }

        if ( ! $this->email || empty( $action ) ) {
            return false;
        }

        $id = $this->get_id( $this->email );
        if ( ! $id ) {
            return false;
        }

        // Generate the URL "?s2=ACTION+HASH+ID"
        // ACTION = 1 to subscribe, 0 to unsubscribe
        // HASH = wp_hash of email address
        // ID = user's ID in the subscribe2 table
        // use home instead of siteurl incase index.php is not in core WordPress directory.
        $link = apply_filters( 's2_confirm_link', get_option( 'home' ) ) . '/?s2=';

        if ( 'add' === $action ) {
            $link .= '1';
        } elseif ( 'del' === $action ) {
            $link .= '0';
        }

        $link .= wp_hash( $this->email );
        $link .= $id;

        // Sort the headers now so we have all substitute information.
        $mailheaders = $this->headers();

        if ( true === $is_remind ) {
            $body    = $this->substitute( stripslashes( $this->subscribe2_options['remind_email'] ) );
            $subject = $this->substitute( stripslashes( $this->subscribe2_options['remind_subject'] ) );
        } else {
            $body = apply_filters( 's2_confirm_email', stripslashes( $this->subscribe2_options['confirm_email'] ) );
            $body = $this->substitute( $body );
            if ( 'add' === $action ) {
                $body    = str_replace( '{ACTION}', $this->subscribe, $body );
                $subject = str_replace( '{ACTION}', $this->subscribe, $this->subscribe2_options['confirm_subject'] );
            } elseif ( 'del' === $action ) {
                $body    = str_replace( '{ACTION}', $this->unsubscribe, $body );
                $subject = str_replace( '{ACTION}', $this->unsubscribe, $this->subscribe2_options['confirm_subject'] );
            }

            $subject = html_entity_decode( $this->substitute( stripslashes( $subject ) ), ENT_QUOTES );
        }

        $body = str_replace( '{LINK}', $link, $body );
        if ( true === $is_remind && function_exists( 'wpmq_mail' ) ) {
            // Could be sending lots of reminders so queue them if wpmq is enabled.
            $status = wp_mail( $this->email, $subject, $body, $mailheaders, '', 0 );
        } else {
            return wp_mail( $this->email, $subject, $body, $mailheaders );
        }
    }

    /**
     * Return an array of all the public subscribers.
     *
     * @param int $confirmed
     *
     * @return array
     */
    public function get_public( $confirmed = 1 ) {
        global $wpdb;

        if ( 1 === $confirmed ) {
            return $wpdb->get_col( "SELECT email FROM $wpdb->subscribe2 WHERE active='1'" ) ?? [];
        }

        return $wpdb->get_col( "SELECT email FROM $wpdb->subscribe2 WHERE active='0'" )  ?? [];
    }

    /**
     * Given a public subscriber ID, returns the email address.
     *
     * @param int $id
     *
     * @return false|string|null
     */
    public function get_email( $id = 0 ) {
        global $wpdb;

        if ( ! $id ) {
            return false;
        }

        return $wpdb->get_var( $wpdb->prepare( "SELECT email FROM $wpdb->subscribe2 WHERE id=%d", $id ) );
    }

    /**
     * Given a public subscriber email, returns the subscriber ID.
     *
     * @param string $email
     *
     * @return false|string|null
     */
    public function get_id( $email = '' ) {
        global $wpdb;

        if ( ! $email ) {
            return false;
        }

        return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->subscribe2 WHERE email=%s", $email ) );
    }

    /**
     * Add a public subscriber to the subscriber table.
     * If added by admin it is immediately confirmed, otherwise as unconfirmed.
     *
     * @param string $email
     * @param bool   $confirm
     *
     * @return false|void
     */
    public function add( $email = '', $confirm = false ) {
        if ( 1 === $this->filtered ) {
            return;
        }

        global $wpdb;

        if ( false === $this->validate_email( $email ) ) {
            return false;
        }

        if ( false !== $this->is_public( $email ) ) {
            // Is this an email for a registered user.
            $check = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE user_email=%s", $this->email ) );
            if ( $check ) {
                return;
            }

            if ( $confirm ) {
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->subscribe2 SET active='1', ip=%s WHERE CAST(email as binary)=%s", $this->ip, $email ) );
            } else {
                $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->subscribe2 SET date=CURDATE(), time=CURTIME() WHERE CAST(email as binary)=%s", $email ) );
            }
        } else {
            if ( $confirm ) {
                global $current_user;
                $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->subscribe2 (email, active, date, time, ip) VALUES (%s, %d, CURDATE(), CURTIME(), %s)",
                    sanitize_email( $email ), 1, sanitize_textarea_field( $current_user->user_login ) )
                );
            } else {
                $wpdb->query( $wpdb->prepare( "INSERT INTO $wpdb->subscribe2 (email, active, date, time, ip) VALUES (%s, %d, CURDATE(), CURTIME(), %s)", sanitize_email( $email ), 0, sanitize_text_field( $this->ip ) ) );
            }
        }
    }

    /**
     * Remove a public subscriber user from the subscription table.
     *
     * @param string $email
     *
     * @return false|void
     */
    public function delete( $email = '' ) {
        global $wpdb;

        if ( false === $this->validate_email( $email ) ) {
            return false;
        }

        $wpdb->query( $wpdb->prepare( "DELETE FROM $wpdb->subscribe2 WHERE CAST(email as binary)=%s LIMIT 1", $email ) );
    }

    /**
     * Toggle a public subscriber's status.
     *
     * @param string $email
     *
     * @return false|void
     */
    public function toggle( $email = '' ) {
        global $wpdb;

        if ( empty( $email ) || false === $this->validate_email( $email ) ) {
            return false;
        }

        // Let's see if this is a public user.
        $status = $this->is_public( $email );
        if ( false === $status ) {
            return false;
        }

        if ( '0' === $status ) {
            $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->subscribe2 SET active='1', conf_date=CURDATE(), conf_time=CURTIME(), conf_ip=%s WHERE CAST(email as binary)=%s LIMIT 1", $this->ip, $email ) );
        } else {
            $wpdb->query( $wpdb->prepare( "UPDATE $wpdb->subscribe2 SET active='0', conf_date=CURDATE(), conf_time=CURTIME(), conf_ip=%s WHERE CAST(email as binary)=%s LIMIT 1", $this->ip, $email ) );
        }
    }

    /**
     * Send reminder email to unconfirmed public subscribers.
     *
     * @param string $emails
     *
     * @return false|void
     */
    public function remind( $emails = '' ) {
        if ( empty( $emails ) ) {
            return false;
        }

        $recipients = explode( ',', $emails );
        if ( ! is_array( $recipients ) ) {
            $recipients = (array) $recipients;
        }

        foreach ( $recipients as $recipient ) {
            $this->email = $recipient;
            $this->send_confirm( 'add', true );
        }
    } // End remind().

    /**
     * Is the supplied email address a public subscriber?
     *
     * @param string $email
     */
    public function is_public( $email = '' ) {
        global $wpdb;

        if ( empty( $email ) ) {
            return false;
        }

        // Run the query and force case sensitivity.
        $check = $wpdb->get_var( $wpdb->prepare( "SELECT active FROM $wpdb->subscribe2 WHERE CAST(email as binary)=%s", $email ) );
        if ( '0' === $check || '1' === $check ) {
            return $check;
        } else {
            return false;
        }
    }

    /**
     * Is the supplied email address a registered user of the blog?
     *
     * @param string $email
     *
     * @return bool
     */
    public function is_registered( $email = '' ) {
        global $wpdb;

        if ( empty( $email ) ) {
            return false;
        }

        $check = $wpdb->get_var( $wpdb->prepare( "SELECT user_email FROM $wpdb->users WHERE user_email=%s", $email ) );

        if ( $check ) {
            return true;
        }

        return false;
    }

    /**
     * Return Registered User ID from email.
     *
     * @param string $email
     *
     * @return false|string|null
     */
    public function get_user_id( $email = '' ) {
        global $wpdb;

        if ( empty( $email ) ) {
            return false;
        }

        $id = $wpdb->get_var(
            $wpdb->prepare( "SELECT id FROM $wpdb->users WHERE user_email=%s", $email )
        );

        return $id;
    }

    /**
     * Return an array of all subscribers emails or IDs.
     *
     * @param string $return
     *
     * @return array|object|stdClass[]|string|null
     */
    public function get_all_registered( $return = 'email' ) {
        global $wpdb;

        static $all_registered_id       = '';
        static $all_registered_email    = '';
        static $all_registered_email_id = '';

        if ( 'ID' === $return ) {
            if ( '' === $all_registered_id ) {
                $all_registered_id = $wpdb->get_col( "SELECT ID FROM $wpdb->users" );
            }
            return $all_registered_id;
        } elseif ( 'emailid' === $return ) {
            if ( '' === $all_registered_email_id ) {
                $all_registered_email_id = $wpdb->get_results( "SELECT user_email, ID FROM $wpdb->users", ARRAY_A );
            }
            return $all_registered_email_id;
        } else {
            if ( '' === $all_registered_email ) {
                $all_registered_email = $wpdb->get_col( "SELECT user_email FROM $wpdb->users" );
            }
            return $all_registered_email;
        }
    }

    /**
     * Return an array of registered subscribers.
     * Collect all the registered users of the blog who are subscribed to the specified categories.
     *
     * @param string $args
     *
     * @return array|mixed|null
     */
    public function get_registered( $args = '' ) {
        global $wpdb;

        parse_str( $args, $r );
        if ( ! isset( $r['format'] ) ) {
            $r['format'] = 'all';
        }

        if ( ! isset( $r['cats'] ) ) {
            $r['cats'] = '';
        }

        if ( ! isset( $r['author'] ) ) {
            $r['author'] = '';
        }

        if ( ! isset( $r['return'] ) ) {
            $r['return'] = 'email';
        }

        // Collect all subscribers for compulsory categories.
        $compulsory = explode( ',', $this->subscribe2_options['compulsory'] );
        foreach ( explode( ',', $r['cats'] ) as $cat ) {
            if ( in_array( $cat, $compulsory, true ) ) {
                $r['cats'] = '';
            }
        }

        $join = $and = '';

        // Text or HTML subscribers.
        if ( 'all' !== $r['format'] ) {
            $join .= "INNER JOIN $wpdb->usermeta AS b ON a.user_id = b.user_id ";
            $and  .= $wpdb->prepare( ' AND b.meta_key=%s AND b.meta_value=%s', $this->get_usermeta_keyname( 's2_format' ), $r['format'] );
        }

        // Specific category subscribers.
        if ( ! empty( $r['cats'] ) ) {
            $join    .= "INNER JOIN $wpdb->usermeta AS c ON a.user_id = c.user_id ";
            $cats_and = '';
            foreach ( explode( ',', $r['cats'] ) as $cat ) {
                $cats_and .= empty( $cats_and ) ?
                    $wpdb->prepare( 'c.meta_key=%s', $this->get_usermeta_keyname( 's2_cat' ) . $cat ) :
                    $wpdb->prepare( ' OR c.meta_key=%s', $this->get_usermeta_keyname( 's2_cat' ) . $cat );
            }

            $and .= " AND ($cats_and)";
        }

        // Specific authors.
        if ( ! empty( $r['author'] ) ) {
            $join .= "INNER JOIN $wpdb->usermeta AS d ON a.user_id = d.user_id ";
            $and  .= $wpdb->prepare( ' AND (d.meta_key=%s AND NOT FIND_IN_SET(%s, d.meta_value))', $this->get_usermeta_keyname( 's2_authors' ), $r['author'] );
        }

        $result = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT a.user_id FROM $wpdb->usermeta AS a " . $join . "WHERE a.meta_key=%s AND a.meta_value <> ''" . $and, // phpcs:ignore WordPress.DB.PreparedSQL, WordPress.DB.PreparedSQLPlaceholders
                $this->get_usermeta_keyname( 's2_subscribed' )
            )
        );

        if ( empty( $result ) ) {
            return array();
        }

        $ids = implode( ',', array_map( array( $this, 'prepare_in_data' ), $result ) );
        if ( 'emailid' === $r['return'] ) {
            $registered = $wpdb->get_results( "SELECT user_email, ID FROM $wpdb->users WHERE ID IN ($ids)", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL
        } else {
            $registered = $wpdb->get_col( "SELECT user_email FROM $wpdb->users WHERE ID IN ($ids)" ); // phpcs:ignore WordPress.DB.PreparedSQL
        }

        if ( empty( $registered ) ) {
            return array();
        }

        // Apply filter to registered users to add or remove additional addresses, pass args too for additional control.
        $registered = apply_filters( 's2_registered_subscribers', $registered, $args );
        return $registered;
    }

    /**
     * Check email is valid.
     *
     * @param string $email
     *
     * @return false|mixed|string
     */
    public function validate_email( $email ) {
        // Check the formatting is correct.
        if ( function_exists( 'filter_var' ) && false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
            return false;
        }

        if ( true === apply_filters( 's2_validate_email_with_dns', true ) ) {
            $domain = explode( '@', $email, 2 );
            if ( function_exists( 'idn_to_ascii' ) ) {
                $check_domain = idn_to_ascii( $domain[1], IDNA_NONTRANSITIONAL_TO_ASCII, INTL_IDNA_VARIANT_UTS46 );
            } else {
                $check_domain = $domain[1];
            }

            if ( true === checkdnsrr( $check_domain, 'MX' ) ) {
                return $email;
            }

            return false;
        }

        return is_email( $email );
    }

    /**
     * Create the appropriate usermeta values when a user registers.
     * If the registering user had previously subscribed to notifications, this function will delete them from the public subscriber list first.
     *
     * @param int  $user_ID
     * @param bool $consent
     *
     * @return int|mixed
     */
    public function register( $user_ID = 0, $consent = false ) {
        global $wpdb;

        if ( 0 === $user_ID ) {
            return $user_ID;
        }


        return $user_ID;
    }

    /**
     * Get admin data from record 1 or first user with admin rights.
     *
     * @param int $admin_id
     *
     * @return mixed|WP_User
     */
    public function get_userdata( $admin_id ) {
        global $wpdb, $userdata;

        if ( is_numeric( $admin_id ) ) {
            $admin = get_userdata( $admin_id );
        } elseif ( 'admin' === $admin_id ) {
            // Ensure compatibility with < 4.16
            $admin = get_userdata( '1' );
        } else {
            $admin = &$userdata;
        }

        if ( empty( $admin ) || 0 === $admin->ID ) {
            $role = array(
                'role' => 'administrator',
            );

            $wp_user_query = get_users( $role );
            $admin         = $wp_user_query[0];
        }

        return $admin;
    } // End get_userdata().

    /**
     * Subscribe/unsubscribe user from one-click submission.
     *
     * @param int    $user_ID
     * @param string $action
     *
     * @return void
     */
    public function one_click_handler( $user_ID, $action ) {
        if ( ! isset( $user_ID ) || ! isset( $action ) ) {
            return;
        }

        $all_cats = $this->all_cats( true );

        if ( 'subscribe' === $action ) {
            // Subscribe.
            $new_cats = array();
            foreach ( $all_cats as $cat ) {
                update_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_cat' ) . $cat->term_id, $cat->term_id );
                $new_cats[] = $cat->term_id;
            }

            update_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_subscribed' ), implode( ',', $new_cats ) );
            if ( 'yes' === $this->subscribe2_options['show_autosub'] && 'no' !== get_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_subscribed' ), true ) ) {
                update_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_autosub' ), 'yes' );
            }
        } elseif ( 'unsubscribe' === $action ) {
            // Unsubscribe.
            foreach ( $all_cats as $cat ) {
                delete_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_cat' ) . $cat->term_id );
            }

            delete_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_subscribed' ) );
            update_user_meta( $user_ID, $this->get_usermeta_keyname( 's2_autosub' ), 'no' );
        }
    } // End one_click_handler().

    /**
     * Get an object of all categories, include default and custom type.
     *
     * @param bool   $exclude
     * @param string $orderby
     *
     * @return array
     */
    public function all_cats( $exclude = false, $orderby = 'slug' ) {
        $all_cats      = array();
        $s2_taxonomies = apply_filters( 's2_taxonomies', array( 'category' ) );

        foreach ( $s2_taxonomies as $taxonomy ) {
            if ( taxonomy_exists( $taxonomy ) ) {
                $all_cats = array_merge(
                    $all_cats,
                    get_categories(
                        array(
                            'hide_empty' => false,
                            'orderby'    => $orderby,
                            'taxonomy'   => $taxonomy,
                        )
                    )
                );
            }
        }

        if ( true === $exclude ) {
            // Remove excluded categories from the returned object.
            $excluded = explode( ',', $this->subscribe2_options['exclude'] );

            // Need to use $id like this as this is a mixed array / object.
            $id = 0;
            foreach ( $all_cats as $cat ) {
                if ( in_array( (string) $cat->term_id, $excluded, true ) ) {
                    unset( $all_cats[ $id ] );
                }

                $id++;
            }
        }

        return $all_cats;
    }

    /**
     * Function to sanitise array of data for SQL.
     *
     * @param mixed $data
     *
     * @return string|null
     */
    public function prepare_in_data( $data ) {
        global $wpdb;
        return $wpdb->prepare( '%s', $data );
    }

    /**
     * Filter for usermeta table key names to adjust them if needed for WPMU blogs.
     *
     * @param string $metaname
     *
     * @return mixed|string
     */
    public function get_usermeta_keyname( $metaname ) {
        global $wpdb;
        // Not MU or not a prefixed option name.
        return $metaname;
    }


    /**
     * Register the form widget.
     *
     * @return void
     */
    public function subscribe2_widget() {
        require_once S2PATH . 'classes/class-s2-form-widget.php';
        register_widget( 'S2_Form_Widget' );
    }


    /**
     * Task to delete unconfirmed public subscribers after a defined interval.
     *
     * @return void
     */
    public function s2cleaner_task() {
        $unconfirmed = $this->get_public( 0 );
        if ( empty( $unconfirmed ) ) {
            return;
        }

        global $wpdb;

        $old_unconfirmed = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT email FROM $wpdb->subscribe2 WHERE active='0' AND date < DATE_SUB(CURDATE(), INTERVAL %d DAY) AND conf_date IS NULL",
                $this->clean_interval
            )
        );

        if ( empty( $old_unconfirmed ) ) {
            return;
        }

        foreach ( $old_unconfirmed as $email ) {
            $this->delete( $email );
        }
    }

     /**
     * Subscribe2 constructor.
     *
     * @return void
     */
    public function s2init() {
        global $wpdb, $wp_version, $wpmu_version;
        // Load the options.
        $this->subscribe2_options = get_option( 'subscribe2_options' );

        // Maybe use dev scripts.
        $this->script_debug = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

        $this->word_wrap = apply_filters( 's2_word_wrap', 78 );
        // RFC5322 states line length MUST be no more than 998 characters and SHOULD be no more than 78 characters.
        // Use 78 as default and cap user values above 998.
        if ( $this->word_wrap > 998 ) {
            $this->word_wrap = 998;
        }

        $this->excerpt_length = apply_filters( 's2_excerpt_length', 55 );
        $this->site_switching = apply_filters( 's2_allow_site_switching', false );
        $this->clean_interval = apply_filters( 's2_clean_interval', 28 );
        $this->lockout        = apply_filters( 's2_lockout', 0 );

        // Lockout is for a maximum of 24 hours so cap the value.
        if ( $this->lockout > 86399 ) {
            $this->lockout > 86399;
        }

        // Get the WordPress release number for in code version comparisons.
        $tmp              = explode( '-', $wp_version, 2 );
        $this->wp_release = $tmp[0];

        // Load our translations.
        add_action( 'init', array( $this, 'load_translations' ) );

        // Define and register table name.
        $s2_table = $wpdb->prefix . 'subscribe2';
        if ( ! isset( $wpdb->subscribe2 ) ) {
            $wpdb->subscribe2 = $s2_table;
            $wpdb->tables[]   = 'subscribe2';
        }

        // Add actions for processing posts based on per-post or cron email settings.
        $statuses = apply_filters( 's2_post_statuses', array( 'new', 'draft', 'auto-draft', 'pending' ) );
        if ( 'yes' === $this->subscribe2_options['private'] ) {
            foreach ( $statuses as $status ) {
                add_action( "{$status}_to_private", array( $this, 'publish' ) );
            }
        }

        array_push( $statuses, 'private', 'future' );
        foreach ( $statuses as $status ) {
            add_action( "{$status}_to_publish", array( $this, 'publish' ) );
        }

        // Add action to display widget if option is enabled.
        if ( '1' === $this->subscribe2_options['widget'] ) {
            add_action( 'widgets_init', array( $this, 'subscribe2_widget' ) );
        }

        // Add action to 'clean' unconfirmed Public Subscribers.
        if ( is_int( $this->clean_interval ) && $this->clean_interval > 0 ) {
            add_action( 'wp_scheduled_delete', array( $this, 's2cleaner_task' ) );
        }

        // Check if Block Editor is in use.
        if (
            function_exists( 'register_block_type' ) &&
            ! class_exists( 'Classic_Editor' ) &&
            false === has_filter( 'use_block_editor_for_post', '__return_false' )
        ) {
            $this->block_editor = true;
        }

        // Compatibility with Fusion Builder.
        if ( is_plugin_active( 'fusion-builder/fusion-builder.php' ) && ! isset( $_GET['gutenberg-editor'] ) ) {
            $this->block_editor = false;
        }

        if ( true === $this->block_editor ) {
            require_once S2PATH . 'classes/class-s2-block-editor.php';
            global $mysubscribe2_block_editor;

            $mysubscribe2_block_editor = new S2_Block_Editor();
        }

        // Add actions specific to admin or frontend.
        if ( is_admin() ) {
            // Add menu, authoring and category admin actions.
            add_action( 'admin_menu', array( $this, 'admin_menu' ) );
            add_action( 'add_meta_boxes', array( $this, 's2_meta_init' ), 10, 2 );
            add_action( 'save_post', array( $this, 's2_meta_handler' ) );
            add_action( 'save_post', array( $this, 's2_preview_handler' ) );
            add_action( 'save_post', array( $this, 's2_resend_handler' ) );

            // Add write button.
            if ( '1' === $this->subscribe2_options['show_button'] && false === $this->block_editor ) {
                add_action( 'admin_init', array( $this, 'button_init' ) );
            }

            // Add handler to dismiss sender error notice.
            add_action( 'wp_ajax_s2_dismiss_notice', array( $this, 's2_dismiss_notice_handler' ) );

            // Subscriber page options handler
            add_filter( 'set-screen-option', array( $this, 'subscribers_set_screen_option' ), 10, 3 );

            // Capture CSV export.
            if ( isset( $_POST['s2_admin'] ) && isset( $_POST['csv'] ) ) {
                $date = gmdate( 'Y-m-d' );
                header( 'Content-Description: File Transfer' );
                header( 'Content-type: application/octet-stream' );
                header( "Content-Disposition: attachment; filename=subscribe2_users_$date.csv" );
                header( 'Pragma: no-cache' );
                header( 'Expires: 0' );

                echo esc_html( $this->prepare_export( sanitize_text_field( $_POST['exportcsv'] ) ) );
                exit( 0 );
            }
        } else {
            // Load strings later on frontend for polylang plugin compatibility.
            add_action( 'wp', array( $this, 'load_strings' ) );

            if ( isset( $_GET['s2'] ) ) {
                // Someone is confirming a request.
                add_filter( 'request', array( $this, 'query_filter' ) );
                add_filter( 'the_title', array( $this, 'title_filter' ) );
                add_filter( 'the_content', array( $this, 'confirm' ) );
            }

            // Add the frontend filters.
            add_filter( 'the_content', array( $this, 'filter' ), 10 );

            // Add actions for other plugins.
            if ( '1' === $this->subscribe2_options['show_meta'] ) {
                add_action( 'wp_meta', array( $this, 'add_minimeta' ), 0 );
            }
        }

        add_shortcode( 'subscribe2', array( $this, 'widget_shortcode' ) );
    }
}
