<?php
/*
Plugin Name: Subscribe Mini
Plugin URI: https://www.livediesel.de
Description: Notifies an email list when new entries are posted.
Version: 1.0
Author: Sven
Author URI: https://www.livediesel.de
Licence: GPLv3
Text Domain: subscribe-mini
*/

/*
Copyright (C) 2025 Sven Fabricius (sven.fabricius@livediesel.de)
Based on the modified Subscribe2 plugin by
Copyright (C) 2020 weDevs (info@getwemail.io)
Based on the Original Subscribe2 plugin by
Copyright (C) 2005 Scott Merrill (skippy@skippy.net)

This file is part of Subscribe Mini.

Subscribe Mini is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Subscribe Mini is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Subscribe Mini. If not, see <http://www.gnu.org/licenses/>.
*/


// Our version number. Don't touch this or any line below.
// Unless you know exactly what you are doing.

// Set maximum execution time to 5 minutes.
if ( function_exists( 'set_time_limit' ) ) {
	set_time_limit( 300 );
}

// define language domain
//define(''subscribe-mini'', 'subscribe-mini');
define('SMOPTIONS', 'smini_options');
define('SMPLUGINDIR', dirname(plugin_basename(__FILE__)));

require_once __DIR__ . "/src/setup.php";
require_once __DIR__ . "/src/core.php";

// is admin panel
if (is_admin())
{
	require_once __DIR__ . "/src/admin.php";

	// register activation and deactivation hooks
	register_activation_hook(__FILE__, [SMini\Setup::class, 'activate']);
	register_deactivation_hook(__FILE__, [SMini\Setup::class, 'deactivate']);

	new SMini\Admin();
}
else
{
	require_once __DIR__ . "/src/frontend.php";
	new SMini\Frontend();
}