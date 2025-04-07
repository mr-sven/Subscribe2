<?php defined('ABSPATH') or die('NO!');

// was anything POSTed?
if (isset($_POST['s2_admin'])) {
    if (! isset($_REQUEST['_wpnonce']) || ! wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'subscribe2-options_subscribers' . S2VERSION)) {
        die('<p>' . esc_html__('Security error! Your request cannot be completed.', 'subscribe2') . '</p>');
    }

    if (isset($_POST['submit'])) {
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['notification_subject', 'mailtext', 'confirm_subject', 'confirm_email'], true) && ! empty($_POST[$key])) {
                // Email subject and body templates.
                $this->subscribe2_options[$key] = in_array($key, ['notification_subject', 'confirm_subject']) ? sanitize_text_field(trim($_POST[$key])) : sanitize_textarea_field(trim($_POST[$key]));
            }
        }

		echo '<div id="message" class="updated fade"><p><strong>' . esc_html__( 'Options saved!', 'subscribe2' ) . '</strong></p></div>';
		update_option( 'subscribe2_options', $this->subscribe2_options );
    }
}
?>
<div class="wrap">
    <h1><?= esc_html__('Templates', 'subscribe2') ?></h1>
    <form method="post">
        <?php wp_nonce_field('subscribe2-options_subscribers' . S2VERSION); ?>
        <input type="hidden" name="s2_admin" />
        <div class="s2_admin" id="s2_templates">
            <table style="width: 100%; border-collapse: separate; border-spacing: 5px;" class="editform">
                <tr>
                    <td style="vertical-align: top; height: 700px; min-height: 700px;">
                        <h3><?= esc_html__('Notification email (must not be empty)', 'subscribe2') ?></h3>
                        <?= esc_html__('Subject Line', 'subscribe2') ?>:
                        <input type="text" name="notification_subject" value="<?= esc_attr($this->subscribe2_options['notification_subject']) ?>" size="45" />
                        <br>
                        <textarea rows="9" cols="60" name="mailtext" style="width:95%;"><?= esc_textarea(stripslashes($this->subscribe2_options['mailtext'])) ?></textarea>
                        <br>
                        <br>
                        <h3><?= esc_html__('Subscribe / Unsubscribe confirmation email', 'subscribe2') ?></h3>
                        <?= esc_html__('Subject Line', 'subscribe2') ?>:
                        <input type="text" name="confirm_subject" value="<?= esc_attr($this->subscribe2_options['confirm_subject']) ?>" size="45" /><br>
                        <textarea rows="9" cols="60" name="confirm_email" style="width:95%;"><?= esc_textarea(stripslashes($this->subscribe2_options['confirm_email'])) ?></textarea>
                        <br>
                        <br>
                        <?php submit_button(__('Submit', 'subscribe2'), 'primary', 'submit', true, 'style="display: block; margin: 0 auto;"'); ?>
                    </td>
                    <td style="vertical-align: top;">
                        <p class="submit">
                            <input type="submit" class="button-secondary" name="preview" value="<?= esc_html__('Send Email Preview', 'subscribe2') ?>" />
                        </p>
                        <h3><?= esc_html__('Message substitutions', 'subscribe2') ?></h3>
                        <dl>
                            <dt><b><em style="color: red"><?= esc_html__('IF THE FOLLOWING KEYWORDS ARE ALSO IN YOUR POST THEY WILL BE SUBSTITUTED', 'subscribe2') ?></em></b></dt>
                            <dd></dd>
                            <dt><b>{BLOGNAME}</b></dt>
                            <dd><?= esc_html(get_option('blogname')) ?></dd>
                            <dt><b>{BLOGLINK}</b></dt>
                            <dd><?= esc_html(get_option('home')) ?></dd>
                            <dt><b>{TITLE}</b></dt>
                            <dd><?= wp_kses_post(__("the post's title<br>(<i>for per-post emails only</i>)", 'subscribe2')) ?></dd>
                            <dt><b>{TITLETEXT}</b></dt>
                            <dd><?= wp_kses_post(__("the post's unformatted title <br>(<i>for per-post emails only</i>)", 'subscribe2')) ?></dd>
                            <dt><b>{POST}</b></dt>
                            <dd><?= wp_kses_post(__("the excerpt or the entire post<br>(<i>based on the subscriber's preferences</i>)", 'subscribe2')) ?></dd>
                            <dt><b>{POSTTIME}</b></dt>
                            <dd><?= wp_kses_post(__('the excerpt of the post and the time it was posted<br>(<i>for digest emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{TABLE}</b></dt>
                            <dd><?= wp_kses_post(__('a list of post titles<br>(<i>for digest emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{TABLELINKS}</b></dt>
                            <dd><?= wp_kses_post(__('a list of post titles followed by links to the articles<br>(<i>for digest emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{REFERENCELINKS}</b></dt>
                            <dd><?= wp_kses_post(__('a reference style list of links at the end of the email with corresponding numbers in the content<br>(<i>for the full content plain text per-post email only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{PERMALINK}</b></dt>
                            <dd><?= wp_kses_post(__("the post's permalink<br>(<i>for per-post emails only</i>)", 'subscribe2')) ?></dd>
                            <dt><b>{TINYLINK}</b></dt>
                            <dd><?= esc_html__("the post's permalink after conversion by TinyURL", 'subscribe2') ?></dd>
                            <dt><b>{PERMAURL}</b></dt>
                            <dd><?= wp_kses_post(__("the post's unformatted permalink<br>(<i>for per-post emails only</i>)", 'subscribe2')) ?></dd>
                            <dt><b>{DATE}</b></dt>
                            <dd><?= wp_kses_post(__('the date the post was made<br>(<i>for per-post emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{TIME}</b></dt>
                            <dd><?= wp_kses_post(__('the time the post was made<br>(<i>for per-post emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{MYNAME}</b></dt>
                            <dd><?= esc_html__("the admin or post author's name", 'subscribe2') ?></dd>
                            <dt><b>{EMAIL}</b></dt>
                            <dd><?= esc_html__("the admin or post author's email", 'subscribe2') ?></dd>
                            <dt><b>{AUTHORNAME}</b></dt>
                            <dd><?= esc_html__("the post author's name", 'subscribe2') ?></dd>
                            <dt><b>{LINK}</b></dt>
                            <dd><?= wp_kses_post(__('the generated link to confirm a request<br>(<i>only used in the confirmation email template</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{UNSUBLINK}</b></dt>
                            <dd><?= wp_kses_post(__('a generated unsubscribe link<br>(<i>only used in the email notification template</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{ACTION}</b></dt>
                            <dd><?= wp_kses_post(__('Action performed by LINK in confirmation email<br>(<i>only used in the confirmation email template</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{CATS}</b></dt>
                            <dd><?= esc_html__("the post's assigned categories", 'subscribe2') ?></dd>
                            <dt><b>{TAGS}</b></dt>
                            <dd><?= esc_html__("the post's assigned Tags", 'subscribe2') ?></dd>
                            <dt><b>{COUNT}</b></dt>
                            <dd><?= wp_kses_post(__('the number of posts included in the digest email<br>(<i>for digest emails only</i>)', 'subscribe2')) ?></dd>
                            <dt><b>{IMAGE}</b></dt>
                            <dd><?= esc_html__("the post's featured image", 'subscribe2') ?></dd>
                        </dl>
                    </td>
                </tr>
            </table>
        </div>
    </form>
</div>