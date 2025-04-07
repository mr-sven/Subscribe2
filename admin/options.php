<?php defined('ABSPATH') or die('NO!');
?>
<div class="wrap">
    <h1><?= esc_html__('Options', 'subscribe2') ?></h1>
    <form method="post">
        <?php wp_nonce_field('subscribe2-options_subscribers' . S2VERSION); ?>
        <input type="hidden" name="s2_admin" />
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
        <br>
        <br>

        <?php submit_button(__('Submit', 'subscribe2'), 'primary', 'submit', true, 'style="display: block; margin: 0 auto;"'); ?>
    </form>
</div>