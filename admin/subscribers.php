<?php defined('ABSPATH') or die('NO!');

global $subscribers, $what;

// Instantiate and prepare our table data - this also runs the bulk actions.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

if ( ! class_exists( 'Subscribe2_List_Table' ) ) {
    require_once S2PATH . 'classes/class-s2-list-table.php';
    $s2_list_table = new S2_List_Table();
}

// Get Public Subscribers.
$confirmed   = $this->get_public();
$unconfirmed = $this->get_public(0);

$what        = 'public';
$subscribers = array_merge($confirmed, $unconfirmed);

if ( isset( $_REQUEST['what'] ) ) {
    if ( 'confirmed' === $_REQUEST['what'] ) {
        $what        = 'confirmed';
        $subscribers = $confirmed;
    } elseif ( 'unconfirmed' === $_REQUEST['what'] ) {
        $what        = 'unconfirmed';
        $subscribers = $unconfirmed;
    }
}

$s2_list_table->prepare_items();

?>
<div class="wrap">
    <h1><?=esc_html__( 'Subscribers', 'subscribe2' )?></h1>
    <form method="post">
        <input type="hidden" name="s2_admin" />
        <input type="hidden" id="s2_location" name="s2_location" value="public" />
        <div class="s2_admin" id="s2_add_subscribers">
            <h2><?=esc_html__( 'Add/Remove Subscribers', 'subscribe2' )?></h2>
            <p>
                <?=esc_html__( 'Enter addresses, one per line or comma-separated', 'subscribe2' )?><br>
                <textarea rows="2" cols="80" name="addresses"></textarea>
            </p>
            <input type="hidden" name="s2_admin" />
            <p class="submit" style="border-top: none;">
                <input type="submit" class="button-primary" name="subscribe" value="<?=esc_attr( __( 'Subscribe', 'subscribe2' ) )?>" />
                <input type="submit" class="button-primary" name="unsubscribe" value="<?=esc_attr( __( 'Unsubscribe', 'subscribe2' ) )?>" />
            </p>
        </div>
        <div class="s2_admin" id="s2_current_subscribers">
            <h2><?=esc_html__( 'Current Subscribers', 'subscribe2' )?></h2>
            <br>
            <table style="width: 100%; border-collapse: separate; border-spacing: 0px; *border-collapse: expression("separate", cellSpacing = "0px");">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <?php $this->display_subscriber_dropdown( $what, __( 'Filter', 'subscribe2' ), ['all', 'all_users', 'registered'] )?>
                    </td>
                    <td style="width: 25%;"></td>
                    <?php if(!empty($subscribers)):?>
                    <td style="width: 25%; text-align: right;">
                        <input type="hidden" name="exportcsv" value="<?=esc_attr( implode( ",\r\n", $subscribers ) )?>" />
                        <input type="submit" class="button-secondary" name="csv" value="<?=esc_attr( __( 'Save Emails to CSV File', 'subscribe2' ) )?>" />
                    </td>
                    <?php else:?>
                    <td style="width: 25%;"></td>
                    <?php endif;?>
                </tr>
            </table>
            <?php $s2_list_table->search_box( __( 'Search', 'subscribe2' ), 'search_id' ); $s2_list_table->display(); ?>
        </div>
    </form>
</div>