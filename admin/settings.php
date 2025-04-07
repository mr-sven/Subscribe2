<?php
if ( ! function_exists( 'add_action' ) ) {
	exit();
}

global $wpdb, $current_tab;

// was anything POSTed?
if ( isset( $_POST['s2_admin'] ) ) {
	if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_REQUEST['_wpnonce'] ), 'subscribe2-options_subscribers' . S2VERSION ) ) {
		die( '<p>' . esc_html__( 'Security error! Your request cannot be completed.', 'subscribe2' ) . '</p>' );
	}

	if ( isset( $_POST['reset'] ) ) {
		require_once S2PATH . 'classes/class-s2-upgrade.php';

		global $s2_upgrade;

		$s2_upgrade = new S2_Upgrade();
		$s2_upgrade->reset();

		echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Options reset!', 'subscribe2' ) . '</strong></p></div>';
	} elseif ( isset( $_POST['preview'] ) ) {
		global $user_email, $post;

		$this->preview_email = true;
		$preview_posts = get_posts( 'numberposts=1' );
		$preview_post  = $preview_posts[0];

		$this->publish( $preview_post, $user_email );

		echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Preview message(s) sent to logged in user', 'subscribe2' ) . '</strong></p></div>';
	} elseif ( isset( $_POST['resend'] ) ) {

	} elseif ( isset( $_POST['submit'] ) ) {
		foreach ( $_POST as $key => $value ) {
			if ( in_array( $key, array( 'bcclimit', 's2page', 's2_unsub_page' ), true ) ) {
				// Numerical inputs fixed for old option names.
				if ( is_numeric( $_POST[ $key ] ) && intval( $_POST[ $key ] ) >= 0 ) {
					$this->subscribe2_options[ $key ] = intval( $_POST[ $key ] );
				}
			} elseif ( in_array( $key, array( 'show_meta', 'show_button', 'ajax', 'widget', 's2meta_default', 'reg_override' ), true ) ) {
				// Check box entries.
				$this->subscribe2_options[ $key ] = ( isset( $_POST[ $key ] ) && '1' === sanitize_key( $_POST[ $key ] ) ) ? '1' : '0';
			} elseif ( 'appearance_tab' === $key ) {
				$options = array( 'show_meta', 'show_button', 'ajax', 'widget', 's2meta_default', 'js_ip_updater', 's2_unsub_page' );
				foreach ( $options as $option ) {
					if ( ! isset( $_POST[ $option ] ) ) {
						$this->subscribe2_options[ $option ] = '0';
					}
				}
			} elseif ( in_array( $key, array( 'compulsory', 'exclude', 'format' ), true ) ) {
				sort( $_POST[ $key ] );

				$newvalue = implode( ',', array_map( 'sanitize_text_field', $_POST[ $key ] ) );
				if ( 'format' === $key ) {
					$this->subscribe2_options['exclude_formats'] = $newvalue;
				} else {
					$this->subscribe2_options[ $key ] = $newvalue;
				}
			} elseif ( 'registered_users_tab' === $key ) {
				$options = array( 'compulsory', 'exclude', 'format', 'reg_override' );
				foreach ( $options as $option ) {
					if ( ! isset( $_POST[ $option ] ) ) {
						if ( 'format' === $option ) {
							$this->subscribe2_options['exclude_formats'] = '';
						} else {
							$this->subscribe2_options[ $option ] = '';
						}
					}
				}
			} else {
				if ( isset( $this->subscribe2_options[ $key ] ) ) {
					if ( 'sender' === $key && $this->subscribe2_options[ $key ] !== sanitize_text_field( $_POST[ $key ] ) ) {
						$this->subscribe2_options['dismiss_sender_warning'] = '0';
					}

					$this->subscribe2_options[ $key ] = sanitize_text_field( $_POST[ $key ] );
				}
			}
		}

		echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Options saved!', 'subscribe2' ) . '</strong></p></div>';
		update_option( 'subscribe2_options', $this->subscribe2_options );
	}
}

// Send error message if no WordPress page exists.
$page_id = $wpdb->get_var( "SELECT ID FROM `{$wpdb->prefix}posts` WHERE post_type='page' AND post_status='publish' LIMIT 1" );
if ( empty( $page_id ) ) {
	echo '<div id="page_message" class="error"><p class="s2_error"><strong>' . esc_html__( 'You must create a WordPress page for this plugin to work correctly.', 'subscribe2' ) . '</strong></p></div>';
}

// Display error message for GDPR.
if ( defined( 'S2GDPR' ) && true === S2GDPR ) {
	if (
		'yes' === $this->subscribe2_options['autosub'] ||
		'yes' === $this->subscribe2_options['wpregdef'] ||
		'yes' === $this->subscribe2_options['autosub_def'] ||
		'yes' === $this->subscribe2_options['comment_def']
	) {
		echo '<div id="gdpr_message" class="error"><p class="s2_error"><strong>' . esc_html__( 'Your Settings may breach GDPR', 'subscribe2' ) . '</strong></p></div>';
	}
}

$disallowed_keywords = array( '{POSTTIME}', '{TABLE}', '{TABLELINKS}', '{COUNT}', '{LINK}', '{ACTION}' );

$disallowed = false;
foreach ( $disallowed_keywords as $disallowed_keyword ) {
	if ( false !== strstr( $this->subscribe2_options['mailtext'], $disallowed_keyword ) ) {
		$disallowed[] = $disallowed_keyword;
	}
}

$template_link = '<a href="' . admin_url( 'admin.php?page=s2_settings&tab=templates' ) . '">' . __( 'Modify your template', 'subscribe2' ) . '</a>';
if ( $disallowed ) {
	$disallowed_keywords = __( 'Your chosen email type (per-post or digest) does not support the following keywords:', 'subscribe2' );
	echo '<div id="keyword_message" class="error"><p class="s2_error"><strong>' . esc_html( $disallowed_keywords ) . '</strong><br>' . esc_html( implode( ', ', $disallowed ) ) . '<br>' . wp_kses_post( $template_link ) . '</p></div>';
}

// Send error message if sender email address is off-domain.
if ( 'blogname' === $this->subscribe2_options['sender'] ) {
	$sender = get_bloginfo( 'admin_email' );
} else {
	$user   = $this->get_userdata( $this->subscribe2_options['sender'] );
	$sender = $user->user_email;
}

list( $user, $sender_domain ) = explode( '@', $sender, 2 );

$dismiss_sender_warning = '';
if( isset( $this->subscribe2_options['dismiss_sender_warning'] ) ) {
	$dismiss_sender_warning = $this->subscribe2_options['dismiss_sender_warning'];
}

if (
	! stristr( esc_html( $_SERVER['SERVER_NAME'] ), $sender_domain ) &&
	'author' !== $this->subscribe2_options['sender'] &&
	'0' === ( isset( $this->subscribe2_options['dismiss_sender_warning'] ) ? $this->subscribe2_options['dismiss_sender_warning'] : '0' )
) {
	/* translators: Placeholders: %s - warning message */
	echo wp_kses_post( '<div id="sender_message" class="error notice is-dismissible"><p class="s2_error"><strong>' . sprintf( __( 'You appear to be sending notifications from %1$s, which has a different domain name than your blog server %2$s. This may result in failed emails.', 'subscribe2' ), $sender, $_SERVER['SERVER_NAME'] ) . '</strong></p></div>' );
}

// Detect or define which tab we are in.
$current_tab = ! empty( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'email';

// Show our form.
echo '<div class="wrap">';
echo '<h1>' . esc_html__( 'Settings', 'subscribe2' ) . '</h1>' . "\r\n";

$s2tabs = array(
	'email'      => __( 'Email Settings', 'subscribe2' ),
	'registered' => __( 'Registered Users', 'subscribe2' ),
	'appearance' => __( 'Appearance', 'subscribe2' ),
	'misc'       => __( 'Miscellaneous', 'subscribe2' ),
);

echo '<h2 class="nav-tab-wrapper">';

foreach ( $s2tabs as $tab_key => $tab_caption ) {
	$active = ( $current_tab === $tab_key ) ? 'nav-tab-active' : '';
	echo '<a class="nav-tab ' . esc_attr( $active ) . '" href="?page=s2_settings&amp;tab=' . esc_attr( $tab_key ) . '">' . esc_html( $tab_caption ) . '</a>';
}

echo '</h2>';
echo '<form method="post">' . "\r\n";

wp_nonce_field( 'subscribe2-options_subscribers' . S2VERSION );

echo '<input type="hidden" name="s2_admin" value="options" />' . "\r\n";
echo '<input type="hidden" id="jsbcclimit" value="' . esc_attr( $this->subscribe2_options['bcclimit'] ) . '" />';

$default_text = sprintf(
	/* translators: Placeholders: %1$s - opening strong tag, %2$s closing strong tag */
	__( 'Restrict the number of %1$srecipients per email%2$s to (0 for unlimited).', 'subscribe2' ),
	'<strong>',
	'</strong>'
);

$restriction_msg = sprintf(
	/* translators: Placeholders: %1$s - opening strong tag, %2$s closing strong tag */
	__( 'Restrict the number of %1$srecipients per email%2$s to (0 for unlimited).', 'subscribe2' ),
	'<strong>',
	'</strong>'
);

switch ( $current_tab ) {
	case 'email':
		// Settings for outgoing emails.
		echo '<div class="s2_admin" id="s2_notification_settings">' . "\r\n";
		echo '<p>' . "\r\n";
		echo wp_kses_post( $restriction_msg ) . ': ';
		echo '<span id="s2bcclimit_1"><span id="s2bcclimit" style="background-color: #FFFBCC">' . esc_html( $this->subscribe2_options['bcclimit'] ) . '</span> ';
		echo '<a href="#" onclick="s2Show(\'bcclimit\'); return false;">' . esc_html__( 'Edit', 'subscribe2' ) . '</a></span>' . "\r\n";
		echo '<span id="s2bcclimit_2">' . "\r\n";
		echo '<input type="text" name="bcclimit" value="' . esc_attr( $this->subscribe2_options['bcclimit'] ) . '" size="3" />' . "\r\n";
		echo '<a href="#" onclick="s2Update(\'bcclimit\'); return false;">' . esc_html__( 'Update', 'subscribe2' ) . '</a>' . "\r\n";
		echo '<a href="#" onclick="s2Revert(\'bcclimit\'); return false;">' . esc_html__( 'Revert', 'subscribe2' ) . '</a></span>' . "\n";

		echo '<br><br>' . esc_html__( 'Send Admins notifications for new', 'subscribe2' ) . ': ';
		echo '<label><input type="radio" name="admin_email" value="subs"' . checked( $this->subscribe2_options['admin_email'], 'subs', false ) . ' />' . "\r\n";
		echo esc_html__( 'Subscriptions', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="admin_email" value="unsubs"' . checked( $this->subscribe2_options['admin_email'], 'unsubs', false ) . ' />' . "\r\n";
		echo esc_html__( 'Unsubscriptions', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="admin_email" value="both"' . checked( $this->subscribe2_options['admin_email'], 'both', false ) . ' />' . "\r\n";
		echo esc_html__( 'Both', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="admin_email" value="none"' . checked( $this->subscribe2_options['admin_email'], 'none', false ) . ' />' . "\r\n";
		echo esc_html__( 'Neither', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		echo esc_html__( 'Include theme CSS stylesheet in HTML notifications', 'subscribe2' ) . ': ';
		echo '<label><input type="radio" name="stylesheet" value="yes"' . checked( $this->subscribe2_options['stylesheet'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="stylesheet" value="no"' . checked( $this->subscribe2_options['stylesheet'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		echo esc_html__( 'Send Emails for Pages', 'subscribe2' ) . ': ';
		echo '<label><input type="radio" name="pages" value="yes"' . checked( $this->subscribe2_options['pages'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="pages" value="no"' . checked( $this->subscribe2_options['pages'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		$s2_post_types = apply_filters( 's2_post_types', array() );
		if ( ! empty( $s2_post_types ) ) {
			echo esc_html__( 'Subscribe2 will send email notifications for the following custom post types', 'subscribe2' ) . ': ';
			echo '<strong>' . esc_html( implode( ', ', $s2_post_types ) ) . '</strong><br><br>' . "\r\n";
		}

		echo esc_html__( 'Send Emails for Password Protected Posts', 'subscribe2' ) . ': ';
		echo '<label><input type="radio" name="password" value="yes"' . checked( $this->subscribe2_options['password'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="password" value="no"' . checked( $this->subscribe2_options['password'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";
		echo esc_html__( 'Send Emails for Private Posts', 'subscribe2' ) . ': ';
		echo '<label><input type="radio" name="private" value="yes"' . checked( $this->subscribe2_options['private'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="private" value="no"' . checked( $this->subscribe2_options['private'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		echo esc_html__( 'Send Email From', 'subscribe2' ) . ': ';
		echo '<label>' . "\r\n";

		$this->admin_dropdown( true );

		echo '</label><br><br>' . "\r\n";

		echo esc_html__( 'Add Tracking Parameters to the Permalink', 'subscribe2' ) . ': ';
		echo '<input type="text" name="tracking" value="' . esc_attr( $this->subscribe2_options['tracking'] ) . '" size="50" /> ';
		echo '<br>' . esc_html__( 'eg. utm_source=subscribe2&amp;utm_medium=email&amp;utm_campaign=postnotify&amp;utm_id={ID}&amp;utm_title={TITLE}', 'subscribe2' ) . "\r\n";
		echo '</p>' . "\r\n";
		echo '</div>' . "\r\n";
		break;

	case 'registered':
		// Access function to allow display for form elements.
		require_once S2PATH . 'classes/class-s2-forms.php';

		$s2_forms = new s2_forms();

		// Excluded post formats.
		$formats = get_theme_support( 'post-formats' );
		if ( false !== $formats ) {
			// Excluded formats.
			echo '<div class="s2_admin" id="s2_excluded_formats">' . "\r\n";
			echo '<h3>' . esc_html__( 'Excluded Formats', 'subscribe2' ) . '</h3>' . "\r\n";
			echo '<p>';
			echo '<strong><em style="color: red">' . esc_html__( 'Posts assigned to any Excluded Format do not generate notifications and are not included in digest notifications', 'subscribe2' ) . '</em></strong><br>' . "\r\n";
			echo '</p>';

			$this->display_format_form( $formats, explode( ',', $this->subscribe2_options['exclude_formats'] ) );

			echo '</div>' . "\r\n";
		}

		// Auto Subscription for new registrations.
		echo '<div class="s2_admin" id="s2_autosubscribe_settings">' . "\r\n";
		echo '<h3>' . esc_html__( 'Auto-Subscribe', 'subscribe2' ) . '</h3>' . "\r\n";
		echo '<p>' . "\r\n";
		echo esc_html__( 'Subscribe new users registering with your blog', 'subscribe2' ) . ':<br>' . "\r\n";

		if (
			defined( 'S2GDPR' ) &&
			( ( true === S2GDPR && 'yes' === $this->subscribe2_options['autosub'] ) || ( false === S2GDPR ) )
		) {
			echo '<label><input type="radio" name="autosub" value="yes"' . checked( $this->subscribe2_options['autosub'], 'yes', false ) . ' /> ';
			echo esc_html__( 'Automatically', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		}

		echo '<label><input type="radio" name="autosub" value="wpreg"' . checked( $this->subscribe2_options['autosub'], 'wpreg', false ) . ' /> ';
		echo esc_html__( 'Display option on Registration Form', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="autosub" value="no"' . checked( $this->subscribe2_options['autosub'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";
		echo esc_html__( 'Auto-subscribe includes any excluded categories', 'subscribe2' ) . ':<br>' . "\r\n";
		echo '<label><input type="radio" name="newreg_override" value="yes"' . checked( $this->subscribe2_options['newreg_override'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="newreg_override" value="no"' . checked( $this->subscribe2_options['newreg_override'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		if (
			defined( 'S2GDPR' ) &&
			( ( true === S2GDPR && 'yes' === $this->subscribe2_options['wpregdef'] ) || ( false === S2GDPR ) )
		) {
			echo esc_html__( 'Registration Form option is checked by default', 'subscribe2' ) . ':<br>' . "\r\n";
			echo '<label><input type="radio" name="wpregdef" value="yes"' . checked( $this->subscribe2_options['wpregdef'], 'yes', false ) . ' /> ';
			echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
			echo '<label><input type="radio" name="wpregdef" value="no"' . checked( $this->subscribe2_options['wpregdef'], 'no', false ) . ' /> ';
			echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";
		}

		echo esc_html__( 'Auto-subscribe users to receive email as', 'subscribe2' ) . ': <br>' . "\r\n";
		echo '<label><input type="radio" name="autoformat" value="html"' . checked( $this->subscribe2_options['autoformat'], 'html', false ) . ' /> ';
		echo esc_html__( 'HTML - Full', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="autoformat" value="html_excerpt"' . checked( $this->subscribe2_options['autoformat'], 'html_excerpt', false ) . ' /> ';
		echo esc_html__( 'HTML - Excerpt', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="autoformat" value="post"' . checked( $this->subscribe2_options['autoformat'], 'post', false ) . ' /> ';
		echo esc_html__( 'Plain Text - Full', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="autoformat" value="excerpt"' . checked( $this->subscribe2_options['autoformat'], 'excerpt', false ) . ' /> ';
		echo esc_html__( 'Plain Text - Excerpt', 'subscribe2' ) . '</label><br><br>';
		echo esc_html__( 'Registered Users have the option to auto-subscribe to new categories', 'subscribe2' ) . ': <br>' . "\r\n";
		echo '<label><input type="radio" name="show_autosub" value="yes"' . checked( $this->subscribe2_options['show_autosub'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="show_autosub" value="no"' . checked( $this->subscribe2_options['show_autosub'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="show_autosub" value="exclude"' . checked( $this->subscribe2_options['show_autosub'], 'exclude', false ) . ' /> ';
		echo esc_html__( 'New categories are immediately excluded', 'subscribe2' ) . '</label><br><br>';

		if (
			defined( 'S2GDPR' ) &&
			( ( true === S2GDPR && 'yes' === $this->subscribe2_options['autosub_def'] ) || ( false === S2GDPR ) )
		) {
			echo esc_html__( 'Option for Registered Users to auto-subscribe to new categories is checked by default', 'subscribe2' ) . ': <br>' . "\r\n";
			echo '<label><input type="radio" name="autosub_def" value="yes"' . checked( $this->subscribe2_options['autosub_def'], 'yes', false ) . ' /> ';
			echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
			echo '<label><input type="radio" name="autosub_def" value="no"' . checked( $this->subscribe2_options['autosub_def'], 'no', false ) . ' /> ';
			echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>';
		}

		// Hide these options if using Jetpack comments.
		if ( ! class_exists( 'Jetpack_Comments' ) ) {
			echo esc_html__( 'Display checkbox to allow subscriptions from the comment form', 'subscribe2' ) . ': <br>' . "\r\n";
			echo '<label><input type="radio" name="comment_subs" value="before"' . checked( $this->subscribe2_options['comment_subs'], 'before', false ) . ' /> ';
			echo esc_html__( 'Before the Comment Submit button', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
			echo '<label><input type="radio" name="comment_subs" value="after"' . checked( $this->subscribe2_options['comment_subs'], 'after', false ) . ' /> ';
			echo esc_html__( 'After the Comment Submit button', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
			echo '<label><input type="radio" name="comment_subs" value="no"' . checked( $this->subscribe2_options['comment_subs'], 'no', false ) . ' /> ';
			echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>';

			if (
				defined( 'S2GDPR' ) &&
				( ( true === S2GDPR && 'yes' === $this->subscribe2_options['comment_def'] ) || ( false === S2GDPR ) )
			) {
				echo esc_html__( 'Comment form checkbox is checked by default', 'subscribe2' ) . ': <br>' . "\r\n";
				echo '<label><input type="radio" name="comment_def" value="yes"' . checked( $this->subscribe2_options['comment_def'], 'yes', false ) . ' /> ';
				echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
				echo '<label><input type="radio" name="comment_def" value="no"' . checked( $this->subscribe2_options['comment_def'], 'no', false ) . ' /> ';
				echo esc_html__( 'No', 'subscribe2' ) . '</label><br><br>' . "\r\n";
			}
		}

		echo esc_html__( 'Show one-click subscription on profile page', 'subscribe2' ) . ':<br>' . "\r\n";
		echo '<label><input type="radio" name="one_click_profile" value="yes"' . checked( $this->subscribe2_options['one_click_profile'], 'yes', false ) . ' /> ';
		echo esc_html__( 'Yes', 'subscribe2' ) . '</label>&nbsp;&nbsp;';
		echo '<label><input type="radio" name="one_click_profile" value="no"' . checked( $this->subscribe2_options['one_click_profile'], 'no', false ) . ' /> ';
		echo esc_html__( 'No', 'subscribe2' ) . '</label>' . "\r\n";
		echo '</p></div>' . "\r\n";
		break;

	case 'appearance':
		// Appearance options.
		echo '<div class="s2_admin" id="s2_appearance_settings">' . "\r\n";
		echo '<input type="hidden" name="appearance_tab" value="options" />' . "\r\n";
		echo '<p>' . "\r\n";

		// WordPress page ID where subscribe2 token is used.
		echo esc_html__( 'Set default Subscribe2 page as', 'subscribe2' ) . ': ';

		$this->pages_dropdown( $this->subscribe2_options['s2page'] );

        echo "</br></br>";
        echo esc_html__( 'Set Subscribe2 unsubscribe page', 'subscribe2' ) . ': ';

		$this->pages_dropdown( isset($this->subscribe2_options['s2_unsub_page']) ? $this->subscribe2_options['s2_unsub_page'] : 0 , 's2_unsub_page');

		// Show link to WordPress page in meta.
		echo '<br><br><label><input type="checkbox" name="show_meta" value="1"' . checked( $this->subscribe2_options['show_meta'], '1', false ) . ' /> ';
		echo esc_html__( 'Show a link to your subscription page in "meta"?', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		// Show QuickTag button.
		echo '<label><input type="checkbox" name="show_button" value="1"' . checked( $this->subscribe2_options['show_button'], '1', false ) . ' /> ';
		echo esc_html__( 'Show the Subscribe2 button on the Write toolbar?', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		// enable popup style form
		echo '<label><input type="checkbox" name="ajax" value="1"' . checked( $this->subscribe2_options['ajax'], '1', false ) . ' /> ';
		echo esc_html__( 'Enable popup style subscription form?', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		// Show widget.
		echo '<label><input type="checkbox" name="widget" value="1"' . checked( $this->subscribe2_options['widget'], '1', false ) . ' /> ';
		echo esc_html__( 'Enable Subscribe2 Widget?', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		// Checked s2_meta by default.
		echo '<label><input type="checkbox" name="s2meta_default" value="1"' . checked( $this->subscribe2_options['s2meta_default'], '1', false ) . ' /> ';
		echo esc_html__( 'Disable email notifications is checked by default on authoring pages?', 'subscribe2' ) . '</label><br><br>' . "\r\n";

		// Subscription form for registered users on rrontend.
		echo '<label><input type="checkbox" name="js_ip_updater" value="1"' . checked( $this->subscribe2_options['js_ip_updater'], '1', false ) . ' /> ';
		echo esc_html__( 'Use javascript to update IP address in Subscribe2 HTML form data? (useful if caching is enabled)', 'subscribe2' ) . '</label>' . "\r\n";
		echo '</p>';
		echo '</div>' . "\r\n";
		break;

	case 'misc':
		// Barred domains.
		echo '<div class="s2_admin" id="s2_barred_domains">' . "\r\n";
		echo '<h3>' . esc_html__( 'Barred Domains', 'subscribe2' ) . '</h3>' . "\r\n";
		echo '<p>' . "\r\n";
		echo esc_html__( 'Enter domains to bar for public subscriptions, wildcards (*) and exceptions (!) are allowed', 'subscribe2' ) . '<br>' . "\r\n";
		echo esc_html__( 'Use a new line for each entry and omit the "@" symbol, for example !email.com, hotmail.com, yahoo.*', 'subscribe2' );
		echo "\r\n" . '<br><textarea style="width: 98%;" rows="4" cols="60" name="barred">' . esc_textarea( $this->subscribe2_options['barred'] ) . '</textarea>';
		echo '</p>';
		echo '<h3>' . esc_html__( 'Links', 'subscribe2' ) . '</h3>' . "\r\n";
		echo '<a href="https://wordpress.org/plugins/subscribe2/">' . esc_html__( 'Plugin Site', 'subscribe2' ) . '</a><br>';
		echo '<a href="https://wordpress.org/support/plugin/subscribe2">' . esc_html__( 'Plugin Forum', 'subscribe2' ) . '</a><br>';
		echo '<a href="https://getwemail.io/subscribe2/">' . esc_html__( 'Plugin Blog', 'subscribe2' ) . '</a><br>';
		echo '</div>' . "\r\n";
		break;

}

// Submit settings.
submit_button( __( 'Submit', 'subscribe2' ), 'primary', 'submit' , true, 'style="display: block; margin: 0 auto;"' );

if ( 'misc' === $current_tab ) {
	// Reset.
	echo '<h3>' . esc_html__( 'Reset to Default Settings', 'subscribe2' ) . '</h3>' . "\r\n";
	echo '<p>' . esc_html__( 'Use this to reset all options to their defaults. This <strong><em>will not</em></strong> modify your list of subscribers.', 'subscribe2' ) . '</p>' . "\r\n";
	echo '<p class="submit" style="text-align: center">';
	echo '<input type="submit" class="button" id="deletepost" name="reset" value="' . esc_attr( __( 'RESET', 'subscribe2' ) ) . '" /></p>';
}

echo '</form></div>' . "\r\n";

require ABSPATH . 'wp-admin/admin-footer.php';
die; // Just to be sure.
