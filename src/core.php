<?php

declare(strict_types=1);

namespace SMini;

defined('ABSPATH') or die('NO!');

abstract class Core
{
    public function __construct()
    {
        Setup::prepare();
        add_action('plugins_loaded', [$this, 'loaded']);
    }

    public function loaded()
    {
        add_action('widgets_init', [$this, 'widget_init']);
    }

    public function widget_init()
    {

    }
}
