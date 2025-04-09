<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

class Frontend extends Core
{
    public function loaded()
    {
        parent::loaded();
        add_shortcode('subscribe-page', [$this, 'shortcode']);
    }

    public function shortcode($atts = [], $content = null, $shortcode_tag = '')
    {
        $options = get_option(SMOPTIONS);


        return "subscribe mini";
    }
}