<?php defined('ABSPATH') or die('NO!'); ?>
	<div class="wrap">
	    <h2><?=esc_html(get_admin_page_title())?></h2>
	    <form method="post">
	    <?php
	        // This prints out all hidden setting fields
	        settings_fields(SM_SETTINGS_GROUP);
	        do_settings_sections(static::OPTIONS_PAGE);
	        submit_button();
	    ?>
	    </form>
	</div>