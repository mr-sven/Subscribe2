<?php defined('ABSPATH') or die('NO!');

// was anything POSTed?
if (isset($_POST['s2_admin'])) {
    if (! isset($_REQUEST['_wpnonce']) || ! wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'subscribe2-options_subscribers' . S2VERSION)) {
        die('<p>' . esc_html__('Security error! Your request cannot be completed.', 'subscribe2') . '</p>');
    }

    if (isset($_POST['submit'])) {
        foreach ($_POST as $key => $value) {
            if (in_array($key, array('s2page', 's2_unsub_page'), true)) {
                // Numerical inputs fixed for old option names.
                if (is_numeric($_POST[$key]) && intval($_POST[$key]) >= 0) {
                    $this->subscribe2_options[$key] = intval($_POST[$key]);
                }
            } elseif ($key === 'barred') {
                if (isset($this->subscribe2_options[$key])) {
                    $this->subscribe2_options[$key] = sanitize_textarea_field($_POST[$key]);
                }
            }
        }
    }
}
?>
<div class="wrap">
    <h1><?= esc_html__('Options', 'subscribe2') ?></h1>
    <form method="post">
        <?php wp_nonce_field('subscribe2-options_subscribers' . S2VERSION); ?>
        <input type="hidden" name="s2_admin" />
        <p>
            <?= esc_html__('Send Admins notifications for new', 'subscribe2') ?>:
            <label>
                <input type="radio" name="admin_email" value="subs" <?= checked($this->subscribe2_options['admin_email'], 'subs', false) ?> />
                <?= esc_html__('Subscriptions', 'subscribe2') ?>
            </label>
            &nbsp;
            <label>
                <input type="radio" name="admin_email" value="unsubs" <?= checked($this->subscribe2_options['admin_email'], 'unsubs', false) ?> />
                <?= esc_html__('Unsubscriptions', 'subscribe2') ?>
            </label>
            &nbsp;
            <label>
                <input type="radio" name="admin_email" value="both" <?= checked($this->subscribe2_options['admin_email'], 'both', false) ?> />
                <?= esc_html__('Both', 'subscribe2') ?>
            </label>
            &nbsp;
            <label>
                <input type="radio" name="admin_email" value="none" <?= checked($this->subscribe2_options['admin_email'], 'none', false) ?> />
                <?= esc_html__('Neither', 'subscribe2') ?>
            </label>
        </p>
        <p><?= esc_html__('Set default Subscribe2 page as', 'subscribe2') ?>: <?php $this->pages_dropdown($this->subscribe2_options['s2page']); ?></p>
        <p><?= esc_html__('Set Subscribe2 unsubscribe page', 'subscribe2') ?>: <?php $this->pages_dropdown(isset($this->subscribe2_options['s2_unsub_page']) ? $this->subscribe2_options['s2_unsub_page'] : 0, 's2_unsub_page'); ?></p>
        <h3><?= esc_html__('Barred Domains', 'subscribe2') ?></h3>
        <p>
            <?= esc_html__('Enter domains to bar for public subscriptions, wildcards (*) and exceptions (!) are allowed', 'subscribe2') ?><br>
            <?= esc_html__('Use a new line for each entry and omit the "@" symbol, for example !email.com, hotmail.com, yahoo.*', 'subscribe2'); ?><br>
            <textarea style="width: 98%;" rows="4" cols="60" name="barred"><?= esc_textarea($this->subscribe2_options['barred']) ?></textarea>
        </p>
        <?php submit_button(__('Submit', 'subscribe2'), 'primary', 'submit', true, 'style="display: block; margin: 0 auto;"'); ?>
    </form>
</div>