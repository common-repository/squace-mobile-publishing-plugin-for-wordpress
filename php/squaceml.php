<?php


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
 * Create a tab list for the XML
 * @global array $settings
 * @global integer $grid_size
 * @global integer $_page
 * @global integer $_squace_type
 * @global integer $_tab_selected
 * @return string
 */
function _get_tablist( $extra = array() ) {

    global $settings, $grid_size, $_page, $_squace_type, $_tab_selected;

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );

    $plugin_dir_uploaded_pictures = "{$plugin_dir}uploads/";
    $feed_generator = $plugin_dir . "php/squaceml.php";

    $_extra = array(); $deselect_all = true;
    if( is_array($extra) && count($extra) > 0 ) {
        foreach( $extra as $link ) {
            if( is_array($link) ) {
                $link['link'] = htmlentities( $link['link'] );
                $_filter = $link['selected'] == false ? "false" : "true";
                $_extra[] = "<tab link='{$link['link']}' text='{$link['tab']}' selected='{$_filter}'/>";
                $deselect_all = $deselect_all && $link['selected'];
            }
        }
    } else {
        $deselect_all = false;
    }

    $select_filter = $deselect_all == false ? "true" : "false";
    
    switch ( $_squace_type ) {

        //Light
        case 0:
            $_act = htmlentities( $feed_generator . "?action=screen&sqt={$_squace_type}" );
            $_return = '<tab link="' . $_act . '" text="Home page" selected="'.$select_filter.'"/>';
            break;

        //Complete
        case 1:
            $_selected[0] = ( $_tab_selected == 0 ? 'selected="'.$select_filter.'"' : 'selected="false"' );
            $_selected[1] = ( $_tab_selected == 1 ? 'selected="'.$select_filter.'"' : 'selected="false"' );
            $_selected[2] = ( $_tab_selected == 2 ? 'selected="'.$select_filter.'"' : 'selected="false"' );
            $_selected[3] = ( $_tab_selected == 3 ? 'selected="'.$select_filter.'"' : 'selected="false"' );
            $_act = htmlentities( $feed_generator . "?action=screen&sqt={$_squace_type}&tbs=" );
            $_return = "\t\t" . '<tab link="' . $_act . '0" text="Home page" ' . $_selected[0] . '/>'
                   . "\n\t\t" . '<tab link="' . $_act . '2" text="By categories" ' . $_selected[2] . '/>'
                   . "\n\t\t" . '<tab link="' . $_act . '1" text="All posts" ' . $_selected[1] . '/>'
                   . "\n\t\t" . '<tab link="' . $_act . '3" text="All pages" ' . $_selected[3] . '/>';
            break;

        //Custom
        case 2:
            $_act = $feed_generator . "?action=screen&sqt={$_squace_type}&tbs={stab}&ctp={ctp}";
            $_tab = array();

            $fgct = $settings['fgct'];

            foreach( $fgct as $key => $tab ) {
                $_tact = str_replace( "{stab}", $key, $_act );
                $_tact = htmlentities( str_replace( "{ctp}", $tab['squacetypecustomgenerate'], $_tact ) );
                $_name = htmlentities( $tab['tab_field_name'] );
                $_selected = ( $key == $_tab_selected ? 'selected="'.$select_filter.'"' : 'selected="false"' );
                $_tab[] = ( "\t\t" . '<tab text="' . $_name . '" link="' . $_tact . '" ' . $_selected . '/>' );
            }
            $_return = implode( "\n", $_tab );
            break;

        default:
            break;

    }

    if( $extra['order'] == 0 ) {
        $_return = implode( "\n", $_extra ) . "\n" . $_return;
    } else {
        $_return = $_return . "\n" . implode( "\n", $_extra );
    }

    return $_return;

}

/**
 * Gets the menu items
 * @return string
 */
function _get_menuitems() { return; }

/**
 * Get and convert the data to usable format
 * @global object $dl_pluginSquace
 * @param string $query
 * @param integer $type_feed
 * @param boolean $convert_to_array
 * @param boolean $return_params_only only return needed items for when accessing from cache
 * @return array
 */
function _work_type_feed( $query, $type_feed, $convert_to_array = true, $return_params_only = false ) {

    global $dl_pluginSquace;

    $_return = array();

    $item_id = 'ID';
    $item_title = 'post_title';

    switch( $type_feed ) {
        case SQUACE_TYPE_FEED_POSTS:
            if( !$return_params_only ) {
                $_return = get_posts($query);
                foreach( $_return as $post ) {
                    setup_postdata( $post );
                }
                if( $convert_to_array ) {
                    $_return = $dl_pluginSquace->build_array_from_objects( $_return );
                }
            }
            $item_type = 'post';
            break;
        case SQUACE_TYPE_FEED_PAGES:
            if( !$return_params_only ) {
                $_return = get_pages($query);
                if( $convert_to_array ) {
                    $_return = $dl_pluginSquace->build_array_from_objects( $_return );
                }
            }
            $item_type = 'pg';
            break;
        case SQUACE_TYPE_FEED_TAGS:
            if( !$return_params_only ) {

            }
            $item_type = 'post';
            break;
        case SQUACE_TYPE_FEED_COMMENTS:
            $item_type = 'comment';
            $item_id = 'comment_ID';
            $item_title = 'comment_author';
            $defaults = array( 'orderby' => 'comment_date_gmt', 'order' => 'DESC', 'post_id' => $query );
            if( !$return_params_only ) {
                $_return = get_comments( $defaults );
                if( $convert_to_array ) {
                    $_return = $dl_pluginSquace->build_array_from_objects( $_return );
                }
            }
            break;
        default:
            return false;
            break;
    }

    return array( "feed" => $_return, "item_type" => $item_type, 'item_id' => $item_id, 'item_title' => htmlspecialchars( $item_title ) );
}

/**
 * Get items to infobox conversions, with paging
 * @global array $settings
 * @global integer $grid_size
 * @global integer $_page
 * @global string $background_image
 * @global integer $_squace_type
 * @global object $dl_pluginSquace
 * @global integer $_tab_selected
 * @global integer $posts_per_page
 * @global integer $_category
 * @global boolean $caching
 * @param integer $type
 * @param integer $type_feed
 * @param string $query
 * @param array $link_options
 * @param string $screen_type
 * @return array
 */
function _get_squace_items_to_infobox( $type, $type_feed, $query, $link_options, $screen_type = 'screen' ) {

    global $settings, $grid_size, $_page, $background_image, $_squace_type, $dl_pluginSquace, $_tab_selected, $posts_per_page, $_category, $caching;

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = $plugin_dir . DEFAULT_IMAGES_PATH_UPLOADS .'/';
    $feed_generator = $plugin_dir . "php/squaceml.php";
    $_tmp = array();

    $link_gen = "&" . implode( "&", $link_options );
    $cache_data = false;
    if( $caching ) {
        $cache_Name_All_Records = str_replace( "//", "/", cache_generate_Name( dirname( __FILE__ ) . '/../' . DEFAULT_CACHE_PATH . '/' , $_squace_type, $screen_type, "{$query}" ) );
        $cache_data = cache_Read( $cache_Name_All_Records, CACHE_DEFAULT_EXPIRE_TIME );
    }

    if( $cache_data == false ) {
        $list = _work_type_feed( $query, $type_feed );
        extract( $list );
        $list = $feed;
        $numlist = count( $list );
        if( $caching ) cache_Write( $cache_Name_All_Records, $list );
    } else {
        $list = _work_type_feed( $query, $type_feed, true, true );
        extract( $list );
        $list = $cache_data;
        $numlist = count( $list );
    }

    if( $numlist == 0 ) return;
    if( $numlist == 1 ) {
        ob_end_clean();
        $_redirect = $feed_generator . "?action=infobox&{$item_type}={$list[0][$item_id]}";
        header( "Location: {$_redirect}");
    };

    $links_needed = ( ( $numlist > $grid_size ) ? 1 : 0 ) + ( $_page > 0 ? 1 : 0 );
    $_totallist = $grid_size - $links_needed;
    $_offset = ( $grid_size - 1 ) * ( $_page > 0 ? 1 : 0 ) + $_totallist * ( ( $_page - 1 ) > 0 ? $_page : 0 );
    $list = array_splice( $list, $_offset, $_totallist );
    $prev_id = -1;
    $next_id = -1;
    $next_array = array();
    foreach( $list as $item ) {
        $_link = htmlentities( $feed_generator . "?action=infobox&{$item_type}={$item[$item_id]}{$next_prev}" );
        $_title = htmlspecialchars ( $item[$item_title] );
        $_tmp[] = "\t\t" . '<square title="' . $_title . '" link="' . $_link . '"/>';
    }


    $_next_page = $_page + 1;
    $_prev_page = $_page - 1;
    $_link_page[0] = htmlentities( $feed_generator . "?action={$screen_type}{$link_gen}&page={$_prev_page}" );
    $_link_page[1] = htmlentities( $feed_generator . "?action={$screen_type}{$link_gen}&page={$_next_page}" );

    $tmp = array();
    switch( $links_needed ) {
        case 0:
            break;
        case 1:
            if( ( count( $list ) > 0 ) ) {
                $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
                $_tmp[] = $_next;
            }
            break;
        case 2:
            $_prev = "\t\t" . '<square title="Previous Page" displayletter="&lt;" link="' . $_link_page[0] . '"/>';
            $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
            if( count( $list ) > 0 ) { $tmp[] = $_prev; }
            foreach( $_tmp as $_t ) { $tmp[] = $_t; }
            if( ( $_offset + $_totallist < $numlist ) && ( count( $list ) > 0 ) ) { $tmp[] = $_next; }
            $_tmp = $tmp;
            break;
        default:
            break;
    }

    return $_tmp;

}

/**
 * Get the main square grid for the squaceml
 * @global array $settings
 * @global integer $squacecustomtype
 * @global integer $grid_size
 * @global integer $_page
 * @global string $background_image
 * @global integer $_squace_type
 * @global object $dl_pluginSquace
 * @global integer $_tab_selected
 * @global integer $posts_per_page
 * @global integer $_category
 * @global boolean $caching
 * @return array
 */
function _get_square() {

    global $settings, $squacecustomtype, $grid_size, $_page, $background_image, $_squace_type, $dl_pluginSquace, $_tab_selected, $posts_per_page, $_category, $caching;

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = $plugin_dir . DEFAULT_IMAGES_PATH_UPLOADS .'/';
    $feed_generator = $plugin_dir . "php/squaceml.php";
    $_tmp = array();

    switch ( $_squace_type ) {

        //Light
        case SQUACE_LIGHT_FEED:

            $_tmp = array();

            $query = "numberposts={$posts_per_page}&orderby=date";
            $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}" );
            $_tmp = _get_squace_items_to_infobox( SQUACE_LIGHT_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );

            return array( "bgimage" => $background_image, "grid" => implode( "\n", $_tmp ) );
            break;

        //Complete
        case SQUACE_COMPLETE_FEED:

            $_tmp = array();

            switch( $_tab_selected ) {

                // Home news ( same as Light )
                case 0:
                    $query = "numberposts={$posts_per_page}&orderby=date";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_COMPLETE_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );
                    break;

                // All posts
                case 1:

                    $query = "numberposts=-1&orderby=date";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_COMPLETE_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );
                    break;

                //All posts by categories and subcategories
                case 2:

                    $query = "category={$_category}&numberposts=-1&orderby=date";
                    $cache_data = false;
                    if( $caching ) {
                        $cache_Name_All_Records = str_replace( "//", "/", cache_generate_Name( dirname( __FILE__ ) . '/../' . DEFAULT_CACHE_PATH . '/' , $_squace_type, 'screen', "{$query}" ) );
                        $cache_data = cache_Read( $cache_Name_All_Records, CACHE_DEFAULT_EXPIRE_TIME );
                    }

                    if( $cache_data == false ) {
                        $categories = array();
                        $categories = $dl_pluginSquace->build_array_from_objects(
                            get_categories( array ( 'type' => 'post', 'parent' => $_category ) )
                        );

                        $postslist = array();
                        if( $_category != 0 ) {
                            $postslist = get_posts( "category={$_category}&numberposts=-1&orderby=date");
                            foreach( $postslist as $post ) { setup_postdata($post); }
                            $postslist = $dl_pluginSquace->build_array_from_objects( $postslist );
                        }

                        $numcats = count( $categories );
                        $numposts = count( $postslist );
                        $num = $numposts + $numcats;

                        $_tlist = array_merge( $categories, $postslist );
                        if( $caching ) cache_Write( $cache_Name_All_Records, $_tlist );
                    } else {
                        $_tlist = $cache_data;
                        $num = count( $_tlist );
                    }

                    if( $num == 0 ) return;
                    if( $num == 1 ) {
                        ob_end_clean(); $item_type = 'post'; $item_id = 'ID';
                        $_redirect = $feed_generator . "?action=infobox&{$item_type}={$_tlist[0][$item_id]}";
                        header( "Location: {$_redirect}");
                    };

                    $links_needed = ( ( $num > $grid_size ) ? 1 : 0 ) + ( $_page > 0 ? 1 : 0 );
                    $_totalposts = $grid_size - $links_needed;
                    $_offset = ( $grid_size - 1 ) * ( $_page > 0 ? 1 : 0 ) + $_totallist * ( ( $_page - 1 ) > 0 ? $_page : 0 );
                    $_tlist = array_splice( $_tlist, $_offset, $_totalposts );
                    foreach( $_tlist as $_list ) {

                        //enter a subcategory
                        if( !isset( $_list['post_type']) ) {
                            $_link = htmlentities( $feed_generator . "?action=screen&sqt={$_squace_type}&tbs={$_tab_selected}&cat={$_list['cat_ID']}" );
                            $_title = htmlspecialchars ( $_list['name'] );
                            $_tmp[] = "\t\t" . '<square title="' . $_title . '" link="' . $_link . '"/>';
                        } else { //info box
                            $_link = htmlentities( $feed_generator . "?action=infobox&post={$_list['ID']}" );
                            $_title = htmlspecialchars ( $_list['post_title'] );
                            $_tmp[] = "\t\t" . '<square title="' . $_title . '" link="' . $_link . '"/>';
                        }

                    }

                    $_next_page = $_page + 1;
                    $_prev_page = $_page - 1;
                    $_link_page[0] = htmlentities( $feed_generator . "?action=screen&sqt={$_squace_type}&tbs={$_tab_selected}&category={$_category}&page={$_prev_page}" );
                    $_link_page[1] = htmlentities( $feed_generator . "?action=screen&sqt={$_squace_type}&tbs={$_tab_selected}&category={$_category}&page={$_next_page}" );

                    $tmp = array();
                    switch( $links_needed ) {
                        case 0:
                            break;
                        case 1:
                            if( ( count( $_tlist ) > 0 ) ) {
                                $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
                                $_tmp[] = $_next;
                            }
                            break;
                        case 2:
                            $_prev = "\t\t" . '<square title="Previous Page" displayletter="&lt;" link="' . $_link_page[0] . '"/>';
                            $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
                            if( count($_tlist) > 0 ) { $tmp[] = $_prev; }
                            foreach( $_tmp as $_t ) { $tmp[] = $_t; }
                            if( ( $_offset + $_totalposts < $numposts ) && ( count( $_tlist ) > 0 ) ) { $tmp[] = $_next; }
                            $_tmp = $tmp;
                            break;
                        default:
                            break;
                    }
                    break;

                // All pages
                case 3:
                    $query = "numberposts=-1&orderby=date";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_COMPLETE_FEED, SQUACE_TYPE_FEED_PAGES, $query, $link_options );
                    break;

                default:
                    break;
            }

            return array( "bgimage" => $background_image, "grid" => implode( "\n", $_tmp ) );
            break;

        //Custom
        case SQUACE_CUSTOM_FEED:

            $tabs = $settings['fgct'];

            $tab = $tabs[$_tab_selected];
            if( !( count($tab) > 0 ) ) return;

            $squacecustomtype = $tab['squacetypecustomgenerate'];

            $_tmp = array();

            switch ( $squacecustomtype ) {

                //Home News
                case 0:
                    $query = "numberposts=-1&orderby=date";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}", "ctp={$squacecustomtype}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_CUSTOM_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );
                    break;

                // Categories
                case 1:
                    $_cats = '';
                    if( isset($tab['cat']) ) { $_cats = implode(',', $tab['cat'] ); }
                    $query = "numberposts=-1&orderby=date&category={$_cats}";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}", "ctp={$squacecustomtype}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_CUSTOM_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );
                    break;

                // Tags
                case 2:
                    $_tags = array();
                    if( isset($tab['tag']) ) {
                        foreach( $tab['tag'] as $slug ) {
                            $_tag = get_tag( $slug );
                            $_tags[] = $_tag->slug;
                        }
                    }
                    $_tags = implode( ',', $_tags );
                    $query = "numberposts=-1&orderby=date&tag={$_tags}";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}", "ctp={$squacecustomtype}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_CUSTOM_FEED, SQUACE_TYPE_FEED_POSTS, $query, $link_options );
                    break;

                //Pages
                case 3:
                    $_ipages = '';
                    if( isset($tab['page']) ) { $_ipages = implode(',', $tab['page'] ); }
                    $query = "numberposts=-1&orderby=date&include={$_ipages}";
                    $link_options = array( "sqt={$_squace_type}", "tbs={$_tab_selected}", "ctp={$squacecustomtype}" );
                    $_tmp = _get_squace_items_to_infobox( SQUACE_CUSTOM_FEED, SQUACE_TYPE_FEED_PAGES, $query, $link_options );
                    break;

                // end
                default:
                    break;
            }

            $plugin_dir = str_replace( "://", ":///", $plugin_dir );
            $_bg_image = str_replace( "//", "/", $plugin_dir . DEFAULT_MOCKUP_BACKGROUND );
            if( strlen( $tab['background_image'] ) > 0 ) {
                $_bg_image = str_replace( "//", "/", $plugin_dir . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $tab['background_image'] );
            } else {
                $_bg_image = strlen( $background_image ) > 0 ? $background_image : $_bg_image;
            }
            return array( "bgimage" => $_bg_image, "grid" => implode( "\n", $_tmp ) );
            break;

        default:
            break;
    }



}

/**
 * Converts the results to infobox squaceml
 * @global array $settings
 * @global integer $grid_size
 * @global integer $_page
 * @global string $background_image
 * @global integer $_comments
 * @global integer $_post
 * @global boolean $caching
 * @global object $dl_pluginSquace
 * @global integer $_pg_infobox
 * @return array
 */
function _get_infobox() {

    global $settings, $grid_size, $_page, $background_image, $_comments, $_post, $caching, $dl_pluginSquace, $_pg_infobox;

    //both are set this is invalid
    if( !( $_post > 0 ) && !( $_pg_infobox > 0 ) ) return;

    $_process = '';
    $_opt = '';
    if( $_post > 0 ) { $_process = 'post'; $_opt = $_post; }
    if( $_pg_infobox > 0 ) { $_process = 'page'; $_opt = $_pg_infobox; }
    if( ($_comments > 0) && ($_post > 0) ) { $_process = 'comment'; $_opt = $_comments; }

    if( !(strlen($_process) > 0) ) return;

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $feed_generator = $plugin_dir . "php/squaceml.php";

    $cache_data = false;
    if( $caching ) {
        $cache_Name_All_Records = str_replace( "//", "/", cache_generate_Name( dirname( __FILE__ ) . '/../' . DEFAULT_CACHE_PATH . '/' , $_opt, 'infobox', $_process ) );
        $cache_data = cache_Read( $cache_Name_All_Records, CACHE_DEFAULT_EXPIRE_TIME );
    }

    $_return_nextprev = '';
    if( $cache_data == false ) {
        switch( $_process ) {
            case 'post':
                $post = get_post( $_post );
                if( count($post) > 0 ) { $post = $dl_pluginSquace->build_array_from_object( $post ); } else { return; }
                $_return_post = ( $post['post_content'] );
                $_return_title = htmlspecialchars( $post['post_title'] );
                $_sp_comments = ( $feed_generator . "?action=comments&post={$_post}" );
                $_return_comments = ( "<br/><a href='{$_sp_comments}'>Show/Post comments</a>" );
                $_return_post = $_return_comments == false ? $_return_post : $_return_post . $_return_comments;
                $_return_post = $_return_post . $_return_nextprev;
                break;
            case 'page':
                $post = get_page( $_pg_infobox );
                if( count($post) > 0 ) { $post = $dl_pluginSquace->build_array_from_object( $post ); } else { return; }
                $_return_post = ( $post['post_content'] );
                $_return_title = comment_content( $post['post_title'] );
                $_return_post = $_return_post . $_return_nextprev;
                break;
            case 'comment':
                $post = get_comment( $_comments );
                if( count($post) > 0 ) { $post = $dl_pluginSquace->build_array_from_object( $post ); } else { return; }
                $_return_post = ( $post['comment_content'] );
                $_return_title = htmlspecialchars( $post['comment_author'] );
                $_return_post = $_return_post . $_return_nextprev;
                break;
            default:
                return;
                break;
        }
        if( $caching ) cache_Write( $cache_Name_All_Records, array( "post" => $post, "infobox" =>  $_return_post, "title" => htmlspecialchars( $_return_title ) ) );
    } else {
        $post = $cache_data['post'];
        $_return_post = $cache_data['infobox'];
        $_return_title = $cache_data['title'];
    }

    return array( "infobox" =>  $_return_post, "title" => $_return_title );
}

/**
 * Gets the post comments and prepare's them for SQUACEML conversion
 * @global array $settings
 * @global integer $grid_size
 * @global integer $_page
 * @global string $background_image
 * @global integer $_squace_type
 * @global object $dl_pluginSquace
 * @global integer $_tab_selected
 * @global integer $posts_per_page
 * @global integer $_category
 * @global boolean $caching
 * @param integer $_post
 * @return array
 */
function _get_post_comments( $_post ) {

    global $settings, $grid_size, $_page, $background_image, $_squace_type, $dl_pluginSquace, $_tab_selected, $posts_per_page, $_category, $caching;

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = $plugin_dir . DEFAULT_IMAGES_PATH_UPLOADS .'/';
    $feed_generator = $plugin_dir . "php/squaceml.php";
    $_tmp = array();
    $screen_type = 'comments';

    $link_options = array( "post={$_post}");
    $link_gen = "&" . implode( "&", $link_options );
    $cache_data = false;
    if( $caching ) {
        $cache_Name_All_Records = str_replace( "//", "/", cache_generate_Name( dirname( __FILE__ ) . '/../' . DEFAULT_CACHE_PATH . '/' , 'comment', $screen_type, "{$query}?action={$screen_type}{$link_gen}" ) );
        $cache_data = cache_Read( $cache_Name_All_Records, CACHE_DEFAULT_EXPIRE_TIME );
    }

    $post_id = get_post( $_post, ARRAY_A );
    $title = $post_id['post_title'];

    if( $cache_data == false ) {
        $list = _work_type_feed( $_post,  SQUACE_TYPE_FEED_COMMENTS );
        extract( $list );
        $list = $feed;
        $numlist = count( $list );
        if( $caching ) cache_Write( $cache_Name_All_Records, $list );
    } else {
        $list = _work_type_feed( $query, SQUACE_TYPE_FEED_COMMENTS, true, true );
        extract( $list );
        $list = $cache_data;
        $numlist = count( $list );
    }

    $_tmp = array();
    $_link = htmlentities( $feed_generator . "?action=postacomment&post=$_post" );
    $_post_comment_link = "\t\t" . "<square title='Post a comment' displayletter='+' onclick=\"confirm('Do you really want to post a new comment?','Yes','No')\" link='$_link'/>";
    $_tmp[] = $_post_comment_link;
    
    $links_needed = ( ( $numlist > $grid_size ) ? 1 : 0 ) + ( $_page > 0 ? 1 : 0 );
    $_totallist = ( $grid_size - 1 ) - $links_needed; // -1 for New comment link
    $_offset = ( $grid_size - 1 ) * ( $_page > 0 ? 1 : 0 ) + $_totallist * ( ( $_page - 1 ) > 0 ? $_page : 0 );
    $list = array_splice( $list, $_offset, $_totallist );
    foreach( $list as $item ) {
        $_link = htmlentities( $feed_generator . "?action=infobox{$link_gen}&{$item_type}={$item[$item_id]}" );
        $_title = htmlspecialchars ( $item[$item_title] );
        $_tmp[] = "\t\t" . '<square title="' . $_title . '" link="' . $_link . '"/>';
    }

    $_next_page = $_page + 1;
    $_prev_page = $_page - 1;
    $_link_page[0] = htmlentities( $feed_generator . "?action={$screen_type}{$link_gen}&page={$_prev_page}" );
    $_link_page[1] = htmlentities( $feed_generator . "?action={$screen_type}{$link_gen}&page={$_next_page}" );

    $tmp = array();
    switch( $links_needed ) {
        case 0:
            break;
        case 1:
            if( ( count( $list ) > 0 ) ) {
                $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
                $_tmp[] = $_next;
            }
            break;
        case 2:
            $_prev = "\t\t" . '<square title="Previous Page" displayletter="&lt;" link="' . $_link_page[0] . '"/>';
            $_next = "\t\t" . '<square title="Next Page" displayletter="&lt;" link="' . $_link_page[1] . '"/>';
            if( count( $list ) > 0 ) { $tmp[] = $_prev; }
            foreach( $_tmp as $_t ) { $tmp[] = $_t; }
            if( ( $_offset + $_totallist < $numlist ) && ( count( $list ) > 0 ) ) { $tmp[] = $_next; }
            $_tmp = $tmp;
            break;
        default:
            break;
    }

    $plugin_dir = str_replace( "://", ":///", $plugin_dir );
    $_bg_image = str_replace( "//", "/", $plugin_dir . DEFAULT_MOCKUP_BACKGROUND );
    if( strlen( $tab['background_image'] ) > 0 ) {
        $_bg_image = str_replace( "//", "/", $plugin_dir . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $tab['background_image'] );
    } else {
        $_bg_image = strlen( $background_image ) > 0 ? $background_image : $_bg_image;
    }

    return array( "bgimage" => $_bg_image, "grid" => implode( "\n", $_tmp ), "title" => $title );
    
}

/**
 * Get the post form for a new comment
 * @param integer $_post_id
 * @param string $_link
 * @param string $title
 * @param string $_form_name
 * @param string $_method
 * @return string
 */
function _get_postbox( $_post_id, $_link, &$title, $_form_name = 'commentform', $_method = 'post' ) {

    if( !( $_post_id > 0 ) ) return;

    $post_id = get_post( $_post_id, ARRAY_A );
    $title = $post_id['post_title'];

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = "{$plugin_dir}uploads/";
    $feed_generator = $plugin_dir . "/php/squaceml.php";
    
    $_back_link_redirect_to = "{$feed_generator}?action=comments&post={$_post_id}";
    $_back_link = "{$feed_generator}?action=redirect";
    $_link_go_back = $_back_link;
    
    return (
    "
    <form id='{$_form_name}' method='{$_method}' action='{$_link}'>

        <p>
            <label for='author'><small>Name (required)</small></label>
            <input type='text' aria-required='true' tabindex='1' size='22' value='' id='author' name='author'>
        </p>

        <p>
            <label for='email'><small>Mail (will not be published) (required)</small></label>
            <input type='text' aria-required='true' tabindex='2' size='22' value='' id='email' name='email'>
        </p>

        <p>
            <label for='url'><small>Website</small></label>
            <input type='text' tabindex='3' size='22' value='' id='url' name='url'>
        </p>

        <p>
            <label for='comment'><small>Comment (required)</small></label>
            <textarea tabindex='4' rows='10' cols='58' id='comment' name='comment'></textarea>
        </p>

        <p>
            <input type='submit' value='Add Comment' tabindex='5' id='submit' name='submit'>
            <input type='hidden' id='comment_post_ID' value='{$_post_id}' name='comment_post_ID'>
            <input type='hidden' value='0' id='comment_parent' name='comment_parent'>
        </p>

    </form>
    <form id='go_back_form' method='{$_method}' action='{$_link_go_back}>
         <input type='submit' value='Cancel' tabindex='6' id='cancel' name='cancel'>
         <input type='hidden' value='{$_back_link_redirect_to}' id='go_back_to' name='go_back_to'>
    </form>
    ");
    
}

function _post_it_curl( $data ) {

    global $siteicon;
    
    if( empty( $data ) || !( count($data) > 0 ) ) return false;
    
    if( isset( $data['author'] ) && ( strlen($data['author']) > 0 ) ) { $bOK[0] = true; }
    if( isset( $data['email'] ) && ( strlen($data['email']) > 0 ) ) { $bOK[1] = true; }
    if( isset( $data['comment'] ) && ( strlen($data['comment']) > 0 ) ) { $bOK[2] = true; }

    $bOK[1] = $bOK[1] ? is_valid_email($data['email']) : false;
    $bOK_array = $bOK;

    $bOK = $bOK[0] && $bOK[1] && $bOK[2];
    $bCancel = isset( $data['cancel'] ) && ( $data['cancel'] == "Cancel" );
    $go_back_to = isset( $data['go_back_to'] ) && ( strlen( $data['go_back_to'] ) > 0 ) ? $data['go_back_to'] : $siteicon['link'];
    $post_link = get_bloginfo('wpurl') . "/wp-comments-post.php";

    if( !$bOK && !$bCancel ) { return _get_postbox_curl_error($data, $bOK_array); }

    if( !$bCancel ) {

        unset( $data['go_back_to'], $data['submit'] );
        $data['submit'] = 'Submit Comment';

        $array = array();
        foreach( $data as $key => $value ) { $array[] = $key . "=" . urlencode( $value ); }
        
        $options = implode("&", $array );

        $ch = curl_init();

        curl_setopt( $ch, CURLOPT_URL, $post_link );
        curl_setopt( $ch, CURLOPT_HEADER, false );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, false );
        curl_setopt( $ch, CURLOPT_POST, count( $options ) );
        curl_setopt( $ch, CURLOPT_POSTFIELDS, $options );

        $response = curl_exec($ch);
        
        curl_close($ch);
        
    }

    ob_clean();
    header( "Location: {$go_back_to}" );
    die();

    return true;
    
}

/**
 * Get postbox error to show in infobox
 * @param array $data
 * @return array
 */
function _get_postbox_curl_error( $data, $array ) {

    if( !( count($data) > 0 ) ) return false;

    $error = array();
    
    if( $array[0] == false ) { $error[] = 'Please enter a name.'; }
    if( $array[1] == false ) { $error[] = 'Please enter a valid e-mail.'; }
    if( $array[2] == false ) { $error[] = 'Please enter a comment.'; }

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = "{$plugin_dir}uploads/";
    $feed_generator = $plugin_dir . "/php/squaceml.php";

    $go_back_to = isset( $data['go_back_to'] ) && ( strlen( $data['go_back_to'] ) > 0 ) ? $data['go_back_to'] : $siteicon['link'];

    $_link = $feed_generator . "?action=postacomment&post={$data['comment_post_ID']}";

    $error[] = "<a href='{$_link}'>Go back to entering a new comment</a>";
    $error[] = "<a href='{$go_back_to}'>Go back to comments</a>";

    $infobox = ( nl2br( implode( "\n", $error ) ) );
    
    return array(
        "title" => "Error",
        "infobox" => $infobox,
        "type" => SQUACE_LAYOUT_INFOBOX
    );
    
}

/**
 * Get the post form for a new comment, for usage with curl
 * @param integer $_post_id
 * @param string $title
 * @param string $_form_name
 * @param string $_method
 * @return string
 */
function _get_postbox_curl( $_post_id, &$title, $_form_name = 'commentform', $_method = 'post' ) {

    if( !( $_post_id > 0 ) ) return;

    $post_id = get_post( $_post_id, ARRAY_A );
    $title = $post_id['post_title'];

    $plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
    $plugin_dir_uploaded_pictures = "{$plugin_dir}uploads/";
    $feed_generator = $plugin_dir . "/php/squaceml.php";

    $_back_link_redirect_to = "{$feed_generator}?action=comments&post={$_post_id}";
    $_back_link = "{$feed_generator}?action=redirect";
    $_link_go_back = $_back_link;

    return (
    "
    <form id='{$_form_name}' method='{$_method}' action='{$feed_generator}?action=post'>

        <p>
            <label for='author'><small>Name (required)</small></label>
            <input type='text' aria-required='true' tabindex='1' size='22' value='' id='author' name='author'>
        </p>

        <p>
            <label for='email'><small>Mail (will not be published) (required)</small></label>
            <input type='text' aria-required='true' tabindex='2' size='22' value='' id='email' name='email'>
        </p>

        <p>
            <label for='url'><small>Website</small></label>
            <input type='text' tabindex='3' size='22' value='' id='url' name='url'>
        </p>

        <p>
            <label for='comment'><small>Comment (required)</small></label>
            <textarea tabindex='4' rows='10' cols='58' id='comment' name='comment'></textarea>
        </p>

        <p>
            <input type='submit' value='Add Comment' tabindex='5' id='submit' name='submit'>
            <input type='submit' value='Cancel' tabindex='6' id='cancel' name='cancel'>
            <input type='hidden' value='{$_back_link_redirect_to}' id='go_back_to' name='go_back_to'>
            <input type='hidden' id='comment_post_ID' value='{$_post_id}' name='comment_post_ID'>
            <input type='hidden' value='0' id='comment_parent' name='comment_parent'>
        </p>

    </form>
    ");

}

/**
 * Get a generated XML layout
 * @param array $siteicon array( "icon", "link" );
 * @param string $title
 * @param string $tablist
 * @param string $menuitems
 * @param array $data array( "bgimage", "grid" ) || array( "infobox" );
 * @param define $layout_type SQUACE_LAYOUT_SQUAREGRID || SQUACE_LAYOUT_INFOBOX
 * @return string
 */
function _get_layout( $siteicon, $title, $tablist, $menuitems, $data, $layout_type = SQUACE_LAYOUT_SQUAREGRID ) {

    switch( $layout_type ) {
        case SQUACE_LAYOUT_POSTACOMMENT:
            $layout = '<infobox>' . "\n\t" . '<content><![CDATA[' . $data['infobox'] . ']]></content>' . "\n" . '</infobox>';
            $tablist = "";
            $menuitems = "";
            break;
        case SQUACE_LAYOUT_INFOBOX:
            $layout = '<infobox>' . "\n\t" . '<content><![CDATA[' . $data['infobox'] . ']]></content>' . "\n" . '</infobox>';
            $tablist = "";
            $menuitems = "";
            break;
        case SQUACE_LAYOUT_SQUAREGRID;
            $layout = "\t" . '<squaregrid bgimage="' . $data['bgimage'] . '">' . "\n" . $data['grid'] . "\n\t" . '</squaregrid>' . "\n";
            $tablist = '<tablist>' . "\n" . $tablist . "\n\t" . '</tablist>';
            $menuitems = "";
            break;
        case SQUACE_LAYOUT_COMMENTS:
            $layout = "\t" . '<squaregrid bgimage="' . $data['bgimage'] . '">' . "\n" . $data['grid'] . "\n\t" . '</squaregrid>' . "\n";
            $tablist = '<tablist>' . "\n" . $tablist . "\n\t" . '</tablist>';
            break;
        default:
            return;
            break;
    }
    return
'<squaceml>
    <head>
        <iconimage iconsrc="' . $siteicon['icon'] . '" link="' . $siteicon['link'] . '"/>
        <title>' . htmlspecialchars( $title ) . '</title>
        ' . $tablist . '
    </head>
    <body>' . "\n" .
        $layout . '    </body>
</squaceml>';
}

/** Read cache file */
$cache_file = str_replace( "//", "/", dirname(__FILE__) . '/cache.php' );
require_once( $cache_file );
/** End read cache file */

/** Read wp-load file */
$wpload_file = str_replace( "//", "/", dirname(__FILE__) . '/../../../../' . '/wp-load.php' );
require_once( $wpload_file );
/** End read wp-load file */

/** Read config file */
$config_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/config.php' );
require_once( $config_file );
/** End read config file */

/** Read helpers file */
$helpers_file = str_replace( "//", "/",  dirname( __FILE__ ) . '/helpers.php' );
require_once( $helpers_file );
/** End read helpers file */


$filename = str_replace( "//", "/",  dirname( __FILE__ ) . '/../' . SQUACE_SETTINGS_FILE );
$settings = settings_Read( $filename );
$caching = ( $settings['squace_caching_allow'] == 'on' );

if( $settings == false ) { die( 'No site has yet been configured.' ); }

$_action = isset( $_GET['action'] ) & strlen($_GET['action']) > 0 ? $_GET['action'] : false;
$_page = isset( $_GET['page'] ) & ( (int) $_GET['page'] >= 0 ) ? (int) $_GET['page'] : 0;
$_post = isset( $_GET['post'] ) & ( (int) $_GET['post'] > 0 ) ? (int) $_GET['post'] : -1;
$_squace_type = isset( $_GET['sqt'] ) ? (int) $_GET['sqt'] : ( isset( $settings['squacetype'] ) ? $settings['squacetype'] : 0 );
$_tab_selected = isset( $_GET['tbs'] ) ? (int) $_GET['tbs'] : ( isset( $settings['tab_selected'] ) ? $settings['tab_selected'] : 0 );
$_category = isset( $_GET['cat'] ) ? (int) $_GET['cat'] : ( isset( $settings['cat'] ) ? $settings['cat'] : 0 );
$_pg_infobox = isset( $_GET['pg'] ) ? (int) $_GET['pg'] : ( isset( $settings['pg'] ) ? $settings['pg'] : -1 );
$_comments = isset( $_GET['comment'] ) ? (int) $_GET['comment'] : ( isset( $settings['comment'] ) ? $settings['comments'] : -1 );
$_goback = isset( $_GET['goback'] );

switch( $_action ) {
    case 'screen':
        $layout_type = SQUACE_LAYOUT_SQUAREGRID;
        break;
    case 'infobox':
        $layout_type = SQUACE_LAYOUT_INFOBOX;
        break;
    case 'comments':
        $layout_type = SQUACE_LAYOUT_COMMENTS;
        break;
    case 'postacomment':
        $layout_type = SQUACE_LAYOUT_POSTACOMMENT;
        break;
    case 'redirect':
        $layout_type = SQUACE_LAYOUT_REDIRECT;
        break;
    case 'post':
        $layout_type = SQUACE_LAYOUT_POST;
        break;
    default:
        die( 'Invalid action.' );
        break;
}

$dl_pluginSquace = new plugin_Squace();
$grid_size = SQUACE_DEFAULT_GRID_SIZE;
if(isset($_SERVER["HTTP_GRIDSIZE"])){
    $arr = explode("x", $_SERVER["HTTP_GRIDSIZE"]);
    $grid_size = (int) ( ( (int) $arr[0] ) * ( (int) $arr[1] ) );
}
//$grid_size = 5;
$plugin_dir = str_replace( "/php", "/", plugins_url( '', __FILE__ ) );
$plugin_dir_uploaded_pictures = "{$plugin_dir}uploads/";
$feed_generator = $plugin_dir . "/php/squaceml.php";

$pdir = str_replace( "://", ":///", $plugin_dir );
$background_image = isset( $settings['squace_rbackground_image'] ) && strlen( $settings['squace_rbackground_image'] ) > 0 ? str_replace( "//", "/",  $pdir . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $settings['squace_rbackground_image'] ) : str_replace( "//", "/",  $pdir . DEFAULT_MOCKUP_BACKGROUND );

$siteicon = array(
    "icon" => strlen( $settings['squace_ricon'] ) > 0 ? $plugin_dir_uploaded_pictures . $settings['squace_ricon'] : $plugin_dir . DEFAULT_MOCKUP_ICON,
    "link" => $feed_generator . "?action=screen"
);
$title = $settings['feedtitle'];
$extra = array();

switch( $layout_type ) {
    case SQUACE_LAYOUT_SQUAREGRID:
        $data = _get_square();
        break;
    case SQUACE_LAYOUT_INFOBOX:
        $data = _get_infobox();
        $title = $title . ' - ' . $data['title'];
        break;
    case SQUACE_LAYOUT_COMMENTS:
        $data = _get_post_comments( $_post );
        $title = $title . ' - ' . $data['title'];
        $extra = array(
            array(
                "link" => $feed_generator . "?action=infobox&post={$_post}",
                "tab" => "Comments",
                "selected" => true
            ),
            "order" => 0 // 0 - start, 1 - end
        );
        break;
    case SQUACE_LAYOUT_POSTACOMMENT:
        if( USE_CURL_MODULE ) { $data['infobox'] = ( _get_postbox_curl( $_post, $title ) ); }
        else { $data['infobox'] = ( _get_postbox( $_post, get_bloginfo('wpurl') . "/wp-comments-post.php", $_title ) ); }
        $title = $title . ' - New Comment - ' . $_title;
        break;
    case SQUACE_LAYOUT_REDIRECT:
        ob_clean();
        $_redirect = isset( $_POST['go_back_to'] ) && ( strlen( $_POST['go_back_to'] ) > 0 ) ? $_POST['go_back_to'] : $siteicon['link'];
        header( "Location: {$_redirect}");
        die();
        break;
    case SQUACE_LAYOUT_POST:
        $data = _post_it_curl( $_POST );
        if( $data === false ) {
            $title = "Error";
            $data['infobox'] = "Error, something unexpected occured.";
            $layout_type = SQUACE_LAYOUT_INFOBOX;   
        } else {
            $layout_type = $data['type'];
            $title = $data['title'];
        }
        break;
    default:
        die("Unknown action.");
        break;
}

$menuitems = _get_menuitems();
$tablist = _get_tablist( $extra );

@ob_start();

header("Content-Type: text/xml; charset=utf-8");
header("Cache-Control: no-cache");

echo '<?xml version="1.0" encoding="utf-8" standalone="yes"?>' . "\n";
echo _get_layout( $siteicon, $title, $tablist, $menuitems, $data, $layout_type );

?>
