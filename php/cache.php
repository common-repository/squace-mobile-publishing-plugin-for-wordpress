<?php

/*
 * cache.php
 *
 * This LICENSE is in the BSD license style.
 *
 *
 * Copyright (c) 1999-2009, Ilija Matoski
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   Redistributions of source code must retain the above copyright
 *   notice, this list of conditions and the following disclaimer.
 *
 *   Redistributions in binary form must reproduce the above copyright
 *   notice, this list of conditions and the following disclaimer in the
 *   documentation and/or other materials provided with the distribution.
 *
 *   Neither the name of Ilija Matoski nor the names of his contributors
 *   may be used to endorse or promote products derived from this software
 *   without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED.  IN NO EVENT SHALL THE REGENTS OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 * EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
 * LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */


/**
 * File docBlock description
 *
 * CACHE Functions
 * ON SERVER RUN COMMAND ' chown nobody:nogroup cache/ -R '
 *
 * @author Ilija Matoski
 * @subpackage Cache
 * @version 1.0
 * @support: http://matoski.com
 */

/**
 * Generate file name according to several tags
 * @param string $path (default: null)
 * @param string $id (default: null)
 * @param string $item (default: null)
 * @param string $extra (default: null) creates sha1 of the extra ( put SQL here )
 * @return string
 */
function cache_generate_Name( $path = null, $id = null, $item = null, $extra = null ) {

    $path = is_null( $path ) ? null : ( substr( $path , -1, 1 ) == DIRECTORY_SEPARATOR ? $path . DIRECTORY_SEPARATOR : $path );
    $item = is_null( $item ) ? null : $item;
    $id = is_null( $id ) ? null : "-" . md5($id);
    //$id = is_null( $id ) ? null : md5($id);
    $extra = is_null( $extra ) ? null : "-" . sha1($extra);

    if( is_null( $path ) || is_null( $id ) ) return;

    /*
    @mkdir( $path . $id, '0777', true );
    @chmod( $path . $id, '0777' );
    */

    return "${path}${item}${id}${extra}.cache";
//    return "${path}${id}" .DIRECTORY_SEPARATOR . "${item}${extra}.cache";

}

/**
 * Generate file name according to several tags
 * @param string $path (default: null)
 * @param string $id (default: null)
 * @param string $item (default: null)
 * @param string $extra (default: null) creates sha1 of the extra ( put SQL here )
 * @return string
 */
//function cache_generate_Name_SQ( $path = null, $id = null, $item = null, $extra = null ) {
function cache_generate_Name_SQ( $path = null, $item = null ) {

    $path = is_null( $path ) ? null : ( substr( $path , -1, 1 ) == DIRECTORY_SEPARATOR ? $path . DIRECTORY_SEPARATOR : $path );
    $item = is_null( $item ) ? null : $item;

    if( is_null( $path ) ) return;

    return "${path}${item}.cache";

}

/**
 * Delete a Cache file
 * @param string $filename
 * @return boolean
 */
function cache_Delete( $filename ) {
    if( is_file ($filename) ) {
        @chmod( $filename, 0777 );
        return @unlink( $filename );
    }
    return false;
}

/**
 * Check if a file cache has expired
 * @param string $filename cache file
 * @param integer $cache_time (in seconds) How much the file is supposed to be cached<br/>
 *                             0 assume cache expired
 *                            -1 infinity cache no expire
 * @return boolean
 * true/false means if expired or not
 */
function cache_Expired( $filename, $cache_time ) {
    return is_file( $filename ) ? ( ( $cache_time == -1 ) ? false : filemtime($filename) < ( time() - $cache_time ) ) : true;
}

/**
 * Write a cache to a file
 * @param string $filename
 * @param data $contents string or binary data
 * @return boolean
 */
function cache_Write( $filename, $contents, $serialize = true, $base64 = true ) {

    cache_Delete( $filename );
    $handle = fopen( $filename, "w+" );

    if( $handle ) {

        if( $serialize ) {

            if( $base64 ) {
                $return = base64_encode( serialize( $contents ) );
            } else {
                $return = serialize( $contents );
            }

        } else {

            if( $base64 ) {
                $return = base64_encode( $contents );
            } else {
                $return = $contents;
            }

        }

        $contents = $return;

        $result = fwrite( $handle, $contents );
        fclose( $handle );
        return $result;
    }

    return false;

}

/**
 * Write a cache to a file in XML format
 * @param string $filename
 * @param array $contents array of the data
 * @return boolean
 */
function cache_Write_XML( $filename, $contents ) {

    /* Load of first call PEAR XML/Serializer */
    require_once( 'XML' . DIRECTORY_SEPARATOR . 'Serializer.php' );

    $serializer = new XML_Serializer( array( "typeHints" => true, "defaultTagName" => "item", "indent" => "    " ) );

    $result = $serializer->serialize( $contents );

    if( $result ) {

        cache_Delete( $filename );
        $handle = fopen( $filename, "w+" );

        if( $handle ) {
            $result = fwrite( $handle, $serializer->getSerializedData() );
            fclose( $handle );
            return $result;
        }

    }

    return false;

}

/**
 * Read cache data from file and return content if not expired
 * @param string $filename Cache File
 * @param integer $cache_time (seconds) Cache time for a file <br/>
 *                             0 assume cache expired
 *                            -1 infinity cache no expire
 * @return boolean|data
 * returns false if not data can be found or if the cache is expired<br/>
 * otherwise it returns the data from the file
 */
function cache_Read( $filename, $cache_time = CACHE_DEFAULT_EXPIRE_TIME, $serialize = true, $base64 = true  ) {

    $cache_Expired =  cache_Expired( $filename, $cache_time ) ;
    if( $cache_Expired ) return false;

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
 * Read XML cache data from file and return content if not expired
 * @param string $filename Cache File
 * @param integer $cache_time (seconds) Cache time for a file <br/>
 *                             0 assume cache expired
 *                            -1 infinity cache no expire
 * @return boolean|data
 * returns false if not data can be found or if the cache is expired<br/>
 * otherwise it returns the data from the file
 */
function cache_Read_XML( $filename, $cache_time ) {

    /* Load of first call PEAR XML/Unserializer */
    require_once( 'XML' . DIRECTORY_SEPARATOR . 'Unserializer.php' );

    if( cache_Expired( $filename, $cache_time ) ) return false;

    $unserializer = new XML_Unserializer();
    $result = $unserializer->unserialize( $filename, true );

    return $result ? $unserializer->getUnserializedData() : false;

}

/**
 * Cleans the path of all cache files that have expired
 * @param string $path File Path containing caches
 * @param integer $cache_time (seconds) (default: 0) Cache time for a file) <br/>
 *                             0 deletes all cache files <br/>
 *                            -1 doesnt delete any file
 * @param boolean $recursively (default: false) search thru the all subdirectories
 * @return boolean
 */
function cache_CleanPath( $path, $cache_time = 0, $recursively = false, $exclude = array() ) {

    $files = $recursively ? server_get_Files_Recursively( $path ) : scandir( $path );
    if( $files == false ) return false;

    if( substr( $path , -1, 1 ) == DIRECTORY_SEPARATOR ) $path = substr( $path, 0, strlen($path) - 1 );

    foreach( $files as $file ) {
        if( !$recursively ) {
            if ( strcmp( $file, '.' ) == 0 || strcmp( $file, '..' ) == 0 ) continue;
            if ( is_it_in_array( $exclude, $file ) ) continue;
            $filename = ( $path . DIRECTORY_SEPARATOR . $file );
            if( is_dir( $filename ) ) continue;
        } else {
            $filename = $file;
        }

        if( cache_Expired( $filename, $cache_time ) ) cache_Delete( $filename );
    }

    return true;

}

/**
 * Cleans the path of all cache files that matches the criteria that have expired
 * @param string $path File Path containing caches
 * @param string $criteria
 * @param integer $cache_time (seconds) (default: 0) Cache time for a file) <br/>
 *                             0 deletes all cache files <br/>
 *                            -1 doesnt delete any file
 * @param boolean $recursively (default: false) search thru the all subdirectories
 * @return boolean
 */
function cache_CleanPath_Criteria( $path, $criteria, $cache_time = 0, $recursively = false ) {

    $files = $recursively ? server_get_Files_Recursively( $path ) : scandir( $path );
    if( $files == false ) return false;

    if( substr( $path , -1, 1 ) == DIRECTORY_SEPARATOR ) $path = substr( $path, 0, strlen($path) - 1 );

    foreach( $files as $file ) {
        if( !$recursively ) {
            if ( strcmp( $file, '.' ) == 0 || strcmp( $file, '..' ) == 0 ) continue;
            $filename = ( $path . DIRECTORY_SEPARATOR . $file );
            if( is_dir( $filename ) ) continue;
        } else {
            $filename = $file;
        }

        if( strstr( $filename, $criteria ) == false ) continue;
        if( cache_Expired( $filename, $cache_time ) ) cache_Delete( $filename );
    }

    return true;

}


?>