<?php defined('ABSPATH') or die('NO!'); ?>
<div class="wrap">
    <h2><?= esc_html(get_admin_page_title()) ?></h2>
    <form method="post">
    <?php
        $table = new SMini\SubscribersTable();
        $table->prepare_items();
        $table->search_box('search', 'search_id');
        $table->display();
    ?>
    </form>
</div>