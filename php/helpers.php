<?php

/**
 *
 * @param string $email
 * @return boolean
 */
function is_valid_email($email) {
  $result = TRUE;
  if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)) {
    $result = FALSE;
  }
  return $result;
}

/**
 * Get file permisions on the server
 * @param string $filename
 * @return string
 */
function server_get_File_Permissions( $filename ) {

    $perms = @fileperms($filename);
    if( $perms == false ) return;

    if     (($perms & 0xC000) == 0xC000) { $info = 's'; }
    elseif (($perms & 0xA000) == 0xA000) { $info = 'l'; }
    elseif (($perms & 0x8000) == 0x8000) { $info = '-'; }
    elseif (($perms & 0x6000) == 0x6000) { $info = 'b'; }
    elseif (($perms & 0x4000) == 0x4000) { $info = 'd'; }
    elseif (($perms & 0x2000) == 0x2000) { $info = 'c'; }
    elseif (($perms & 0x1000) == 0x1000) { $info = 'p'; }
    else                                 { $info = 'u'; }

    $info .= (($perms & 0x0100) ? 'r' : '-');
    $info .= (($perms & 0x0080) ? 'w' : '-');
    $info .= (($perms & 0x0040) ? (($perms & 0x0800) ? 's' : 'x' ) : (($perms & 0x0800) ? 'S' : '-'));

    $info .= (($perms & 0x0020) ? 'r' : '-');
    $info .= (($perms & 0x0010) ? 'w' : '-');
    $info .= (($perms & 0x0008) ? (($perms & 0x0400) ? 's' : 'x' ) : (($perms & 0x0400) ? 'S' : '-'));

    $info .= (($perms & 0x0004) ? 'r' : '-');
    $info .= (($perms & 0x0002) ? 'w' : '-');
    $info .= (($perms & 0x0001) ? (($perms & 0x0200) ? 't' : 'x' ) : (($perms & 0x0200) ? 'T' : '-'));

    return $info;
}

/**
 * Get file permisions on the server and return them as a number
 * @param string $filename
 * @return long
 */
function server_get_File_Permissions_Num( $filename ) {

    $val = 0;
    $perms = @fileperms($filename);

    if( $perms == false ) return;
    // Owner; User
    $val += (($perms & 0x0100) ? 0x0100 : 0x0000); //Read
    $val += (($perms & 0x0080) ? 0x0080 : 0x0000); //Write
    $val += (($perms & 0x0040) ? 0x0040 : 0x0000); //Execute

    // Group
    $val += (($perms & 0x0020) ? 0x0020 : 0x0000); //Read
    $val += (($perms & 0x0010) ? 0x0010 : 0x0000); //Write
    $val += (($perms & 0x0008) ? 0x0008 : 0x0000); //Execute

    // Global; World
    $val += (($perms & 0x0004) ? 0x0004 : 0x0000); //Read
    $val += (($perms & 0x0002) ? 0x0002 : 0x0000); //Write
    $val += (($perms & 0x0001) ? 0x0001 : 0x0000); //Execute

    // Misc
    $val += (($perms & 0x40000) ? 0x40000 : 0x0000); //temporary file (01000000)
    $val += (($perms & 0x80000) ? 0x80000 : 0x0000); //compressed file (02000000)
    $val += (($perms & 0x100000) ? 0x100000 : 0x0000); //sparse file (04000000)
    $val += (($perms & 0x0800) ? 0x0800 : 0x0000); //Hidden file (setuid bit) (04000)
    $val += (($perms & 0x0400) ? 0x0400 : 0x0000); //System file (setgid bit) (02000)
    $val += (($perms & 0x0200) ? 0x0200 : 0x0000); //Archive bit (sticky bit) (01000)

    return $val;
}


/**
 * Checks if a value is in an array
 * @param array $array
 * @param mixed $value
 * @return boolean
 */
function is_it_in_array( $array, $value ) {

  $checked = false;
  if( is_array($array) && count($array) > 0 ) {
    $checked = in_array( $value, $array ) ? true : false;
  }

  return $checked;
}

/**
 * Executes a CHMOD thru FTP
 * @param string $path
 * @param octal $mod
 * @param array $ftp_details array( ftp_user_name, ftp_user_pass, ftp_root, ftp_server )
 * @return boolean
 */
function chmod_11oo10( $path, $mod, $ftp_details ) {

//    $ftp_details['ftp_user_name'] = $row['username'];
//    $ftp_details['ftp_user_pass'] = $row['password'];
//    $ftp_details['ftp_root'] = '/public_html/';
//    $ftp_details['ftp_server'] = 'ftp'.$_SERVER['HTTP_HOST'];
    
    // extract ftp details (array keys as variable names)
    extract ($ftp_details);

    // set up basic connection
    $conn_id = ftp_connect($ftp_server);

    // login with username and password
    $login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

    // try to chmod $path directory
    if (ftp_site($conn_id, 'CHMOD '.$mod.' '.$ftp_root.$path) !== false) {
        $success=TRUE;
    }  else {
        $success=FALSE;
    }

    // close the connection
    ftp_close($conn_id);
    return $success;
}

/**
 * Compares a value for sorting in multi dimensinal array with usort
 * @param integer $a
 * @param integer $b
 * @return integer
 */
function sortByOrderASC($a, $b) {

    $cmp_a = isset( $a['tab_order'] ) && ( strlen( $a['tab_order'] ) > 0 ) ? (int) $a['tab_order'] : false;
    $cmp_b = isset( $b['tab_order'] ) && ( strlen( $b['tab_order'] ) > 0 ) ? (int) $b['tab_order'] : false;

    if( $cmp_a === false ) return 1;
    if( $cmp_b === false ) return -1;

    if ( $cmp_a == $cmp_b ) {
        return 0;
    }

    return ( $cmp_a > $cmp_b ) ? 1 : -1;

}

/**
 * CHMOD recursive thru PHP, safe mode needs to be off
 * @param string $path
 * @param octal $filemode
 * @return boolean
 */
function chmodr($path, $filemode) {
    if (!is_dir($path))
        return chmod($path, $filemode);

    $dh = opendir($path);
    while (($file = readdir($dh)) !== false) {
        if($file != '.' && $file != '..') {
            $fullpath = $path.'/'.$file;
            if(is_link($fullpath))
                return FALSE;
            elseif(!is_dir($fullpath) && !chmod($fullpath, $filemode))
                    return FALSE;
            elseif(!chmodr($fullpath, $filemode))
                return FALSE;
        }
    }

    closedir($dh);

    if(chmod($path, $filemode))
        return TRUE;
    else
        return FALSE;
}

?>
