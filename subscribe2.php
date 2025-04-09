<?php
/*
Plugin Name: Subscribe2
Plugin URI: https://getwemail.io
Description: Notifies an email list when new entries are posted.
Version: 10.44
Author: weMail
Author URI: https://getwemail.io
Licence: GPLv3
Text Domain: subscribe2
*/

/*
Copyright (C) 2020 weDevs (info@getwemail.io)
Based on the Original Subscribe2 plugin by
Copyright (C) 2005 Scott Merrill (skippy@skippy.net)

This file is part of Subscribe2.

Subscribe2 is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Subscribe2 is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with Subscribe2. If not, see <http://www.gnu.org/licenses/>.
*/


// Our version number. Don't touch this or any line below.
// Unless you know exactly what you are doing.
define( 'S2VERSION', '10.44' );
define( 'S2PLUGIN', __FILE__ );
define( 'S2PATH', trailingslashit( dirname( __FILE__ ) ) );
define( 'S2DIR', trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) );
define( 'S2URL', plugin_dir_url( dirname( __FILE__ ) ) . S2DIR );

// Set maximum execution time to 5 minutes.
if ( function_exists( 'set_time_limit' ) ) {
	set_time_limit( 300 );
}

// define language domain
define('SMLD', 'subscribe2');
define('SMOPTIONS', 'smini_options');

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

}


global $mysubscribe2;

require_once S2PATH . 'classes/class-s2-core.php';

if ( is_admin() ) {
	require_once S2PATH . 'classes/class-s2-admin.php';
	$mysubscribe2 = new S2_Admin();
} else {
	require_once S2PATH . 'classes/class-s2-frontend.php';
	$mysubscribe2 = new S2_Frontend();
}

add_action( 'plugins_loaded', array( $mysubscribe2, 's2init' ) );
