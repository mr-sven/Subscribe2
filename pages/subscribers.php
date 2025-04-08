<?php defined('ABSPATH') or die('NO!'); ?>
<div class="wrap">
    <h2><?= esc_html(get_admin_page_title()) ?></h2>
    <form method="post">
        <input type="hidden" name="action" value="add" />
        <div>
            <h2><?= esc_html__('Add Subscribers', SMLD) ?></h2>
            <p>
                <?= esc_html__('Enter addresses, one per line or comma-separated', SMLD) ?><br>
                <textarea rows="4" cols="80" name="addresses"></textarea>
            </p>
            <p class="submit" style="border-top: none;">
                <input type="submit" class="button-primary" name="subscribe" value="<?= esc_attr(__('Subscribe', SMLD)) ?>" />
            </p>
        </div>
        <div>
            <h2><?= esc_html__('Current Subscribers', SMLD) ?></h2>
            <?php $sminiTable->views(); ?>
            <?php
                $sminiTable->prepare_items();
                $sminiTable->search_box('search', 'search_id');
                $sminiTable->display();
            ?>
        </div>
    </form>
</div>