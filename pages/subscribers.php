<?php defined('ABSPATH') or die('NO!'); ?>
<div class="wrap">
    <h2><?= esc_html(get_admin_page_title()) ?></h2>
    <?php
        $table = new SMini\SubscribersTable();
        $table->prepare_items();
        $table->display();
    ?>
</div>