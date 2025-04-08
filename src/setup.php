<?php
declare(strict_types=1);
defined('ABSPATH') or die('NO!');

namespace SMini;

class Setup
{
    private const TABLE_NAME = 'smini';
    /**
     * Prepares wpdb table names.
     *
     */
    public static function prepare()
    {
        // use Wordpress Database
        global $wpdb;

        $smini_table = $wpdb->prefix . static::TABLE_NAME;
        if ( ! isset( $wpdb->smini ) ) {
            $wpdb->smini = $smini_table;
            $wpdb->tables[] = static::TABLE_NAME;
        }
    }

    /**
     * Plugin activation function
     *
     * creates required sql tables
     */
    public static function activate()
    {
        // use Wordpress Database
        global $wpdb;

        static::prepare();

        $charset_collate = '';
		if ( ! empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
		}

		if ( ! empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE {$wpdb->collate}";
		}

		$sql = "CREATE TABLE $wpdb->smini (
			id int(11) NOT NULL auto_increment,
			email varchar(64) NOT NULL,
			active tinyint(1) default 0,
			ts DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL,
			active_ts DATETIME DEFAULT NULL,
			PRIMARY KEY (id) ) $charset_collate";

        // check if table exists
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->smini)) !== $wpdb->smini)
        {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }
    }

    /**
     * Plugin deactivation function
     *
     * removed required sql tables
     */
    public static function deactivate()
    {
        // use Wordpress Database
        global $wpdb;

        $sql = "DROP TABLE IF EXISTS `$wpdb->smini`;";

        $wpdb->query($sql);
    }
}