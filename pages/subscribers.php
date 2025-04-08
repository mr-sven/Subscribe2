<?php defined('ABSPATH') or die('NO!'); ?>
<?php $table = new SMini\SubscribersTable(); ?>
<div class="wrap">
    <h2><?= esc_html(get_admin_page_title()) ?></h2>
    <?php $table->views(); ?>
    <form method="post">
    <?php
        $table->prepare_items();
        $table->search_box('search', 'search_id');
        $table->display();
    ?>
    </form>
</div>