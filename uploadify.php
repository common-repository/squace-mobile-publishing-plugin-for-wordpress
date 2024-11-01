<?php

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

/** Read config file */
$config_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/php/config.php' );
require_once( $config_file );
/** End read config file */

if (!empty($_FILES)) {

	$tempFile = $_FILES['Filedata']['tmp_name'];
//        $targetPath = str_replace('//','/', ( $_SERVER['DOCUMENT_ROOT'] . $_REQUEST['folder'] . '/' ) );
        $targetPath = str_replace('//','/', ( dirname( __FILE__ ) . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' ) );

        $better_token = md5(uniqid(rand(), true)) . '-' . md5( $_FILES['Filedata']['name'] ) . '.' . get_extension( $_FILES['Filedata']['name'] );
        $targetFile =  str_replace('//','/',$targetPath) . $better_token;

	// $fileTypes  = str_replace('*.','',$_REQUEST['fileext']);
	// $fileTypes  = str_replace(';','|',$fileTypes);
	// $typesArray = split('\|',$fileTypes);
	// $fileParts  = pathinfo($_FILES['Filedata']['name']);

	// if (in_array($fileParts['extension'],$typesArray)) {
		// Uncomment the following line if you want to make the directory if it doesn't exist
//		 mkdir(str_replace('//','/',$targetPath), 0755, true);

		if( @move_uploaded_file($tempFile,$targetFile) ) {
                    echo $better_token;
                } else {
                    echo 'error';
                }
	// } else {
	// 	echo 'Invalid file type.';
	// }
}

?>