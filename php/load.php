<?php
@ob_start();

/**
 * @todo Complete this for dinamicaly loading of pictures
 */

/** Read config file */
$config_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/config.php' );
require_once( $config_file );
/** End read config file */

/** variables and actions needed */
$id = isset($_GET['id']) ? $_GET['id'] : -1;
$action = isset($_GET['action']) ? strtolower(trim($_GET['action'])) : null;

switch ( $action ) {
    case 'images':
        $path = DEFAULT_IMAGES_PATH;
        break;
    case 'uploads':
        $path = DEFAULT_IMAGES_PATH_UPLOADS;
        break;
    default:
        break;
}
/** end variables and actions needed */

if( $id == -1 ) { return; }

$file_to_load = str_replace( "//", "/", dirname( __FILE ) . '/../' . $path . '/' ) . $id;

if( !file_exists( $file_to_load ) ) return;

ob_end_clean();

echo file_get_contents( $file_to_load );

?>