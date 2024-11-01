<?php
/*
	Plugin Name: Squace Mobile Publishing Plugin for Wordpress
	Plugin URI: http://labs.squace.com/wordpress
	Description: The Squace Mobile Publishing Plugin for Wordpress enables site owners to develop a mobile site in just a few minutes and, using the unique distribution method by Squace, distribute your mobile site to your users mobile for free.
	Version: 1.3.5
	Author: Squace
	Author URI: http://squace.com/
*/

require_once( str_replace( "//", "/",  dirname( __FILE__ ) . '/php/config.php' ) );
require_once( str_replace( "//", "/",  dirname( __FILE__ ) . '/php/cache.php' ) );
require_once( str_replace( "//", "/",  dirname( __FILE__ ) . '/php/helpers.php' ) );

global $wp_version, $squace_ajax_load;

if( !$squace_ajax_load ) {
    
    $exit_msg='Squace plugin requires Wordpress 2.9 or newer. Please update!';
    if (version_compare($wp_version,"2.9","<")) {
        return ($exit_msg);
    }
    
}

//separate some stuff from the main plugin
if (!class_exists("squacetools")) {
    class squacetools {

        /**
         * Clean out unused files
         * @return void
         */
        function clean_directories() {

            $directory = dirname( __FILE__ );

            $exclude = array();

            if( isset($tab['squace_rbackground_image']) && strlen($this->_settings['squace_rbackground_image']) > 0 ) { $exclude[] = $this->_settings['squace_rbackground_image']; }
            if( isset($tab['squace_ricon']) && strlen($this->_settings['squace_ricon']) > 0 ) { $exclude[] = $this->_settings['squace_ricon']; }
            if( isset($tab['squace_rpicture']) && strlen($this->_settings['squace_rpicture']) > 0 ) { $exclude[] = $this->_settings['squace_rpicture']; }

            if( isset( $this->_settings['fgct'] ) ) {
                foreach( $this->_settings['fgct'] as $tab ) {
                    if( isset($tab['background_image']) && strlen($tab['background_image']) > 0 ) {
                        $exclude[] = $tab['background_image'];
                    }
                }
            }
                        
            //clean old uploads
            cache_CleanPath( $directory . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' , CACHE_DEFAULT_EXPIRE_TIME * 24, false, $exclude );
            
            //clean expired cache files
            cache_CleanPath_Criteria( $directory . '/' . DEFAULT_CACHE_PATH . '/' , '.cache', CACHE_DEFAULT_EXPIRE_TIME );

        }

        /**
         * Create a _add_div javascript function for inserting new tabs
         * @return string
         */
        function build_JS_ajax( $application ) {

            $url = str_replace("//", "/", $plugin_dir . $application );

            $url = plugins_url( $application, __FILE__ );
            
            $js =
                'function _add_div( type, container ) {
                    
                    var $j = jQuery.noConflict();
                     $j.ajax({
                      url: "' . $url . '",
                      data: "action="+type,
                      cache: false,
                      success: function(html){
                        _tb_Coll(container);
                        $j("#"+container).append($j(html));
                        $j("#"+container+" > div:odd").addClass("odd");
                        $j("#"+container+"> div:gt(1):even").addClass("even");
                        switch( type ) {
                            case "tab":
                                $j("#"+container+" div:last input[type=\'text\']:first").focus();
                                break;
                        }
                      }
                    });
                    
                }
                
            ';

            return "<script type='text/javascript'>{$js}</script>";
        }

        /**
         * Generates a javascript for previewing a picture in mockup display
         * @param string $actionbox
         * @param string $preview_box
         * @param string $preview_url
         * @param string $preview_data
         * @param string $extra
         * @return string
         */
        function get_JS_preview ( $actionbox, $preview_box, $preview_url, $preview_data, $extra = '' ) {


            $_option_list_calc = '';
            $_option_list = '';

            $_option_list_calc = "
                var values = [];
                switch( \${$actionbox}('input#squacetype:checked').val() ) {
                    case '0':
                        values = 'Home page';
                        break;
                    case '1':
                        values = 'Home page;By Category;All posts;All pages';
                        break;
                    case '2':
                        \${$actionbox}(\"#squace-custom-tabs input[name*='tab_field_name']\").each(function() {
                            tmp = \${$actionbox}('#'+this.name).val();
                            values.push( tmp );
                        });
                        values = values.join(';');
                        break;
                }
                var feedtitle = \${$actionbox}('#feedtitle').val();
            ";
            $_option_list = "'&tabnames='+escape( values )+'&feedtitle='+escape(feedtitle)";

            $_response = "?squace_ricon='+escape( \${$actionbox}('#squace_ricon').val() )+'&squace_rbackground_image='+escape( \${$actionbox}('#$preview_data').val() )+{$_option_list}+'";
            
            return
            "
            <script type='text/javascript'>

                var \${$actionbox} = jQuery.noConflict();

                \${$actionbox}(document).ready(function(){

                    \${$actionbox}('#{$actionbox}').click(function() {
                        {$_option_list_calc}
                        \${$actionbox}('#{$preview_box}').css( 'background-image', 'url( {$preview_url}{$_response}{$extra} )' );
                    });

                });

            </script>
            ";
            
        }

        /**
         * Checks if a values are in an array
         * @param array $array
         * @param array $data
         * @return boolean
         */
        function check_ifin_string( $array, $data ) {

            $bIn = false;
            foreach( $array as $key => $item ) {
                $bIn = $bIn || !( strpos( $data, $item ) === false );
            }

            return $bIn;
        }

        /**
         * Gets a part from the string
         * @param string $data
         * @param integer $position
         * @param integer $length
         * @return string
         */
        function get_part_from( $data, $position, $length = null ) {

            return substr( $data, $position, $length );
        }

        /**
         * Format a _POST array to a good array for saving data
         * @param array $array
         * @param integer $action
         * @return array
         */
        function regenerate_Settings( $array, $action = SQUACE_LOAD_SETTINGS ) {

            if( $action == SQUACE_LOAD_SETTINGS ) {
                return $array;
            }

            if( $action == SQUACE_SAVE_SETTINGS ) {

                $tmp = array();

                $_in_string = array(
                    "tab_name_text",
                    "cat",
                    "squacetypecustomgenerate",
                    "tag",
                    "tab_field_name",
                    "background_image",
                    "tab_order",
                    "page"
                );

                foreach( $array as $key => $item ) {

                    $ignore =    ( $key != 'last_page_on' )
                              && ( $key != 'squace_rbackground_image' )
                              && ( $key != 'dbcg_download_pages' )
                              && ( $key != 'squace_rdbcg_download_pages' )
                              && ( $key != 'squace_rdbcg_download_pages_link_text' )
                              && ( $key != 'squace_rdbcg_download_pages_exclude_id' )
                              && ( $key != 'dbcg_download_posts' )
                              && ( $key != 'squace_rdbcg_download_posts' )
                              && ( $key != 'squace_rdbcg_download_posts_link_text' )
                              && ( $key != 'squace_rdbcg_download_posts_exclude_id' )
                              && ( $key != 'dbcg-download-links-box-posts' )
                              && ( $key != 'dbcg-download-links-box-pages' )
                    ;
                    
                    if( $this->check_ifin_string( $_in_string, $key ) && $ignore ) {

                        $_iPos = array( 
                            strpos( $key, $_in_string[0] ),    //tab_name_text
                            strpos( $key, $_in_string[1] ),    //cat
                            strpos( $key, $_in_string[2] ),    //squacetypecustomgenerate
                            strpos( $key, $_in_string[3] ),    //tag
                            strpos( $key, $_in_string[4] ),    //tab_field_name
                            strpos( $key, $_in_string[5] ),    //background_image
                            strpos( $key, $_in_string[6] ),    //tab_order
                            strpos( $key, $_in_string[7] )     //page
                        );

                        if( !( $_iPos[0] === false ) ) { //tab_name_text
                            $_position = 0;
                        } elseif ( !( $_iPos[1] === false ) ) { //cat
                            $_position = 1;
                        } elseif ( !( $_iPos[2] === false ) ) { //squacetypecustomgenerate
                            $_position = 2;
                        } elseif ( !( $_iPos[3] === false ) ) { //tag
                            $_position = 3;
                        } elseif ( !( $_iPos[4] === false ) ) { //tab_field_name
                            $_position = 4;
                        } elseif ( !( $_iPos[5] === false ) ) { //background_image
                            $_position = 5;
                        } elseif ( !( $_iPos[6] === false ) ) { //tab_order
                            $_position = 6;
                        } elseif ( !( $_iPos[7] === false ) ) { //page
                            $_position = 7;
                        }

                        $nkey = substr( $key, strlen( $_in_string[$_position] ) + 1 );
                        $tmp[$nkey][$_in_string[$_position]] = $item;
                        unset( $array[$key] );
                    }
                    
                }

                //regenerate array tabs as needed
                $_tmp = array();
                foreach( $tmp as $_item ) {
                    if( strlen( trim( $_item['tab_field_name'] ) ) > 0 ) {
                        $_tmp[] = $_item;
                    } else {
                       continue;
                    }
                }
                unset( $tmp, $_iPos, $_in_string, $item, $_item );
                $tmp = array();
                $tmp['fgct'] = $_tmp;
                foreach( $array as $key => $_item ) {
                    $tmp[$key] = $_item;
                }
                //clean out invalid entries, ex: added tabs with no data.
                //$tmp = $this->_purge_Invalid($array, $invalid, false, true);

                return $tmp;
                
            }

            return $array;
            
        }

        /**
         * Generates a javascript for handling upload and preview of uploaded picture
         * @param string $plugin_dir
         * @param string $plugin_dir_upload
         * @param string $filebox
         * @param string $update_box
         * @param string $preview_box
         * @param string $preview_url
         * @param boolean $have_preview
         * @param boolean $do_i_have_response
         * @return string
         */
        function get_JS_Upload( $plugin_dir, $plugin_dir_upload, $filebox, $update_box, $preview_box, $preview_url, $have_preview = true, $do_i_have_response = true, $unhide_preview = false, $eextra = null ) {

            //$preview_options("#squace-custom-tabs input[name*='tab_field_name']")
            $jsbox = str_replace( "-", "_", $filebox );

            if( $do_i_have_response ) {
                $_response = "/'+response+'";
            } else {
                $_response = "?squace_ricon='+escape( \${$jsbox}('#squace_ricon').val() )+'&squace_rbackground_image='+escape( \${$jsbox}('#squace_rbackground_image').val() )+'";
            }
            $_preview_url = "{$preview_url}{$_response}";

            $_preview = '';
            if( $have_preview ) {
                $_preview = "\${$jsbox}('#{$preview_box}').css( 'background-image', 'url( {$_preview_url} )');";
            }

            $_unhide = '';
            if( $unhide_preview ) {
                $_unhide = "\${$jsbox}('#{$preview_box}').show();";
            }
            
            return
            "
            <script type='text/javascript'>

                var \${$jsbox} = jQuery.noConflict();

                \${$jsbox}(document).ready(function(){

                    var $jsbox = \${$jsbox}('#{$filebox}'), interval;

                     new AjaxUpload($jsbox, {
                            action: '{$plugin_dir}/uploadify.php',
                            name: 'Filedata',
                            autoSubmit: true,
                            data: {
                                folder: '$plugin_dir_upload'
                            },
                            onSubmit : function(file, ext){
                                    {$jsbox}.text('Uploading');
                                    this.disable();
                                    interval = window.setInterval(function(){
                                            var text = {$jsbox}.text();
                                            if (text.length < 13){
                                                    {$jsbox}.text(text + '.');
                                            } else {
                                                    {$jsbox}.text('Uploading');
                                            }
                                    }, 200);
                            },
                            onComplete: function(file, response){
                                                    
                                {$jsbox}.text('Upload');
                                window.clearInterval(interval);
                                this.enable();

                                if( response == 'error' ) {
                                    alert( 'Picture not uploaded successfuly!');
                                } else {
                                    \${$jsbox}('#{$update_box}').text(response);
                                    \${$jsbox}('#{$update_box}').val(response);
                                }

                                {$eextra}

                                response = escape( response );

                                {$_preview}
                                {$_unhide}
                                
                            }
                    });


                });

            </script>
            ";
                            
        }

        /**
         * Checks if a value is in an array
         * @param array $array
         * @param mixed $value
         * @param mixed $return returns this if it exists in this array
         * @return boolean
         */
        function is_it_in_array( $array, $value, $return ) {

          $checked = "";
          if( is_array($array) && count($array) > 0 ) {
            $checked = in_array( $value, $array ) ? $return : "";
          }

          return $checked;
        }

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
         *
         * @param string $filename
         * @param <type> $contents
         * @param boolean $serialize
         * @param boolean $base64*
         * @return boolean
         */
        function settings_Write( $filename, $contents, $serialize = true, $base64 = true ) {

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
         * Filter the array by a index field with specific values
         * @param array $array
         * @param mixed $index
         * @param mixed $value
         * @return array
         */
        function filter_by_value ($array, $index, $value){

            $newarray = array();
            if(is_array($array) && count($array)>0) {

                foreach(array_keys($array) as $key){

                    $temp[$key] = $array[$key][$index];

                    if ($temp[$key] == $value){
                        $newarray[$key] = $array[$key];
                    }

                }

            }

            return $newarray;
        }

        /**
         * Create array from object returned from wordpress queries
         * @param object $obj
         * @return array
         */
        function build_array_from_object( $obj ) {

            $_tmp = array();

            foreach( $obj as $keyt => $item ) {
                $_tmp[ $keyt ] = $item;
            }

            return $_tmp;

        }

        /**
         * Create array from objects returned from wordpress queries
         * @param object $obj array of objects
         * @return array
         */
        function build_array_from_objects( $obj ) {

            $_tmp = array();
            
            foreach( $obj as $key => $category ) {

                foreach( $category as $keyt => $item ) {
                    $_tmp[ $key ][ $keyt ] = $item;
                }

            }

            return $_tmp;

        }

        /**
         * Builds recursivley an tree of arrays according to their parents and childs
         * @param array $array
         * @param mixed $filter
         * @param array $all
         * @param mixed $type
         * @param string $term_id field name
         * @return array
         */
        function build_subcategory_array( $array, $filter, $all, $type = 'parent', $term_id = 'term_id' ) {

            if( !( is_array($array) && count($array) > 0 )  ) return;
            if( !( is_array($all) && count($all) > 0 )  ) return;

            $_tmp = array();

            $_get_filtered = $this->filter_by_value( $array, $type, $filter );
            if( count( $_get_filtered ) > 0 ) {
                foreach( $_get_filtered as $key => $item ) {
                    $_tmp[$key] = $item;
                    $_tmp[$key]['_object'] = $this->build_subcategory_array(  $all, $item["$term_id"], $all, $type, $term_id );
                }
            }

            return $_tmp;

        }

        /**
         * Builds the category array
         * @param object $obj
         * @return array
         */
        function build_category_array( $obj ) {

            $_tmp_parent = array();
            $_tmp = array();

            $obj_tmp = $this->build_array_from_objects( $obj );

            $_tmp_parent = $this->filter_by_value( $obj_tmp, 'parent', 0 );
            foreach( $_tmp_parent as $key => $category ) {
               $_tmp[ $key ] = $category;
               $_tmp[ $key ]['_object'] = $this->build_subcategory_array( $obj_tmp, $category['term_id'], $obj_tmp );
            }

            return count($_tmp) > 0 ? $_tmp : null;

        }

        /**
         * Builds the pages in array
         * @param objects $obj
         * @return array
         */
        function build_pages_array( $obj ) {

            $_tmp_parent = array();
            $_tmp = array();

            $obj_tmp = $this->build_array_from_objects( $obj );

            $_tmp_parent = $this->filter_by_value( $obj_tmp, 'post_parent', 0 );
            foreach( $_tmp_parent as $key => $page ) {
               $_tmp[ $key ] = $page;
               $_tmp[ $key ]['_object'] = $this->build_subcategory_array( $obj_tmp, $page['ID'], $obj_tmp, 'post_parent', 'ID' );
            }

            return count($_tmp) > 0 ? $_tmp : null;

        }

        /**
         * Builds an array with checkboxes according to the parameters
         * @param string $id
         * @param string $name
         * @param array $array
         * @param array $data
         * @param integer $tabs
         * @param string $term_id
         * @param string $name_term
         * @param string $cb_type
         * @return array
         */
        function build_checkbox_Categories( $id, $name, $array, $data = array(), $tabs = 1, $term_id = 'term_id', $name_term = 'name', $cb_type = 'cat' ) {

            if( !( is_array($array) && count($array) > 0 )  ) return;

            $_tmp = array();

            $_tabs_array = array();
            $_tabs_array = array_fill( 1, $tabs, "&nbsp;&nbsp;" );
            $_tabs_start = implode( "", $_tabs_array );

            $field = "{$cb_type}_{$id}";

            $selected = " checked='checked' ";
            
            foreach( $array as $item ) {

                $_next = array();
                if( isset($item['_object']) && count( $item['_object'] ) > 0 ) {
                    $_next = $this->build_checkbox_Categories( $id, $name, $item['_object'], $data, ++$tabs, $term_id, $name_term, $cb_type );
                }

                $checked = $this->is_it_in_array( $data, $item[$term_id ], $selected );
                $_tmp[] = "$_tabs_start<INPUT name='{$cb_type}_{$name}[]' id='{$id}' $checked TYPE='CHECKBOX' VALUE='{$item[$term_id]}'>{$item[$name_term]}";

                if( count($_next) > 0 ) {
                    $_tmp[] = $_next;
                }

            }

            unset( $settings, $selected, $field );
            
            return $_tmp;

        }

        /**
         * Prepare an array recursivly for valid items and filtering
         * @param array $array
         * @return array
         */
        function prepare_checkbox_Categories( $array ) {

            if( !( is_array($array) && count($array) > 0 )  ) return;

            $_newarray = array();

            foreach( $array as $item ) {

                if( !(is_array($item) && count($item) > 0) ) {

                    $_newarray[] = $item;

                } else {

                    $_tmp = $this->prepare_checkbox_Categories( $item );

                    // run thru all to see if there are any more
                    foreach( $_tmp as $_chkbx ) {
                        $_newarray[] = $_chkbx;
                    }

                }

            }

            return $_newarray;

        }

    }
}

if (!class_exists("plugin_Squace")) {

    /**
     * Squace plugin class
     */
    class plugin_Squace extends squacetools {

        /** @var array */ protected $_settings = array();

        /**
         * Get the settings
         * @return array
         */
        function _get_settings() { return $this->_settings; }

        /**
         * Loads necesseary javascripts and load what you need for the plugin to work
         * @return void
         */
        function add_headerCode() {

            $base_file = plugins_url();           
            wp_enqueue_script("jquery-ui-core");

            $style =  plugins_url( 'css/wp-squace.css', __FILE__ );
            echo '<link type="text/css" rel="stylesheet" href="' . $style . '" />' . "\n";

            $style_ie =  plugins_url( 'css/ie.css', __FILE__ );
            echo "<!--[if gte IE 6]><link rel='stylesheet' type='text/css' href='{$style_ie}' /><![endif]-->";

            wp_register_script(
                 'wpsquace',
                 plugins_url( 'js/wp-squace.js', __FILE__ )
            );
            
            wp_register_script(
                 'uploadify',
                  plugins_url( 'uploadify/upload.js', __FILE__ )
            );

            wp_print_scripts(array('wpsquace', 'uploadify', 'jquery-ui-core', 'jquery-ui-sortable'));
            
        }

        /**
         * Get and print the Feed Generator TAB
         * @return string
         */
        function print_FeedGenerator_Page( ) {

            $settings = $this->_settings;

            if( !isset($settings["squacetype"]) ) {
                $settings["squacetype"] = 0;
            }

            $squacetype_state = $settings["squacetype"] == 2 ? "show" : "hide";
            
            $selected = " checked='checked' ";
            $state_selected = array(
                $settings["squacetype"] == 0 ? $selected : "",
                $settings["squacetype"] == 1 ? $selected : "",
                $settings["squacetype"] == 2 ? $selected : ""
            );

            $feedtitle = $settings['feedtitle'];
            if( !(isset($feedtitle) && strlen($feedtitle) > 0) ) $feedtitle = get_bloginfo("name");
            unset( $settings );

            $plugin_dir = plugins_url( '', __FILE__ );
            $plugin_dir_uploadify = plugins_url( 'uploadify/', __FILE__ );
            $plugin_dir_uploads_preview =  plugins_url( DEFAULT_IMAGES_PATH_UPLOADS, __FILE__ );
            
            $options =
            "
            <div>
                <h4>Enter a site title.</h4>
                <input type='text' name='feedtitle' id='feedtitle' value='{$feedtitle}' onblur='generate_HTML_button_DBCG(\"{$plugin_dir_uploads_preview}\");'>
                <h4>Select site version.</h4>
                <div id='select_fgtp'>
                    <INPUT TYPE='RADIO' NAME='squacetype' ID='squacetype' class='light' VALUE='0' {$state_selected[0]}>Light version (One tab: Home page)<BR>
                    <INPUT TYPE='RADIO' NAME='squacetype' ID='squacetype' class='complete' VALUE='1' {$state_selected[1]}>Complete version( Four tabs: Home page, By Category, All posts, All pages)<BR>
                    <INPUT TYPE='RADIO' NAME='squacetype' ID='squacetype' class='custom' VALUE='2' {$state_selected[2]}>Custom version (Add custom tabs)<BR>
                </div>
            </div>
            ";
            
            $js =
            "
            <script type='text/javascript'>
                var \$cpageswitch = jQuery.noConflict();

                \$cpageswitch(function(){

                    \$cpageswitch('#squace-custom-tabs').{$squacetype_state}();

                    \$cpageswitch('#squacetype.light').live('click', function(){
                        \$cpageswitch('#squace-custom-tabs').hide();
                        \$cpageswitch('#new_tab_save').hide();
                    });

                    \$cpageswitch('#squacetype.complete').live('click', function(){
                        \$cpageswitch('#squace-custom-tabs').hide();
                        \$cpageswitch('#new_tab_save').hide();
                    });

                    \$cpageswitch('#squacetype.custom').live('click', function(){
                        \$cpageswitch('#squace-custom-tabs').show();
                        \$cpageswitch('#new_tab_save').show();
                    });

                });
             </script>
            ";
                        
            $custom = $this->print_custom_Page();
            
            return $options . $custom . $js;
        }

        /**
         * Gets and creates a checkboxes for the tags and hierarchical tree
         * @param string $id
         * @param string $name
         * @param array $data
         * @return string
         */
        function print_get_Tags( $id, $name, $data = array() ) {

            $field = "tag_{$id}";
//            $settings = $this->_settings[ $field ];
            $selected = " checked='checked' ";
            
            $posttags = get_tags();
            $_tmp = array();
            if ($posttags) {
              foreach ($posttags as $tag) {
                 $_tmp[] = array( "name" => $tag->name, "id" => $tag->term_id );
              }
              $_tmp_tags = array();
              foreach( $_tmp as $key => $tag ) {
                  $checked = $this->is_it_in_array( $data, $tag['id'], $selected );
                  $_tmp_tags[] = "<INPUT name='tag_{$name}[]' id='{$id}' {$checked} TYPE='CHECKBOX' VALUE='{$tag['id']}'>{$tag['name']}";
              }
            }

            unset( $settings );
            
            $_ret = $posttags == false ? "No tags" :  implode("<br/>",$_tmp_tags);
            return "<div id = 'sqauce-ctags_{$id}'><p>Select from available tags:</p><span id='tag_cloud'>" . $_ret . "</span></div>";
            
        }

        /**
         * Gets and creates a checkboxes for the categories and hierarchical tree
         * @param string $id
         * @param string $name
         * @param array $data
         * @return string
         */
        function print_get_Categories( $id, $name, $data = array() ) {

            $args = array(
                'type' => 'post',
                'child_of' => 0,
                'hide_empty' => false,
                'hierarchical' => true
            );

            $field = "cat_{$id}";
            $selected = " checked='checked' ";
                
            $categories = $this->build_category_array( get_categories($args) );
            $parents = $categories;

            $_tmp = array(); $i=0;
            foreach( $parents as $next ) {
                $checked = $this->is_it_in_array( $data, $next['term_id'], $selected );
                $_tmp[] = "<INPUT name='cat_{$name}[]' id='{$id}' {$checked} TYPE='CHECKBOX' VALUE='{$next['term_id']}'>{$next['name']}";
                $_next = $this->build_checkbox_Categories( $id, $name, $next['_object'], $data );
                if( is_array( $_next) && count( $_next ) > 0 ) {
                    $_tmp[] = $_next;
                }
            }

            unset( $settings, $field, $checked );

            $_ret = ( isset($_tmp) && count($_tmp) > 0 ) ? implode("<br/>", $this->prepare_checkbox_Categories($_tmp) ) : "No categories";
            return "<div id = 'sqauce-ccats_{$id}'><h4>Select from available categories:</h4><span id='tag_category'>" . $_ret . "</span></div>";
            
        }

        /**
         * Gets and creates a checkboxes for the pages and hierarchical tree
         * @param string $id
         * @param string $name
         * @param array $data
         * @return string
         */
        function print_get_Pages( $id, $name, $data = array() ) {
            $args = array(
                'type' => 'post',
                'child_of' => 0,
                'hide_empty' => false,
                'hierarchical' => true
            );

            $field = "{$cb_type}_{$id}";
            $selected = " checked='checked' ";
                
            $pages = $this->build_pages_array( get_pages($args) );
            $parents = $pages;
            $cb_type = "page";
            
            $_tmp = array(); $i=0;
            foreach( $parents as $next ) {
                $checked = $this->is_it_in_array( $data, $next['ID'], $selected );
                $_tmp[] = "<INPUT name='{$cb_type}_{$name}[]' id='{$id}' {$checked} TYPE='CHECKBOX' VALUE='{$next['ID']}'>{$next['post_title']}";
                $_next = $this->build_checkbox_Categories( $id, $name, $next['_object'], $data, 1, "ID", "post_title", $cb_type );
                if( is_array( $_next) && count( $_next ) > 0 ) {
                    $_tmp[] = $_next;
                }
            }

            unset( $settings, $field, $checked );
//            return ( isset($_tmp) && count($_tmp)>0 ? "<div id = 'sqauce-ccats_{$id}'><h4>Select from available categories:</h4><span id='tag_category'>" . implode("<br/>", $this->prepare_checkbox_Categories($_tmp) ) . "</span></div>" :  false );
            $_ret = ( isset($_tmp) && count($_tmp) > 0 ) ? implode("<br/>", $this->prepare_checkbox_Categories($_tmp) ) : "No pages";
            return "<div id = 'sqauce-cpages_{$id}'><h4>Select from available pages:</h4><span id='tag_pages'>" . $_ret . "</span></div>";
        }

        /**
         * Create's a tab with all javascripts and interactions for the tab
         * @param string $id
         * @param string $name
         * @param array $data (optional)
         * @param boolean $optional (optional)
         * @param boolean $hide (optional)
         * @return string
         */
        function print_custom_Tab( $id, $name, $data = array(), $optional = false, $hide = false ) {

            $plugin_dir = plugins_url( '', __FILE__ );
            $plugin_dir_uploadify = plugins_url( 'uploadify/', __FILE__ );
            $plugin_dir_uploads_preview =  plugins_url( DEFAULT_IMAGES_PATH_UPLOADS, __FILE__ );

            $plugin_dir_upload = '';
            
            $_tmp_categories = $this->print_get_Categories( $id, $name, $data['cat'] );
            $_tmp_tags = $this->print_get_Tags( $id, $name, $data['tag'] );
            $_tmp_pages = $this->print_get_Pages( $id, $name, $data['page'] );

            $ctags_state = $data["squacetypecustomgenerate"] == 2 ? "show" : "hide";
            $ccats_state = $data["squacetypecustomgenerate"] == 1 ? "show" : "hide";
            $cpages_state = $data["squacetypecustomgenerate"] == 3 ? "show" : "hide";

            $selected = " checked='checked' ";
            $state_selected = array(
                 $data["squacetypecustomgenerate"] == 0 ? $selected : "",
                 $data["squacetypecustomgenerate"] == 1 ? $selected : "",
                 $data["squacetypecustomgenerate"] == 2 ? $selected : "",
                 $data["squacetypecustomgenerate"] == 3 ? $selected : ""
            );

            $tab_name_span = strlen($data["tab_field_name"]) > 0 ? $data['tab_field_name'] : "&nbsp;";
            $tab_name_field = strlen($data["tab_field_name"]) > 0 ? $data['tab_field_name'] : "";
            $background_image = $data['background_image'];

            if( ! (strlen( $background_image ) > 0) ) {
                $_hide_it = "\$tab_{$id}('#upload_preview_box_{$id}').hide();\n";
            }
            
            if( $hide ) {
                $_hide_it .= "\$tab_{$id}('#toggle_name_{$id}').click();";
            }
            
            $js =
            "
            <script type='text/javascript'>
            
                var \$tab_{$id} = jQuery.noConflict();

                \$tab_{$id}('#toggle_name_{$id}').die('click');
                \$tab_{$id}('#tab_name_text_{$id}').die('click');
                \$tab_{$id}('#tab_field_name_{$id}').unbind('keydown');
                \$tab_{$id}('#tab_field_name_{$id}').unbind('blur');
                \$tab_{$id}('#squacetypecustomgenerate_{$id}.news').die('click');
                \$tab_{$id}('#squacetypecustomgenerate_{$id}.category').die('click');
                \$tab_{$id}('#squacetypecustomgenerate_{$id}.tags').die('click');
                \$tab_{$id}('#squacetypecustomgenerate_{$id}.pages').die('click');

                \$tab_{$id}(function(){

                     \$tab_{$id}('#toggle_name_{$id}').live('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        if ( \$tab_{$id}('#data_$id').css('display') == 'none' ) {
                            \$tab_{$id}('#toggle_name_$id').html('<img src=\"" . plugins_url( 'images/minus.gif', __FILE__ ) . "\" />');
                        } else {
                            \$tab_{$id}('#toggle_name_$id').html('<img src=\"" . plugins_url( 'images/plus.gif', __FILE__ ) . "\" />');
                        }
                        event.preventDefault();
                        event.stopPropagation();
                        \$tab_{$id}('#data_$id').slideToggle('slow');
                        event.preventDefault();
                        event.stopPropagation();
                    });

                     \$tab_{$id}('#tab_name_text_{$id}').live('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        if ( \$tab_{$id}('#data_$id').css('display') == 'none' ) {
                            \$tab_{$id}('#toggle_name_$id').html('<img src=\"" . plugins_url( 'images/minus.gif', __FILE__ ) . "\" />');
                        } else {
                            \$tab_{$id}('#toggle_name_$id').html('<img src=\"" . plugins_url( 'images/plus.gif', __FILE__ ) . "\" />');
                        }
                        \$tab_{$id}('#data_$id').slideToggle('slow');
                        _tb_Coll( 'squace_sortable_tabs', false, 'tab_$id .data_block' );
                    });

                    \$tab_{$id}('#tab_field_name_{$name}').keydown( function(e) {
                        \$tab_{$id}('#tab_name_text_{$id}').text( \$tab_{$id}('#tab_field_name_{$name}').val() );
                    });

                    \$tab_{$id}('#tab_field_name_{$name}').blur( function(e) {
                        \$tab_{$id}('#tab_name_text_{$id}').text( \$tab_{$id}('#tab_field_name_{$name}').val() );
                    });

                
                    \$tab_{$id}('#sqauce-ctags_{$id}').$ctags_state();
                    \$tab_{$id}('#sqauce-ccats_{$id}').$ccats_state();
                    \$tab_{$id}('#sqauce-cpages_{$id}').$cpages_state();

                    \$tab_{$id}('#squacetypecustomgenerate_{$id}.news').live('click',function(){
                        \$tab_{$id}('#sqauce-ctags_{$id}').hide();
                        \$tab_{$id}('#sqauce-ccats_{$id}').hide();
                        \$tab_{$id}('#sqauce-cpages_{$id}').hide();
                    });

                    \$tab_{$id}('#squacetypecustomgenerate_{$id}.category').live('click',function(){
                        \$tab_{$id}('#sqauce-ctags_{$id}').hide();
                        \$tab_{$id}('#sqauce-ccats_{$id}').show();
                        \$tab_{$id}('#sqauce-cpages_{$id}').hide();
                    });

                    \$tab_{$id}('#squacetypecustomgenerate_{$id}.tags').live('click',function(){
                        \$tab_{$id}('#sqauce-ctags_{$id}').show();
                        \$tab_{$id}('#sqauce-ccats_{$id}').hide();
                        \$tab_{$id}('#sqauce-cpages_{$id}').hide();
                    });

                    \$tab_{$id}('#squacetypecustomgenerate_{$id}.pages').live('click',function(){
                        \$tab_{$id}('#sqauce-ctags_{$id}').hide();
                        \$tab_{$id}('#sqauce-ccats_{$id}').hide();
                        \$tab_{$id}('#sqauce-cpages_{$id}').show();
                    });

                    {$_hide_it}
                        
                });
             </script>
            ";

            $class = '';
            if( $optional != false ) {
                $class = $optional % 2 == 0 ? "even" : "odd";
            }

            $span_toggle = "<span id='toggle_name_$id' class='toggle_name'><img src='" . plugins_url( 'images/minus.gif', __FILE__ ) . "' /></span>";
            $tab_buttons = "
            <p>
              <input type='button' name='clear' onclick='javascript:clear_tab( \"$id\" )' value='Clear Tab' />
              <input type='button' name='delete' onclick='if( confirm(\"Are you sure you want to delete this tab?\") ) { delete_tab( \"tab_{$id}\" ); }' value='Delete Tab' />
            </p>
            ";

            $_preview_picture = "style='background-image: url({$plugin_dir}/uploads/{$background_image});'";
            $js_upload = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "upload_background_{$id}", "background_image_{$id}", "upload_preview_box_{$id}", $plugin_dir_uploads_preview, true, true, true );
            
            $data_id = "
              <div id='split_custom'>
                  <p>Enter tab name: <span><input name='tab_field_name_{$name}' id='tab_field_name_{$id}' value='{$tab_name_field}'></span></p>
                  <h4>Select a custom type.</h4>
                  <div id='select_cfgtp'>
                    <INPUT TYPE='RADIO' ID='squacetypecustomgenerate_{$id}' {$state_selected[0]} NAME='squacetypecustomgenerate_{$id}' class='news' VALUE='0'>Home News<BR>
                    <INPUT TYPE='RADIO' ID='squacetypecustomgenerate_{$id}' {$state_selected[1]} NAME='squacetypecustomgenerate_{$id}' class='category' VALUE='1'>Categories<BR>
                    <INPUT TYPE='RADIO' ID='squacetypecustomgenerate_{$id}' {$state_selected[2]} NAME='squacetypecustomgenerate_{$id}' class='tags' VALUE='2'>Tags<BR>
                    <INPUT TYPE='RADIO' ID='squacetypecustomgenerate_{$id}' {$state_selected[3]} NAME='squacetypecustomgenerate_{$id}' class='pages' VALUE='3'>Pages<BR>
                  </div> " .
                ( $_tmp_categories == false ? "" : $_tmp_categories ) .
                ( $_tmp_tags == false ? "" : $_tmp_tags ) .
                ( $_tmp_pages == false ? "" : $_tmp_pages ) .
            "</div>
             <div id='split_custom' class='last'>
                <p>Upload a background for this tab</p>
                <div name='upload_background_{$name}' id='upload_background_{$id}' class='button'>Upload</div>
                <div id='squace_background_clear_{$id}' class='squace_button_clear button overide_button' onclick='javascript:clear_image( \"background_image_{$id}\", \"upload_preview_box_{$id}\", \"\", true, true )'>Clear image</div>
                <input type='hidden' name='background_image_{$name}' id='background_image_{$id}' value='{$background_image}'>
                <span>{$js_upload}</span>
                <div id='separator'></div>
                <div name='upload_preview_box_{$name}' id='upload_preview_box_{$id}' class='custom_tabs_upload_preview_box' $_preview_picture></div>
             </div>
             <div id='separator'></div>
            ";
                        
            $div_data = "<div id='data_$id' class='data_block'>{$data_id}{$tab_buttons}</div>";
            $div_tab_name = "<span id='tab_name_text_{$id}' class='tab_name_text'>&nbsp;{$tab_name_span}</span>";
            $div_move_span = "<span><img src='" . plugins_url( 'images/arrow.png', __FILE__ ) . "' alt='move' width='16' height='16' class='handle' /></span>";
            
            $div_tab_id  = "<div id='tab_name_{$id}' class='info_name'>{$span_toggle}{$div_tab_name}{$div_move_span}</div>";
            $div_tab  = "<div id='tab_{$id}' class='info_block $class'>{$div_tab_id}{$div_data}</div>";

            return "<li>" . $div_tab . $js . "<div id='separator'></div></li>";

        }

        /**
         * Prints the custom tabs and it's contents
         * @return string
         */
        function print_custom_Page() {

            $return = array();
            $settings = $this->_settings;

            $total_fcgt = count($settings['fgct']);
            $i = 1;
            if( $total_fcgt > 0 ) {

                foreach( $settings['fgct'] as $key => $tab ) {
                    $hide = $total_fcgt == $i++ ? false : true;
                    $tab_uid = md5(uniqid(rand(), true));
                    $return[] = $this->print_custom_Tab( $tab_uid, $tab_uid, $tab, false, $hide );
                }
                
            } else {
                
                $tab_uid = md5(uniqid(rand(), true));
                $return[] = $this->print_custom_Tab( $tab_uid, $tab_uid, array(), false, false );
                
            }

            $return = "<ul id='squace_sortable_tabs'>" . implode( "\n", $return ) . "</ul>";
            $js = $this->build_JS_ajax( 'php/ajax.php' );

            $add_div = "<div id='squace-add-tab'><input type='button' onclick='_add_div( \"tab\", \"squace_sortable_tabs\")' class='button' value='Add new tab' /></div>
						<p>Add custom tabs. You can also change sort order by drag the tab.</p>";
            return "<div id='squace-custom-tabs'>
                        <span>{$js}</span>
                        {$add_div}
                        {$return}
                    </div>";

        }

        /**
         * Combines all data and prints the complete output for the administration of plugin
         * @return string
         */
        function print_AdminPage() {

            $js = ''; $active = ''; $active_class = array( "", "", "" ); $new_data = false;
            if( isset($_POST) && !empty($_POST) && isset( $_POST['last_page_on' ] ) ) {
                $active = $_POST['last_page_on'];
                $new_data = true;
            }

            $base_dir =  dirname(__FILE__);
            $cache_dir = str_replace( '//', '/', $base_dir . '/' . DEFAULT_CACHE_PATH );
            $uploads_dir = str_replace( '//', '/', $base_dir . '/' . DEFAULT_IMAGES_PATH_UPLOADS );
            $settings_dir = str_replace( '//', '/', $base_dir . '/' . SQUACE_SETTINGS_DIR );

            $alert_no_perms = '';

            $file_permissions = array(
                DEFAULT_CACHE_PATH => array(
                    "perms" => server_get_File_Permissions($cache_dir),
                    "octal" => sprintf( "%04o", server_get_File_Permissions_Num($cache_dir) ),
                    "requires" => "0777"
                ),
                DEFAULT_IMAGES_PATH_UPLOADS => array(
                    "perms" => server_get_File_Permissions($uploads_dir),
                    "octal" => sprintf( "%04o", server_get_File_Permissions_Num($uploads_dir) ),
                    "requires" => "0777"
                ),
                SQUACE_SETTINGS_DIR => array(
                    "perms" => server_get_File_Permissions($settings_dir),
                    "octal" => sprintf( "%04o", server_get_File_Permissions_Num($settings_dir) ),
                    "requires" => "0777"
                ),
            );

            if( $new_data ) { $alert = "<div class='updated fade below-h2 squace-update' id='message'><p>Your settings have been saved successfuly.</p></div>"; }

            $alert_no_perms = "<div class='updated fade below-h2 squace-update' id='message'>{ALERT_NO_PERMS}</div>";
            $_perm_problems = false;
            $_tmp_perms = array();
            foreach( $file_permissions as $key => $file ) {

                if( $file['octal'] != $file['requires'] ) {
                    $_perm_problems = true;
                    $_tmp_perms[] = "<p>Folder <strong>{$key}</strong> has <strong>{$file['octal']} [{$file['perms']}]</strong> permissions but it requires <strong>0777</strong>.</p>";
                }
                
            }

            if ( !(extension_loaded('gd') && function_exists('gd_info')) ) {
                $_perm_problems = true;
                $_tmp_perms[] = "<p><strong>GD Library</strong> not detected please install or enable the library.</p>";
            }

            if( $_perm_problems ) {
                $alert_no_perms = str_replace( "{ALERT_NO_PERMS}", implode( "\n", $_tmp_perms ), $alert_no_perms );
            } else {
                $alert_no_perms = '';
            }

            $squacetype = isset( $this->_settings['squacetype'] ) && $this->_settings['squacetype'] >= 0 ? $this->_settings['squacetype'] : 0;

            $active = !( strlen($active) > 0 ) ? "#squace-feed-generator" : $active;

            $new_tab_add_action = ($squacetype == 2) && ($active == "#squace-feed-generator") ? "show()" : "hide()";

            $js = "
                    <script type='text/javascript'>
                        \$jt = jQuery.noConflict();
                        
                        var lpoid = \$jt('#last_page_on');
                        \$jt('#a-squace-feed-generator').live( 'click', function() {
                            var \$jtmp = jQuery.noConflict();
                            if( \$jtmp('#squacetype:checked').val() == '2' ) {
                                \$jtmp('#new_tab_save').show();
                            } else {
                                \$jtmp('#new_tab_save').hide();
                            }
                            lpoid.val( \$jtmp('#a-squace-feed-generator').attr('href') );
                        });
                        \$jt('#a-squace-settings').click(function(){ \$jt('#new_tab_save').hide(); lpoid.val( \$jt('#a-squace-settings').attr('href') ); });
                        \$jt('#a-squace-dbcg').click(function(){ \$jt('#new_tab_save').hide(); lpoid.val( \$jt('#a-squace-dbcg').attr('href') ); });
                        \$jt('#new_tab_save').{$new_tab_add_action};

                        \$jt(document).ready(function() {
                          \$jt('#squace_sortable_tabs').sortable({
                            handle: '.handle',
                            appendTo: '#squace_sortable_tabs',
                            items: 'li',
                            start: function(event, ui) {
                                event.stopPropagation();
                                _tb_Coll('squace_sortable_tabs', true);
                            },
                            update: function( event, ui ) {
                                event.stopPropagation();
                                _tb_Coll('squace_sortable_tabs', true);
                            }
                          });
                          \$jt('#squace_sortable_tabs').disableSelection();
                        });
                        
                    </script>";

            $full_url = esc_url(str_replace( '%7E', '~', $_SERVER['REQUEST_URI']));
            return "
            {$alert_no_perms}
            {$alert}
            <form name='squace_form_data' id='squace_form_data' method='post' action='{$full_url}' enctype='multipart/form-data'>
                <input type='hidden' name='is_there_new_data' value='Y'>
                <input type='hidden' name='last_page_on' id='last_page_on' value='$active'>
                <div class='domtab'>
                  <ul class='domtabs'>
                    <li><a id='a-squace-feed-generator' href='#squace-feed-generator'>Manage Site</a></li>
                    <li><a id='a-squace-settings' href='#squace-settings'>Settings</a></li>
                    <li><a id='a-squace-dbcg' href='#squace-dbcg'>Download button</a></li>
                  </ul>
                  <div>
                    <h2><a name='squace-feed-generator' id='squace-feed-generator'></a></h2>
                    {$this->print_FeedGenerator_Page()}
                  </div>
                  <div style='display:none;'>
                    <h2><a name='squace-settings' id='squace-settings'></a></h2>
                    {$this->print_Settings_Page()}
                  </div>
                  <div style='display:none;'>
                    <h2><a name='squace-dbcg' id='squace-dbcg'></a></h2>
                    {$this->print_DBCG_Page()}
                  </div>
                  <div id='separator'></div>
                  <div id='squace_save'>
                    <p>
                      <input type='button' name='Submit' onclick='squace_submit_Save( \"squace_form_data\",\"{$full_url}\" );' value='Save' />
                      <input type='button' id='new_tab_save' name='new_tab_save' onclick='_add_div( \"tab\", \"squace_sortable_tabs\")' class='button' value='Add new tab' />
                    </p>
                  </div>
                </div>
                $js
             </form>
                ";
            
        }

        /**
         * Prints Code generation Tab
         * @return string
         */
        function print_DBCG_Page() {

            $selected = " checked='checked' ";
            
            $squace_genhtml = isset($this->_settings['squace_genhtml']) ? stripcslashes( $this->_settings['squace_genhtml'] ): '';
            $squace_genhtml_copypaste = isset($this->_settings['squace_genhtml_copypaste']) ? stripcslashes( $this->_settings['squace_genhtml_copypaste'] ): '';

            $squace_rdbcg_download_posts_link_text = isset($this->_settings['squace_rdbcg_download_posts_link_text']) ? ( $this->_settings['squace_rdbcg_download_posts_link_text'] ): '';
            $squace_rdbcg_download_posts_exclude_id = isset($this->_settings['squace_rdbcg_download_posts_exclude_id']) ? ( $this->_settings['squace_rdbcg_download_posts_exclude_id'] ): '';
            $dbcg_download_posts = isset($this->_settings['dbcg_download_posts']) && $this->_settings['dbcg_download_posts'] == 'on' ? $selected : '';
            
            $squace_rdbcg_download_pages_link_text = isset($this->_settings['squace_rdbcg_download_pages_link_text']) ? ( $this->_settings['squace_rdbcg_download_pages_link_text'] ): '';
            $squace_rdbcg_download_pages_exclude_id = isset($this->_settings['squace_rdbcg_download_pages_exclude_id']) ? ( $this->_settings['squace_rdbcg_download_pages_exclude_id'] ): '';
            $dbcg_download_pages = isset($this->_settings['dbcg_download_pages']) && $this->_settings['dbcg_download_pages'] == 'on' ? $selected : '';

            $plugin_dir = plugins_url( '', __FILE__ );
            $plugin_dir_uploadify = plugins_url( 'uploadify/', __FILE__ );
            $plugin_dir_uploads_preview =  plugins_url( DEFAULT_IMAGES_PATH_UPLOADS, __FILE__ );

            $squace_rpicture = strlen($this->_settings['squace_rpicture']) > 0 ? $this->_settings['squace_rpicture'] : '';
            $squace_rdbcg_download_posts = strlen($this->_settings['squace_rdbcg_download_posts']) > 0 ? $this->_settings['squace_rdbcg_download_posts'] : '';
            $squace_rdbcg_download_pages = strlen($this->_settings['squace_rdbcg_download_pages']) > 0 ? $this->_settings['squace_rdbcg_download_pages'] : '';

            $_opt_selected = " SELECTED='selected' ";
            $dbcg_download_links_box_posts = array( "", "", "" );
            if(  isset( $this->_settings['dbcg-download-links-box-posts'] ) ) {
                $dbcg_download_links_box_posts = array(
                    $this->_settings['dbcg-download-links-box-posts'] == 0 ? $_opt_selected : "",
                    $this->_settings['dbcg-download-links-box-posts'] == 1 ? $_opt_selected : "",
                    $this->_settings['dbcg-download-links-box-posts'] == 2 ? $_opt_selected : ""
                );
            }
            $dbcg_download_links_box_pages = array( "", "", "" );
            if(  isset( $this->_settings['dbcg-download-links-box-pages'] ) ) {
                $dbcg_download_links_box_pages = array(
                    $this->_settings['dbcg-download-links-box-pages'] == 0 ? $_opt_selected : "",
                    $this->_settings['dbcg-download-links-box-pages'] == 1 ? $_opt_selected : "",
                    $this->_settings['dbcg-download-links-box-pages'] == 2 ? $_opt_selected : ""
                );
            }
            
            $squace_rdpicture = strlen($squace_rpicture) > 0 ? '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $squace_rpicture : DEFAULT_MOCKUP_DOWNLOAD_BUTTON;
            $dbcg_download_posts_picture_preview = strlen($squace_rdbcg_download_posts) > 0 ? '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $squace_rdbcg_download_posts : DEFAULT_MOCKUP_DOWNLOAD_BUTTON_POSTS;
            $dbcg_download_pages_picture_preview = strlen($squace_rdbcg_download_pages) > 0 ? '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $squace_rdbcg_download_pages : DEFAULT_MOCKUP_DOWNLOAD_BUTTON_PAGES;
            
            $plugin_dir_upload = '';

            $eextra = "generate_HTML_button_DBCG('{$plugin_dir_uploads_preview}');";
            $js[0] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "squace_picture", "squace_rpicture", "squace_picture_preview", $plugin_dir_uploads_preview, true, true, false, $eextra);
//            $js[0] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "squace_picture", "squace_rpicture", "squace_picture_preview", $plugin_dir_uploads_preview );
            $js[1] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "dbcg_download_posts_picture", "squace_rdbcg_download_posts", "dbcg_download_posts_picture_preview", $plugin_dir_uploads_preview );
            $js[2] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "dbcg_download_pages_picture", "squace_rdbcg_download_pages", "dbcg_download_pages_picture_preview", $plugin_dir_uploads_preview );

            $dbcg_sitetitle = isset( $this->_settings['feedtitle'] ) ? urlencode( $this->_settings['feedtitle'] ) : urlencode( get_bloginfo("name") );

            $dbcg_downloadimage = ( $plugin_dir . DEFAULT_MOCKUP_DOWNLOAD_BUTTON );
            $dbcg_download_posts_downloadimage = ( $plugin_dir . DEFAULT_MOCKUP_DOWNLOAD_BUTTON_POSTS );
            $dbcg_download_pages_downloadimage = ( $plugin_dir . DEFAULT_MOCKUP_DOWNLOAD_BUTTON_PAGES );
            
            $dbcg_siteurl= ( $plugin_dir . "/php/squaceml.php" );

            $tags = get_tags();
            $_tags = '';
            if( $tags ) {
                $_tags = array();
                foreach( $tags as $tag ) { $_tags[] = $tag->slug; }
                if( count($_tags) > 0 ) {
                    $_tags = array_slice( $_tags, 0, SQUACE_MAX_URL_TAGS );
                    $_tags = urlencode( implode( SQUACE_URL_TAGS_SEPARATOR, $_tags ) );
                }
            }
            $dbcg_sitetags = $_tags;

            $_gen_html_code_cp = "<a\n\tonclick=\"window.open('http://www.squace.com/content.browse.action?addBookmark={$dbcg_siteurl}?action=screen&title={$dbcg_sitetitle}&tags={$dbcg_sitetags}','landingPagePopup','width=480,height=720,scrollbars=yes,toolbar=no,status=no,menubar=no,left=300,top=200,screenX=300,screenY=200')\" \n\thref=\"#\">\n\t\t<img src=\"{$dbcg_downloadimage}\" alt=\"Download {$dbcg_sitetitle}\" />\n</a>";
            $_gen_html_code = "<a \n\tonclick=\"window.open('http://www.squace.com/content.browse.action?addBookmark={$dbcg_siteurl}<siteOptions>&title=<siteTitle>&tags=<siteTags>','landingPagePopup','width=480,height=720,scrollbars=yes,toolbar=no,status=no,menubar=no,left=300,top=200,screenX=300,screenY=200')\" \n\thref=\"#\">\n\t\t<img src=\"<siteImage>\" alt=\"Download <siteTitle>\" />\n</a>";

            if( !(strlen($squace_genhtml) > 0) ) { $squace_genhtml = $_gen_html_code; }
            if( !(strlen($squace_genhtml_copypaste) > 0) ) { $squace_genhtml_copypaste = $_gen_html_code_cp; }
            
            return
            "
                <div>

                    <div id='squace-dbcg-upload-button'>
                        <h4>Create a download button<br/><small>(Use Squace widget to place the download button on your site.)</small></h4>
                        <p>Please upload a picture</p>
                        <div id='squace_picture' class='button'>Upload</div>
                        <div id='squace_picture_clear' class='button' onclick='javascript:clear_image( \"squace_rpicture\", \"squace_picture_preview\", \"$dbcg_downloadimage\", true ); generate_HTML_button_DBCG(\"{$plugin_dir_uploads_preview}\");'>Clear image</div>
                        <input type='hidden' id='squace_rpicture' name='squace_rpicture' value='{$squace_rpicture}' />
                        <div id='squace_picture_preview' style='background-image: url({$plugin_dir}{$squace_rdpicture});'></div>
                    </div>
                    <br/>
                    <div id='squace-dbcg-html'>
                        <h4<small>If you can't use widgets or if you want to place the download button on a different place use this code.<br/><br/></small></h4>
                        <input type='hidden' name='dbcg_sitetitle' id='dbcg_sitetitle' value={$dbcg_sitetitle} />
                        <input type='hidden' name='dbcg_downloadimage' id='dbcg_downloadimage' value={$dbcg_downloadimage} />
                        <input type='hidden' name='dbcg_siteurl' id='dbcg_siteurl' value={$dbcg_siteurl} />
                        <input type='hidden' name='dbcg_sitetags' id='dbcg_sitetags' value={$dbcg_sitetags} />
                        <div class='clear'></div>
                        <div id='squace-dbcg-genhtml'>
                            <div style='display:none'>
                                <input type='button' class='button' value = 'Generate HTML code' onclick='javascript:generate_HTML_button_DBCG(\"{$plugin_dir_uploads_preview}\");' />
                                <p> Default generated HTML code: (Posts, Pages, and Widget)<br/> </p>
                                <textarea name='squace_genhtml' onkeypress='sync_fields(\"squace_genhtml\", \"squace_genhtml_copypaste\")' onblur='sync_fields(\"squace_genhtml\", \"squace_genhtml_copypaste\")' id='squace_genhtml'>{$squace_genhtml}</textarea>
                                <p>
                                    <strong>&lt;siteOptions&gt;</strong> - Replaces it options that are required for the script to run.<br/>
                                    <strong>&lt;siteTitle&gt;</strong> - Replaces it with site feed title from manage site tab.<br/>
                                    <strong>&lt;siteTags&gt;</strong> - Replaces it with all of site tags (MAX: " . SQUACE_MAX_URL_TAGS . " tags).<br/>
                                    <strong>&lt;siteImage&gt;</strong> - Replaces it with site image for download button depending on the type.<br/><br/>
                                    <strong>Note:</strong> To be able to use text links in pages or posts make sure you have an <strong>img</strong> tag because that tag is swaped with the link text, for example this part <strong><em>\"" . htmlentities("<img src='<siteImage>' alt='Download <siteTitle>' />") . "\"</em></strong> will be replaced by the text you want for the link.
                                </p>
                                <p>Copy/Paste HTML code: (If you want to paste the code somewhere on the page use this, auto updates on change from the default generated html code)</p>
                            </div>
                            <textarea readonly name='squace_genhtml_copypaste' id='squace_genhtml_copypaste'>{$squace_genhtml_copypaste}</textarea>
                        </div>
                    </div>
                    {$js[0]}
                    <div id='separator'></div>
                    <div id='squace-dbcg-download-links'>
                    
                        <h4>Page and post specific download buttons</h4>
                    
                        <div id='squace-dbcg-download-links-box'>
                            <input type='checkbox' $dbcg_download_posts id='dbcg_download_posts' name='dbcg_download_posts'>Add download button on posts
                                <select id='dbcg-download-links-box-posts' name='dbcg-download-links-box-posts'>
                                    <option value='0' {$dbcg_download_links_box_posts[0]}>Use only link</option>
                                    <option value='1' {$dbcg_download_links_box_posts[1]}>Use only image</option>
                                    <option value='2' {$dbcg_download_links_box_posts[2]}>Use both link and image</option>
                                </select>
                            <div>
                                <div id='dbcg_download_posts_picture' class='button'>Upload</div>
                                <div id='dbcg_download_posts_picture_clear' class='button' onclick='javascript:clear_image( \"squace_rdbcg_download_posts\", \"dbcg_download_posts_picture_preview\", \"$dbcg_download_posts_downloadimage\", false )'>Clear image</div>
                            </div>
                            <input type='hidden' id='squace_rdbcg_download_posts' name='squace_rdbcg_download_posts' value='{$squace_rdbcg_download_posts}' />
                            <div id='dbcg_download_posts_picture_preview' style='background-image: url({$plugin_dir}{$dbcg_download_posts_picture_preview});'></div>
                            <div>
                                <p>Enter link title: <input type='text' id='squace_rdbcg_download_posts_link_text' name='squace_rdbcg_download_posts_link_text' value='{$squace_rdbcg_download_posts_link_text}'></p>
                                <p>Exclude posts ( separate post ID's with a comma [,] ): <br/> <input type='text' id='squace_rdbcg_download_posts_exclude_id' name='squace_rdbcg_download_posts_exclude_id' value='{$squace_rdbcg_download_posts_exclude_id}'></p>
                            </div>
                            {$js[1]}
                        </div>

                        <div id='squace-dbcg-download-links-box'>
                            <input type='checkbox' $dbcg_download_pages id='dbcg_download_pages' name='dbcg_download_pages'>Add download buttonoptions on pages
                                <select id='dbcg-download-links-box-pages' name='dbcg-download-links-box-pages'>
                                    <option value='0' {$dbcg_download_links_box_pages[0]}>Use only link</option>
                                    <option value='1' {$dbcg_download_links_box_pages[1]}>Use only image</option>
                                    <option value='2' {$dbcg_download_links_box_pages[2]}>Use both link and image</option>
                                </select>
                            <div>
                                <div id='dbcg_download_pages_picture' class='button'>Upload</div>
                                <div id='dbcg_download_pages_picture_clear' class='button' onclick='javascript:clear_image( \"squace_rdbcg_download_pages\", \"dbcg_download_pages_picture_preview\", \"$dbcg_download_pages_downloadimage\", false )'>Clear image</div>
                            </div>
                            <input type='hidden' id='squace_rdbcg_download_pages' name='squace_rdbcg_download_pages' value='{$squace_rdbcg_download_pages}' />
                            <div id='dbcg_download_pages_picture_preview' style='background-image: url({$plugin_dir}{$dbcg_download_pages_picture_preview});'></div>
                            <div>
                                <p>Enter link title: <input type='text' id='squace_rdbcg_download_pages_link_text' name='squace_rdbcg_download_pages_link_text' value='{$squace_rdbcg_download_pages_link_text}'></p>
                                <p>Exclude pages ( separate page ID's with a comma [,] ): <br/> <input type='text' id='squace_rdbcg_download_pages_exclude_id' name='squace_rdbcg_download_pages_exclude_id' value='{$squace_rdbcg_download_pages_exclude_id}'></p>
                            </div>
                            {$js[2]}
                        </div>

                    </div>

                </div>
                <div id='separator'></div>

            ";

        }

        /**
         * Load/Save settings and prepare the array for storage
         * @param array &$array
         * @param string $filename
         * @param integer $action (SQUACE_LOAD_SETTINGS, SQUACE_SAVE_SETTINGS)
         * @return boolean
         */
        function settings( &$array, $filename, $action = SQUACE_LOAD_SETTINGS ) {

            switch( $action ) {
                case SQUACE_LOAD_SETTINGS:
                    $array = $this->regenerate_Settings( $this->settings_Read( $filename ), SQUACE_LOAD_SETTINGS );
                    break;
                case SQUACE_SAVE_SETTINGS:
                    $this->settings_Write( $filename, $this->regenerate_Settings( $_POST, SQUACE_SAVE_SETTINGS ) );
                    break;
                default:
                    $array = array();
                    return false;
                    break;
            }

            return true;
            
        }

        /**
         * Activates the plugin
         * @return void
         */
        function activate() {

            $filename = str_replace('//', '/', dirname(__FILE__) . '/' . SQUACE_SETTINGS_FILE );

            if( file_exists( $filename . ".deactivated" ) ) {
                @rename( $filename . ".deactivated", $filename );
            }

            $base_dir =  dirname(__FILE__);
            $cache_dir = str_replace( '//', '/', $base_dir . '/' . DEFAULT_CACHE_PATH );
            $uploads_dir = str_replace( '//', '/', $base_dir . '/' . DEFAULT_IMAGES_PATH_UPLOADS );
            $settings_dir = str_replace( '//', '/', $base_dir . '/' . SQUACE_SETTINGS_DIR );
            
        }

        /**
         * Deactivates this plugin
         * @return void
         */
        function deactivate() {

            $filename = str_replace('//', '/', dirname(__FILE__) . '/' . SQUACE_SETTINGS_FILE );

            if( file_exists($filename) ) {
                @rename( $filename, $filename . ".deactivated" );
            }
            
        }

        /**
         * Loads plugin, takes care of saving and loading of the data
         * @return void
         */
        function load_plugin() {

            $b_LoadSettings = true;
            $filename = str_replace('\\', '/', dirname(__FILE__)) . '/' . SQUACE_SETTINGS_FILE;
            
            /**
             * Do i need to save settings before proceding, did i have a post?
             */
            if( !( empty($_POST) && isset($_POST) ) ) {
                
              if( $_POST[ 'is_there_new_data' ] == 'Y' ) {
                  $this->settings( $this->_settings, $filename, SQUACE_SAVE_SETTINGS );
              }
              
            }
            

            if( $b_LoadSettings ) {
                /**
                 * Check if config exists already and load it
                 */
                if( file_exists( $filename ) ) {
                    $b_Load = $this->settings( $this->_settings, $filename, SQUACE_LOAD_SETTINGS );
                    if( !$b_Load ) {
                        // error, why? shouldn't happen, maybe no read permisions
                    }
                }
            }

            $this->clean_directories();
            
            echo $this->print_AdminPage();
            
        }

        /**
         * Build a dropdown menu from the available tabs in settings file
         * @param array $data
         * @return string
         */
        function build_image_options( $data ) {

            if( is_array($data) && count($data) > 0 ) {

                $_tmp = array();
                foreach( $data as $image ) {
                    if( isset( $image['background_image'] ) ) {
                        $_tmp[] = "<option value='{$image['background_image']}'>{$image['tab_field_name']}</option>";
                    }
                }
                $_tmp = implode( "\n", $_tmp );
                
            } else {

                $_tmp = '';
                
            }
            
            return "<select id='_preview_options'>{$_tmp}</select>";
            
        }

        /**
         * Prints the settings tab
         * @return string
         */
        function print_Settings_Page() {

            $plugin_dir = plugins_url( '', __FILE__ );
            $plugin_dir_uploadify = plugins_url( 'uploadify/', __FILE__ );
            $plugin_dir_uploads_preview =  plugins_url( DEFAULT_IMAGES_PATH_UPLOADS, __FILE__ );

            $plugin_dir_upload = '';
            
            $squace_ricon = $this->_settings['squace_ricon'];
            $squace_rbackground_image = $this->_settings['squace_rbackground_image'];
            $squace_caching_allow = $this->_settings['squace_caching_allow'];
            $squace_default_gsize = $this->_settings['squace_default_gsize'];
            
            $js['squace_background_image_preview'] = $this->get_JS_preview('squace_background_image_preview', 'preview_box', "{$plugin_dir}/php/mockup.php", 'squace_rbackground_image' );

            $js['_preview_options'] = $this->get_JS_preview('preview_options', 'preview_box', "{$plugin_dir}/php/mockup.php", '_preview_options' );
            $_preview_options = $this->build_image_options( $this->_settings['fgct'] );
            $preview_box = '';
            $js['squace_background_image'] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, "squace_background_image", "squace_rbackground_image", "preview_box", "{$plugin_dir}/php/mockup.php", true, false );
            $js['squace_icon'] = $this->get_JS_Upload( $plugin_dir, $plugin_dir_upload, 'squace_icon', 'squace_ricon', 'squace_icon_preview', $plugin_dir_uploads_preview );
            $_preview_squace_rdeafult_icon = '';
            
            $default_icon = $plugin_dir . DEFAULT_MOCKUP_ICON;
            $default_mockup = $plugin_dir . DEFAULT_MOCKUP . "?squace_ricon=&squace_rbackground_image=";
            
            if( strlen($squace_ricon) > 0 ) {
                $url_ricon = ( $plugin_dir . '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . urlencode($squace_ricon) );
                $_preview_squace_rdeafult_icon = " style='background-image: url( {$url_ricon} );' ";
            } else {
                $_preview_squace_rdeafult_icon = " style='background-image: url( {$default_icon} );' ";
            }
            
            $_preview_squace_box = "";
            $cache = function_exists('wp_cache_postload');

            $url_purge_cache = plugins_url( 'php/ajax.php', __FILE__ );
            return
            "
                <div id='settings_page'>
                    <div id='squace_custom_image'>
                        <div id='squace-container'>
                            <p>Select default background image (jpg, png, gif)(240x257): </p>
                            <div id='squace_background_image' class='button'>Upload</div>
                            <div id='squace_background_image_clear' class='button' onclick='javascript:clear_image( \"squace_rbackground_image\", \"squace_custom_preview_box #preview_box\", \"$default_mockup\" )'>Clear image</div>
                            <div id='squace_background_image_preview' class='button_preview'>&nbsp;</div>
                            <input type='hidden' name='squace_rbackground_image' id='squace_rbackground_image' value='{$squace_rbackground_image}'>
                            {$js['squace_background_image']}
                            {$js['squace_background_image_preview']}
                            <div id='separator'></div>
                        </div>
                        <div id='squace-container'>
                            <p>Select icon (jpg, png, gif)(16x16):</p>
                            <div id='squace_icon' class='button'>Upload</div>
                            <div id='squace_icon_preview' {$_preview_squace_rdeafult_icon} class='preview_box'></div>
                            <div id='squace_icon_clear' class='button' onclick='javascript:clear_image( \"squace_ricon\", \"squace_icon_preview\", \"$default_icon\" )'>Clear image</div>
                            <input type='hidden' name='squace_ricon' id='squace_ricon' value='{$squace_ricon}'>
                            {$js['squace_icon']}
                            <div id='separator'></div>
                        </div>
                    </div>
                            
                <div id='squace_custom_preview_box'>
                    <div id='preview_box' {$_preview_squace_box}></div>
                </div>

                <div id='squace_custom_image' class='right_box'>
                    
                    <div id='squace-container'>
                        <p>Preview tabs background image: </p>
                        {$_preview_options}
                        <div id='preview_options' class='button_preview preview_options'>&nbsp;</div>
                        {$js['_preview_options']}
                        <div id='separator'></div>
                    </div>
                        
                </div>

                <div id='separator'></div>
                            
                <div id='squace-caching'>
                    <h3>Caching system</h3>
                    " . ( $cache ? "<p>An existing caching plugin detected. (wp-cache)</p>" : "") . "
                    <input type='checkbox' " . ( $cache ? " disabled " : "" ). " name='squace_caching_allow' id='squace_caching_allow' " . ( $squace_caching_allow == 'on' ? " checked='{$squace_caching_allow}' " : "" ) . " /> Allow caching
                    <br/><br/>
                    <input onclick='squace_purge_Cache( \"{$url_purge_cache}\" );' type='button' " . ( $cache ? " disabled " : "" ). " name='squace_caching_purge' id='squace_caching_purge' value='Purge cache'>

                </div>

             </div>
            ";
        }
        
    }
    
}

/**
 * Generate the code for bookmarks for posts and pages
 * @global object $dl_pluginSquace
 * @global object $post
 * @param string $content
 * @return string
 */
function generate_bookmark_posts_pages( $content ) {

    global $dl_pluginSquace;

    $filename = str_replace('\\', '/', dirname(__FILE__)) . '/' . SQUACE_SETTINGS_FILE;
    if( file_exists( $filename ) ) {
        $b_Load = $dl_pluginSquace->settings( $settings, $filename, SQUACE_LOAD_SETTINGS );
        if( !$b_Load ) {
            // error, why? shouldn't happen, maybe no read permisions
        }
    }

    $hurl = stripslashes( $settings['squace_genhtml'] );
    $post_link_text = $settings['squace_rdbcg_download_posts_link_text'];
    $page_link_text = $settings['squace_rdbcg_download_pages_link_text'];

    $b_bookmark_posts = $settings['dbcg_download_posts'] == 'on';
    $b_bookmark_pages = $settings['dbcg_download_pages'] == 'on';

    $post_exclude = array(); $page_exclude = array();
    $post_exclude = strlen( trim( $settings['squace_rdbcg_download_posts_exclude_id'] ) ) > 0 ? explode( ",", $settings['squace_rdbcg_download_posts_exclude_id'] ) : array();
    $page_exclude = strlen( trim( $settings['squace_rdbcg_download_pages_exclude_id'] ) ) > 0 ? explode( ",", $settings['squace_rdbcg_download_pages_exclude_id'] ) : array();

    global $post;
    $plugin_url = plugins_url( '', __FILE__ );
    $base_generator = plugins_url( 'php/squaceml.php', __FILE__ );

    $squace_rdbcg_download_posts = DEFAULT_MOCKUP_DOWNLOAD_BUTTON_POSTS;
    if(  isset( $settings['squace_rdbcg_download_posts'] ) && (strlen( $settings['squace_rdbcg_download_posts'] ) > 0 )  ) {
       $squace_rdbcg_download_posts =  '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $settings['squace_rdbcg_download_posts'];
    }

    $squace_rdbcg_download_pages = DEFAULT_MOCKUP_DOWNLOAD_BUTTON_PAGES;
    if(  isset( $settings['squace_rdbcg_download_pages'] ) && (strlen( $settings['squace_rdbcg_download_pages'] ) > 0 )  ) {
       $squace_rdbcg_download_pages =  '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $settings['squace_rdbcg_download_pages'];
    }

    $squace_rdbcg_download_posts = $plugin_url . $squace_rdbcg_download_posts;
    $squace_rdbcg_download_pages = $plugin_url . $squace_rdbcg_download_pages;

    $dbcg_download_links_box_pages = $settings['dbcg-download-links-box-pages'];
    $dbcg_download_links_box_posts = $settings['dbcg-download-links-box-posts'];

    $hurl_image = '';
    $hurl_link = '';

    if( strlen($hurl) > 0 ) {

        if( $b_bookmark_pages && is_page() && !in_array( $post->ID, $page_exclude )) {

            $hurl = str_replace( "<siteImage>", $squace_rdbcg_download_pages, $hurl );
            $href = ( "?action=infobox&pg={$post->ID}" );

            $siteOptions = urlencode( $href );
            $hurl = str_replace( "<siteOptions>", $siteOptions, $hurl );

            $siteTitle = urlencode( $settings['feedtitle'] . ' - ' . $post->post_title  );
            $hurl = str_replace( "<siteTitle>", $siteTitle, $hurl );

            $tags = get_tags();
            $_tags = '';
            if( $tags ) {
                $_tags = array();
                foreach( $tags as $tag ) { $_tags[] = $tag->slug; }
                if( count($_tags) > 0 ) {
                    $_tags = array_slice( $_tags, 0, SQUACE_MAX_URL_TAGS );
                    $_tags = urlencode( implode( SQUACE_URL_TAGS_SEPARATOR, $_tags ) );
                }
            }
            $hurl = str_replace( "<siteTags>", $_tags, $hurl );

            if( $dbcg_download_links_box_pages == 1 || $dbcg_download_links_box_pages == 2 ) {
                /** add with image */
                if( strlen($squace_rdbcg_download_pages) > 0 ) {
                    $hurl_image = str_replace( "<siteImage>", $squace_rdbcg_download_pages, $hurl );
                    $content .= "<div id='squace_hurl'>{$hurl_image}</div>";
                }
            }

            if( $dbcg_download_links_box_pages == 0 || $dbcg_download_links_box_pages == 2 ) {
                /** add text only */
                if( strlen($settings['squace_rdbcg_download_pages_link_text']) > 0 ) {
                    $regex = "#([<]img)(.*)([[>])#";
                    $hurl_link = preg_replace( $regex, $settings['squace_rdbcg_download_pages_link_text'], $hurl );
                    $content .= "<div id='squace_hurl'>{$hurl_link}</div>";
                }
            }

        }

        if ( $b_bookmark_posts && !is_feed() && !is_page() && !in_array( $post->ID, $post_exclude ) ) {

            $href = ( "?action=infobox&post={$post->ID}" );

            $siteOptions = urlencode( $href );
            $hurl = str_replace( "<siteOptions>", $siteOptions, $hurl );

            $siteTitle = urlencode( $settings['feedtitle'] . ' - ' . $post->post_title  );
            $hurl = str_replace( "<siteTitle>", $siteTitle, $hurl );

            $tags = get_the_tags();
            $_tags = '';
            if( $tags ) {
                $_tags = array();
                foreach( $tags as $tag ) { $_tags[] = $tag->slug; }
                if( count($_tags) > 0 ) {
                    $_tags = array_slice( $_tags, 0, SQUACE_MAX_URL_TAGS );
                    $_tags = urlencode( implode( SQUACE_URL_TAGS_SEPARATOR, $_tags ) );
                }
            }
            $hurl = str_replace( "<siteTags>", $_tags, $hurl );

            if( $dbcg_download_links_box_posts == 1 || $dbcg_download_links_box_posts == 2 ) {
                /** add with image */
                if( strlen($squace_rdbcg_download_posts) > 0 ) {
                    $hurl_image = str_replace( "<siteImage>", $squace_rdbcg_download_posts, $hurl );
                    $content .= "<div id='squace_hurl'>{$hurl_image}</div>";
                }
            }

            if( $dbcg_download_links_box_posts == 0 || $dbcg_download_links_box_posts == 2 ) {
                /** add text only */
                if( strlen($settings['squace_rdbcg_download_posts_link_text']) > 0 ) {
                    $regex = "#([<]img)(.*)([[>])#";
                    $hurl_link = preg_replace( $regex, $post_link_text, $hurl );
                    $content .= "<div id='squace_hurl'>{$hurl_link}</div>";
                }
            }

        }

    }

    return $content;

}

/**
 * Prepare the widget code for showing on main page
 * @global object $dl_pluginSquace
 * @global object $post
 * @param array $args
 */
function widget_Squace( $args ) {

   global $dl_pluginSquace;

    $filename = str_replace('\\', '/', dirname(__FILE__)) . '/' . SQUACE_SETTINGS_FILE;
    if( file_exists( $filename ) ) {
        $b_Load = $dl_pluginSquace->settings( $settings, $filename, SQUACE_LOAD_SETTINGS );
        if( !$b_Load ) {
            // error, why? shouldn't happen, maybe no read permisions
        }
    }

    $plugin_url = plugins_url( '', __FILE__ );
    $base_generator = plugins_url( 'php/squaceml.php', __FILE__ );
    
    if( isset( $settings['squace_genhtml'] ) && strlen( $settings['squace_genhtml'] ) > 0 ) {
        $hurl = stripslashes( $settings['squace_genhtml'] );
    } else {
        $url_gen = $base_generator;
        $hurl =
        "<a
            onclick='window.open(\"http://www.squace.com/content.browse.action?addBookmark={$url_gen}<siteOptions>&title=<siteTitle>&tags=<siteTags>\",\"landingPagePopup\",\"width=480,height=720,scrollbars=yes,toolbar=no,status=no,menubar=no,left=300,top=200,screenX=300,screenY=200\")'
            href='#'
           >
		<img src='<siteImage>' alt='Download <siteTitle>' />
        </a>";
    }

    global $post;

    $squace_rpicture = DEFAULT_MOCKUP_DOWNLOAD_BUTTON;
    if(  isset( $settings['squace_rpicture'] ) && (strlen( $settings['squace_rpicture'] ) > 0 )  ) {
       $squace_rpicture =  '/' . DEFAULT_IMAGES_PATH_UPLOADS . '/' . $settings['squace_rpicture'];
    }

    $siteImage = $plugin_url . $squace_rpicture;
    $hurl = str_replace( "<siteImage>", $siteImage, $hurl );

    $siteOptions = urlencode( "?action=screen" );
    $hurl = str_replace( "<siteOptions>", $siteOptions, $hurl );

    if( isset( $settings['feedtitle']) && strlen( $settings['feedtitle'] ) > 0 ) {
        $siteTitle = urlencode( $settings['feedtitle'] );
    } else {
        $siteTitle = get_bloginfo('name');
    }
    $hurl = str_replace( "<siteTitle>", $siteTitle, $hurl );

    $tags = get_tags();
    $_tags = '';
    if( $tags ) {
        $_tags = array();
        foreach( $tags as $tag ) { $_tags[] = $tag->slug; }
        if( count($_tags) > 0 ) {
            $_tags = array_slice( $_tags, 0, SQUACE_MAX_URL_TAGS );
            $_tags = urlencode( implode( SQUACE_URL_TAGS_SEPARATOR, $_tags ) );
        }
    }
    $hurl = str_replace( "<siteTags>", $_tags, $hurl );

    echo $args['before_widget'];
//    echo $args['before_title'];
//    echo $args['after_title'];
    echo "<div id='squace_hurl'>{$hurl}</div>";
    echo $args['after_widget'];

}

/**
 * Registers squace widget to wordpress API
 */
function init_squace_widget() {
    register_sidebar_widget( __("Squace"), 'widget_Squace' );
}

if( !$squace_ajax_load ) {

    if (class_exists("plugin_Squace")) {
        $dl_pluginSquace = new plugin_Squace();
    }

    if( !function_exists( "generate_AdminPanel") ) {

        function generate_squace_AdminPanel() {

            global $dl_pluginSquace;
            if (!isset($dl_pluginSquace)) {
                return;
            }

            if ( function_exists('add_submenu_page') ) {
                add_submenu_page('options-general.php', __('Squace', 'sqConfiguration'), __('Squace', 'sqConfiguration'), 'administrator', 'squace-config', array(&$dl_pluginSquace, 'load_plugin'));
            }

        }

    }

    if (isset($dl_pluginSquace)) {

        if( function_exists( "is_admin" ) ) {
            if( is_admin() ) {

                add_action('admin_menu', 'generate_squace_AdminPanel');
                add_action('admin_head', array(&$dl_pluginSquace, 'add_headerCode'));
                add_action('activate_wp-squace/wp-squace.php',  array(&$dl_pluginSquace, 'activate'));
                add_action('deactivate_wp-squace/wp-squace.php',  array(&$dl_pluginSquace, 'deactivate'));

            }
        }

        // View this page/post on Squace
        add_filter('the_content', 'generate_bookmark_posts_pages', 1097);

        //register widget
        add_action("plugins_loaded", "init_squace_widget");
    }
    
}
?>
