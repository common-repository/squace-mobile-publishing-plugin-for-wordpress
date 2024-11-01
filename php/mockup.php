<?php

@ob_start();

ini_set("memory_limit","32M");

$current_dir = str_replace( "//", "/",  dirname( __FILE__ ) );
require_once( str_replace( "//", "/", $current_dir . '/config.php' ) );
require_once( str_replace( "//", "/", $current_dir . '/photo.php' ) );
require_once( str_replace( "//", "/", $current_dir . '/helpers.php' ) );

set_time_limit(5*60);

/**
 * Gets the program settings
 * @param string $filename
 * @param boolean $serialize
 * @param boolean $base64
 * @return array
 */
function settings_Read( $filename, $serialize = true, $base64 = true  ) {

    if( is_file($filename) ) {

        $file_data = file_get_contents( $filename );

        if( $serialize ) {

            if( $base64 ) {
                $return = base64_decode( $file_data );
            } else {
                $return = $file_data;
            }

            $return = unserialize( $return );

        } else {

            if( $base64 ) {
                $return = base64_decode( $file_data );
            } else {
                $return = $file_data;
            }

        }

    } else {

        return false;

    }

    return $return;

}

/**
 * Gets the filename extension
 * @param string $filename
 * @return string
 */
function get_extension( $filename ) {

    if( strlen($filename) > 0 ) {
        $array = explode( ".", $filename );
        return $array[ count($array) - 1 ];
    }

    return false;
    
}

/**
 * Create a resource from a filename
 * @param string $filename
 * @return resource
 */
function create_imagebuffer( $filename ) {

    switch ( strtolower( get_extension( $filename ) ) ) {
        case 'png':
            return imagecreatefrompng( $filename );
            break;
        case 'gif':
            return imagecreatefromgif( $filename );
            break;
        case 'jpeg':
        case 'jpg':
            return imagecreatefromjpeg( $filename );
            break;
        default:
            return false;
    }
    
}

$photo_utils = new SimpleImage();

$filename = str_replace( '\\', '/', $current_dir . '/../' . SQUACE_SETTINGS_FILE );
$settings = settings_Read( $filename );

$_background = str_replace( "//", "/",  $current_dir . '/../' . DEFAULT_MOCKUP_BACKGROUND );
$_background_grid = str_replace( "//", "/",  $current_dir . '/../' . DEFAULT_MOCKUP_GRID );
$_menu_up = str_replace( "//", "/",  $current_dir . '/../' . DEFAULT_MOCKUP_MENU );
$_menu_down = str_replace( "//", "/",  $current_dir . '/../' . DEFAULT_MOCKUP_BOTTOM );
$_squace_icon = str_replace( "//", "/",  $current_dir . '/../' . DEFAULT_MOCKUP_ICON );

if( !empty($_GET) ) {
    
    $_squace_rbackground_image = isset( $_GET['squace_rbackground_image'] ) && strlen( $_GET['squace_rbackground_image'] ) > 0 ? $_GET['squace_rbackground_image'] : $settings['squace_rbackground_image'];
    $_squace_ricon = $_GET['squace_ricon'];

    /** tabnames */

    /** default from settings tabnames */
    $_nt = array();
    foreach( $settings['fgct'] as $tmp ) { $_nt[] = array( 'name' => $tmp['tab_field_name'], 'tab_order' => $tmp['tab_order'] ); }
    //usort( $_nt, 'sortByOrderASC' );
    $_tabnames = array();
    foreach( $_nt as $t ) { $_tabnames[] = $t['name']; }
    $_tabnames = implode( " | ", $_tabnames );
    /** default from settings tabnames */
    
    $_tabnames = strlen( $_GET['tabnames'] ) > 0 ? $_GET['tabnames'] : $_tabnames;
    $_tabnames = explode( ";", $_tabnames );
    $_nt = array();
    foreach( $_tabnames as $_tab ) {
        $_tab = explode("__", $_tab);
        $_nt[] = array( 'name' => $_tab[0], 'tab_order' => $_tab[1] );
    }
//    usort( $_nt, 'sortByOrderASC' );
    $_tabnames = array();
    foreach( $_nt as $t ) { $_tabnames[] = $t['name']; }
    $_tabnames = implode( " | ", $_tabnames );
    /** tabnames */
    
    $_feedtitle = strlen( $_GET['feedtitle'] ) > 0 ? $_GET['feedtitle'] : 'Default';

} else {

    $_squace_rbackground_image = $settings['squace_rbackground_image'];
    $_squace_ricon = $settings['squace_ricon'];

    /** tabnames */
    $_nt = array();
    foreach( $settings['fgct'] as $tmp ) { $_nt[] = array( 'name' => $tmp['tab_field_name'], 'tab_order' => $tmp['tab_order'] ); }
//    usort( $_nt, 'sortByOrderASC' );
    $_tabnames = array();
    foreach( $_nt as $t ) { $_tabnames[] = $t['name']; }
    $_tabnames = implode( " | ", $_tabnames );
    /** tabnames */
    
    $_feedtitle = strlen( $settings['feedtitle'] ) > 0 ? $settings['feedtitle'] : "Default";

}

$max_size_width = 438;
$max_size_height = 436;
//start building mockup image
$_background_path = str_replace( "//", "/",  $current_dir . '/../' . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $_squace_rbackground_image );
if( strlen($_squace_rbackground_image) > 0 && file_exists( $_background_path ) ) {
    $_load_picture =  str_replace( "//", "/",  $current_dir . '/../' . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $_squace_rbackground_image );
} else {
    $_load_picture = $_background;
}

$photo_utils->load( $_load_picture );
$background = $photo_utils->auto($max_size_width, $max_size_height);

//$background_grid = crop_background_image( $_background_grid );
//$photo_utils->load( $_background_grid );
//$background_grid = $photo_utils->auto($max_size_width, $max_size_height);
$background_grid = create_imagebuffer( $_background_grid );

$menu_up = create_imagebuffer( $_menu_up );
$menu_down = create_imagebuffer( $_menu_down );

if( strlen($_squace_ricon) > 0 && file_exists( str_replace( "//", "/",  $current_dir . '/../' . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $_squace_ricon ) )) {
    $squace_icon = create_imagebuffer( str_replace( "//", "/",  $current_dir . '/../' . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $_squace_ricon ) );
} else {
    $squace_icon = create_imagebuffer( $_squace_icon );
}

$size['background'] = array( 'w' => imagesx( $background ), 'h' => imagesy( $background ) );
$size['background_grid'] = array( 'w' => imagesx( $background_grid ), 'h' => imagesy( $background_grid ), 'offset' => 128 );
$size['menu_up'] = array( 'w' => imagesx( $menu_up ), 'h' => imagesy( $menu_up ) );
$size['menu_down'] = array( 'w' => imagesx( $menu_down ), 'h' => imagesy( $menu_down ) );
$size['squace_icon'] = array( 'w' => imagesx( $squace_icon ), 'h' => imagesy( $squace_icon ) );
$size['blank'] = array(  'w' => $size['background']['w'] + $size['menu_up']['w'] + $size['menu_down']['w'],  'h' => $size['background']['h'] + $size['menu_up']['h'] + $size['menu_down']['h'] );

$string = $_feedtitle;
$px = 30 + strlen($string);
$text_color = imagecolorallocate( $menu_up, 255, 255, 255 );
imagestring( $menu_up, 7, $px, 17, $string, $text_color );

$string = $_tabnames;
$px = 12;
$text_color = imagecolorallocate( $menu_up, 255, 255, 255 );
imagestring( $menu_up, 7, $px, 60, $string, $text_color );

imagecopymerge( $menu_up, $squace_icon, 10, 16, 0, 0, 16, 16, 100 );

// make grid image transparent
imagecolortransparent( $background_grid, imagecolorat( $background_grid, 0, 0 ) );

// make the mockup picture
$blank = imagecreatetruecolor( (int) $size['blank']['w'], (int) $size['blank']['h'] );
imageantialias( $blank, true );

$alfa = 100;
imagecopymerge( $blank, $menu_up, 0, 0, 0, 0, $size['menu_up']['w'], $size['menu_up']['h'], $alfa );
imagecopymerge( $blank, $background, 0, $size['menu_up']['h'], 0, 0, $max_size_width, $max_size_height, $alfa);
imagecopymerge( $blank, $background_grid, 0, 0, 0, 0, $size['background_grid']['w'], $size['background_grid']['h'], 30 );
imagecopymerge( $blank, $background_grid, 0, $size['background_grid']['offset'], 0, 0, $size['background_grid']['w'], $size['background_grid']['h'], 30 );
imagecopymerge( $blank, $menu_down, 0, $max_size_height + $size['menu_up']['h'], 0, 0, $size['menu_down']['w'], $size['menu_down']['h'], $alfa );

$resized = imagecreatetruecolor( 260, 321 );
imagecopyresampled( $resized , $blank, 0, 0, 0, 0, 260, 321, 439, 580 );

@ob_clean();
header ("Content-type: image/jpeg");
imagejpeg( $resized );

imagedestroy( $menu_up );
imagedestroy( $background );
imagedestroy( $menu_down );
imagedestroy( $squace_icon );
imagedestroy( $blank );
imagedestroy( $resized );

?>