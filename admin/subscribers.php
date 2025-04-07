<?php defined('ABSPATH') or die('NO!');

global $subscribers, $what;

// Instantiate and prepare our table data - this also runs the bulk actions.
if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if (!class_exists('Subscribe2_List_Table')) {
    require_once S2PATH . 'classes/class-s2-list-table.php';
    $s2_list_table = new S2_List_Table();
}

if (isset($_POST['s2_admin'])) {
    if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce(sanitize_key($_REQUEST['_wpnonce']), 'bulk-' . $s2_list_table->_args['plural'])) {
        die('<p>' . esc_html__('Security error! Your request cannot be completed.', 'subscribe2') . '</p>');
    }

    if (!empty($_POST['addresses'])) {
        $reg_sub_error = $pub_sub_error = $unsub_error = $email_error = $message = '';
        foreach (preg_split('/[\s,]+/', $_POST['addresses']) as $email) {
            $clean_email = sanitize_email($email);
            if (false === $this->validate_email($clean_email)) {
                $email_error .= empty($email_error) ? $email : ", $email";
                continue;
            } else {
                if (!empty($_POST['subscribe'])) {
                    if (false !== $this->is_public($clean_email)) {
                        $pub_sub_error .= empty($pub_sub_error) ? $clean_email : ", $clean_email";
                        continue;
                    }

                    if ($this->is_registered($clean_email)) {
                        $reg_sub_error .= empty($reg_sub_error) ? $clean_email : ", $clean_email";
                        continue;
                    }

                    $this->add($clean_email, true);
                    $message = __('Address(es) subscribed!', 'subscribe2');
                } elseif (isset($_POST['unsubscribe'])) {
                    if (false === $this->is_public($clean_email) || $this->is_registered($clean_email)) {
                        $unsub_error .= empty($unsub_error) ? $clean_email : ", $clean_email";
                        continue;
                    }

                    $this->delete($clean_email);
                    $message = __('Address(es) unsubscribed!', 'subscribe2');
                }
            }
        }

        if ($reg_sub_error) {
            echo '<div id="message" class="error"><p><strong>' . esc_html__('Some emails were not processed, the following are already Registered Subscribers', 'subscribe2') . ':<br>' . esc_html($reg_sub_error) . '</strong></p></div>';
        }

        if ($pub_sub_error) {
            echo '<div id="message" class="error"><p><strong>' . esc_html__('Some emails were not processed, the following are already Public Subscribers', 'subscribe2') . ':<br>' . esc_html($pub_sub_error) . '</strong></p></div>';
        }

        if ($unsub_error) {
            echo '<div id="message" class="error"><p><strong>' . esc_html__('Some emails were not processed, the following were not in the database', 'subscribe2') . ':<br> ' . esc_html($unsub_error) . '</strong></p></div>';
        }

        if ($email_error) {
            echo '<div id="message" class="error"><p><strong>' . esc_html__('Some emails were not processed, the following were invalid email addresses', 'subscribe2') . ':<br> ' . esc_html($email_error) . '</strong></p></div>';
        }

        if ('' !== $message) {
            echo '<div id="message" class="updated fade"><p><strong>' . esc_html($message) . '</strong></p></div>';
        }

        $_POST['what'] = 'confirmed';
    }
}

// Get Public Subscribers.
$confirmed   = $this->get_public();
$unconfirmed = $this->get_public(0);

$what        = 'public';
$subscribers = array_merge($confirmed, $unconfirmed);

if (isset($_REQUEST['what'])) {
    if ('confirmed' === $_REQUEST['what']) {
        $what        = 'confirmed';
        $subscribers = $confirmed;
    } elseif ('unconfirmed' === $_REQUEST['what']) {
        $what        = 'unconfirmed';
        $subscribers = $unconfirmed;
    }
}

$s2_list_table->prepare_items();

?>
<div class="wrap">
    <h1><?= esc_html__('Subscribers', 'subscribe2') ?></h1>
    <form method="post">
        <input type="hidden" name="s2_admin" />
        <input type="hidden" id="s2_location" name="s2_location" value="public" />
        <div class="s2_admin" id="s2_add_subscribers">
            <h2><?= esc_html__('Add/Remove Subscribers', 'subscribe2') ?></h2>
            <p>
                <?= esc_html__('Enter addresses, one per line or comma-separated', 'subscribe2') ?><br>
                <textarea rows="2" cols="80" name="addresses"></textarea>
            </p>
            <input type="hidden" name="s2_admin" />
            <p class="submit" style="border-top: none;">
                <input type="submit" class="button-primary" name="subscribe" value="<?= esc_attr(__('Subscribe', 'subscribe2')) ?>" />
                <input type="submit" class="button-primary" name="unsubscribe" value="<?= esc_attr(__('Unsubscribe', 'subscribe2')) ?>" />
            </p>
        </div>
        <div class="s2_admin" id="s2_current_subscribers">
            <h2><?= esc_html__('Current Subscribers', 'subscribe2') ?></h2>
            <br>
            <table style="width: 100%; border-collapse: separate; border-spacing: 0px;">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <?php $this->display_subscriber_dropdown($what, __('Filter', 'subscribe2'), ['all', 'all_users', 'registered']) ?>
                    </td>
                    <td style="width: 25%;"></td>
                    <?php if (!empty($subscribers)): ?>
                        <td style="width: 25%; text-align: right;">
                            <input type="hidden" name="exportcsv" value="<?= esc_attr(implode(",\r\n", $subscribers)) ?>" />
                            <input type="submit" class="button-secondary" name="csv" value="<?= esc_attr(__('Save Emails to CSV File', 'subscribe2')) ?>" />
                        </td>
                    <?php else: ?>
                        <td style="width: 25%;"></td>
                    <?php endif; ?>
                </tr>
            </table>
            <?php $s2_list_table->search_box(__('Search', 'subscribe2'), 'search_id');
            $s2_list_table->display(); ?>
        </div>
    </form>
</div>