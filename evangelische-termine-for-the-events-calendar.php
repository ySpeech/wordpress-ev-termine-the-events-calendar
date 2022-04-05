<?php
/*
Plugin Name: Evangelische Termine for The Events Calendar
Description: Plugin zum import von Ev Termine. Das The Events Calendar Plugin sollte installiert und aktiviert sein um dieses Plugin zu verwenden.
Version: 0.0.4
Requires at least: 5.6
Requires PHP: 7.2
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html
Author: ySpeech
Author URI: https://yspeech.de
Text Domain: yspeechetftec
*/

// If this file is called directly, abort. //
if (!defined('WPINC')) {
    die;
} // end if

// Let's Initialize Everything
if (file_exists(plugin_dir_path(__FILE__) . 'core-init.php')) {
    require_once(plugin_dir_path(__FILE__) . 'core-init.php');
}
