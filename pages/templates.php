<?php defined('ABSPATH') or die('NO!'); ?>
<div class="wrap">
    <h2><?=esc_html(get_admin_page_title())?></h2>
    <table style="width: 100%; border-collapse: separate; border-spacing: 5px;" class="editform">
        <tr>
            <td style="vertical-align: top; height: 700px; min-height: 700px;">
                <form method="post" action="options.php">
                    <?php settings_fields(SM_SETTINGS_GROUP); ?>
                    <h3><?= esc_html__('Notification email (must not be empty)', 'subscribe-mini') ?></h3>
                    <?= esc_html__('Subject Line', 'subscribe-mini') ?>:
                    <input type="text" name="<?= SMOPTIONS . '[' . SM_SETTING_MAILHEADER . ']'?>" value="<?= esc_attr($this->options[SM_SETTING_MAILHEADER]) ?>" size="45" />
                    <br>
                    <textarea rows="9" cols="60" name="<?= SMOPTIONS . '[' . SM_SETTING_MAILTEXT . ']'?>" style="width:95%;"><?= esc_textarea(stripslashes($this->options[SM_SETTING_MAILTEXT])) ?></textarea>
                    <br>
                    <br>
                    <h3><?= esc_html__('Subscribe confirmation email', 'subscribe-mini') ?></h3>
                    <?= esc_html__('Subject Line', 'subscribe-mini') ?>:
                    <input type="text" name="<?= SMOPTIONS . '[' . SM_SETTING_CONFIRMHEADER . ']'?>" value="<?= esc_attr($this->options[SM_SETTING_CONFIRMHEADER]) ?>" size="45" /><br>
                    <textarea rows="9" cols="60" name="<?= SMOPTIONS . '[' . SM_SETTING_CONFIRMTEXT . ']'?>" style="width:95%;"><?= esc_textarea(stripslashes($this->options[SM_SETTING_CONFIRMTEXT])) ?></textarea>
                    <br>
                    <br>
                    <h3><?= esc_html__('Unsubscribe confirmation email', 'subscribe-mini') ?></h3>
                    <?= esc_html__('Subject Line', 'subscribe-mini') ?>:
                    <input type="text" name="<?= SMOPTIONS . '[' . SM_SETTING_UNCONFIRMHEADER . ']'?>" value="<?= esc_attr($this->options[SM_SETTING_UNCONFIRMHEADER]) ?>" size="45" /><br>
                    <textarea rows="9" cols="60" name="<?= SMOPTIONS . '[' . SM_SETTING_UNCONFIRMTEXT . ']'?>" style="width:95%;"><?= esc_textarea(stripslashes($this->options[SM_SETTING_UNCONFIRMTEXT])) ?></textarea>
                    <br>
                    <br>
                    <br>
                    <?= esc_html__('Mail Post Container', 'subscribe-mini') ?>:
                    <input type="text" name="<?= SMOPTIONS . '[' . SM_SETTING_MAIL_CONTAINER . ']'?>" value="<?= esc_attr($this->options[SM_SETTING_MAIL_CONTAINER]) ?>" size="45" /><br>
                    <br>
                    <br>
                    <?php submit_button(); ?>
                </form>
            </td>
            <td style="vertical-align: top;">
                <h3><?= esc_html__('Message substitutions', 'subscribe-mini') ?></h3>
                <dl>
                    <dt><b><em style="color: red"><?= esc_html__('IF THE FOLLOWING KEYWORDS ARE ALSO IN YOUR POST THEY WILL BE SUBSTITUTED', 'subscribe-mini') ?></em></b></dt>
                    <dd></dd>
                    <dt><b>{BLOGNAME}</b></dt>
                    <dd><?= esc_html(get_option('blogname')) ?></dd>
                    <dt><b>{BLOGLINK}</b></dt>
                    <dd><?= esc_html(get_option('home')) ?></dd>
                    <dt><b>{TITLE}</b></dt>
                    <dd><?= wp_kses_post(__("the post's title<br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{TITLETEXT}</b></dt>
                    <dd><?= wp_kses_post(__("the post's unformatted title <br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{POST}</b></dt>
                    <dd><?= wp_kses_post(__("the excerpt or the entire post<br>(<i>based on the subscriber's preferences</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{POSTTIME}</b></dt>
                    <dd><?= wp_kses_post(__('the excerpt of the post and the time it was posted<br>(<i>for digest emails only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{TABLE}</b></dt>
                    <dd><?= wp_kses_post(__('a list of post titles<br>(<i>for digest emails only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{TABLELINKS}</b></dt>
                    <dd><?= wp_kses_post(__('a list of post titles followed by links to the articles<br>(<i>for digest emails only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{REFERENCELINKS}</b></dt>
                    <dd><?= wp_kses_post(__('a reference style list of links at the end of the email with corresponding numbers in the content<br>(<i>for the full content plain text per-post email only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{PERMALINK}</b></dt>
                    <dd><?= wp_kses_post(__("the post's permalink<br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{PERMAURL}</b></dt>
                    <dd><?= wp_kses_post(__("the post's unformatted permalink<br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{IMPRINTLINK}</b></dt>
                    <dd><?= wp_kses_post(__("the imprint link<br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{IMPRINTURL}</b></dt>
                    <dd><?= wp_kses_post(__("the unformatted imprint link<br>(<i>for per-post emails only</i>)", 'subscribe-mini')) ?></dd>
                    <dt><b>{DATE}</b></dt>
                    <dd><?= wp_kses_post(__('the date the post was made<br>(<i>for per-post emails only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{TIME}</b></dt>
                    <dd><?= wp_kses_post(__('the time the post was made<br>(<i>for per-post emails only</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{MYNAME}</b></dt>
                    <dd><?= esc_html__("the admin or post author's name", 'subscribe-mini') ?></dd>
                    <dt><b>{EMAIL}</b></dt>
                    <dd><?= esc_html__("the admin or post author's email", 'subscribe-mini') ?></dd>
                    <dt><b>{AUTHORNAME}</b></dt>
                    <dd><?= esc_html__("the post author's name", 'subscribe-mini') ?></dd>
                    <dt><b>{LINK}</b></dt>
                    <dd><?= wp_kses_post(__('the generated link to confirm a request<br>(<i>only used in the confirmation email template</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{UNSUBLINK}</b></dt>
                    <dd><?= wp_kses_post(__('a generated unsubscribe link<br>(<i>only used in the email notification template</i>)', 'subscribe-mini')) ?></dd>
                    <dt><b>{ACTION}</b></dt>
                    <dd><?= wp_kses_post(__('Action performed by LINK in confirmation email<br>(<i>only used in the confirmation email template</i>)', 'subscribe-mini')) ?></dd>
                </dl>
            </td>
        </tr>
    </table>
</div>