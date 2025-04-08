<?php defined('ABSPATH') or die('NO!'); ?>
<?php $table = new SMini\SubscribersTable(); ?>
<div class="wrap">
    <h2><?= esc_html(get_admin_page_title()) ?></h2>
    <form method="post">
        <div>
            <h2><?= esc_html__('Add Subscribers', SMLD) ?></h2>
            <p>
                <?= esc_html__('Enter addresses, one per line or comma-separated', SMLD) ?><br>
                <textarea rows="2" cols="80" name="addresses"></textarea>
            </p>
            <p class="submit" style="border-top: none;">
                <input type="submit" class="button-primary" name="subscribe" value="<?= esc_attr(__('Subscribe', SMLD)) ?>" />
            </p>
        </div>
        <div>
            <h2><?= esc_html__('Current Subscribers', SMLD) ?></h2>
            <?php $table->views(); ?>
            <?php
                $table->prepare_items();
                $table->search_box('search', 'search_id');
                $table->display();
            ?>
        </div>
    </form>
</div>