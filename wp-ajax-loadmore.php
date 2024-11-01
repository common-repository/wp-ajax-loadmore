<?php 
/*
Plugin Name: WP Ajax Loadmore
Plugin URI: #
Description: Load Post using ajax.
Version: 1.0.1
Author: Libin Prasanth
Author URI: http://libinprasanth.com
License: GPLv2 or later
Text Domain: wp-ajax-loadmore
*/

// Plugin Constant
define( 'WPALM_VERSION', '1.0.1' );
define( 'WPALM_PLUGIN', __FILE__ );
define( 'WPALM_PLUGIN_BASENAME', plugin_basename( WPALM_PLUGIN ) );
define( 'WPALM_URL' , plugin_dir_url( __FILE__ ));

require_once "inc/loader.php";