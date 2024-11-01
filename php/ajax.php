<?php

/** Read config file */
$config_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/config.php' );
require_once( $config_file );
/** End read config file */

/** Read helper file */
$helpers_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/helpers.php' );
require_once( $helpers_file );
/** End read helper file */

/** read wp-load file */
$wpload_file = str_replace( "//", "/", dirname(__FILE__) . '/../../../../' . '/wp-load.php' );
require_once( $wpload_file );
/** End read wp-load file */

/** read wp-squace file */
$wpsquace_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/../' . '/wp-squace.php' );
require_once( $wpsquace_file );
$squace_ajax_load = true;
/** End read wp-squace file */

$psq = new plugin_Squace();

/** variables and actions needed */
$id = isset($_GET['id']) && is_numeric($_GET['id']) ? strtolower(trim($_GET['id'])) : 0;
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : null;

switch ( $action ) {
    case 'tab':
        echo _build_tab();
        break;
    case 'purgecache':
        echo _purge_cache();
        break;
    default:
        echo "Invalid action.";
        break;
}
/** end variables and actions needed */

/**
 * Builds a new empty tab
 * @global object $psq
 * @return string
 */
function _build_tab() {

    global $psq;

    $id = md5(uniqid(rand(), true));
    $name = $id;

    return $psq->print_custom_Tab($id, $name, array(), false, false);
    
}

function _purge_cache() {

    global $psq;

    if( cache_CleanPath( SQUACE_PLUGIN_BASE_PATH . DEFAULT_CACHE_PATH ) ) {
        return "Cache files cleaned successfuly.";
    } else {
        return "An error occured while trying to clear cache file.";
    }
    
}

?>