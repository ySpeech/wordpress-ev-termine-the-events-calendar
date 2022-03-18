<?php
/*
*
*	***** Evangelische Termine for The Events Calendar *****
*
*	This file initializes all YSPEECHETFTEC Core components
*	
*/

// If this file is called directly, abort. //
if (!defined('WPINC')) {
	die;
} // end if

// Define Our Constants
define('YSPEECHETFTEC_CORE_INC', dirname(__FILE__) . '/assets/inc/');
define('YSPEECHETFTEC_CORE_IMG', plugins_url('assets/img/', __FILE__));

/*
*
*  Includes
*
*/
// Load the Functions
if (file_exists(YSPEECHETFTEC_CORE_INC . 'yspeechetftec-core-functions.php')) {
	require_once YSPEECHETFTEC_CORE_INC . 'yspeechetftec-core-functions.php';
}
if (file_exists(YSPEECHETFTEC_CORE_INC . 'yspeechetftec-import-functions.php')) {
	require_once YSPEECHETFTEC_CORE_INC . 'yspeechetftec-import-functions.php';
}
