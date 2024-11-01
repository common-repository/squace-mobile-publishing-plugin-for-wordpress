<?php

define( 'SQUACE_PLUGIN_BASE_PATH', str_replace( "//", "/", dirname( __FILE__ ) . '/../' ) );

define( 'SQUACE_VERSION', '1.3.5' );
define( 'SQUACE_LOAD_SETTINGS', 1 );
define( 'SQUACE_SAVE_SETTINGS', 2 );

define( 'SQUACE_MAX_URL_TAGS', 7 );
define( 'SQUACE_URL_TAGS_SEPARATOR', ' ' );

define( 'SQUACE_DOWNLOAD_HEIGHT', 100 );
define( 'SQUACE_DOWNLOAD_WIDTH', 150 );

define( 'SQUACE_SETTINGS_DIR', 'settings' );
define( 'SQUACE_SETTINGS_FILE', '/' . SQUACE_SETTINGS_DIR . '/squace.settings' );

define( 'DEFAULT_IMAGES_PATH', 'images' );
define( 'DEFAULT_IMAGES_PATH_UPLOADS', 'uploads' );
define( 'DEFAULT_CACHE_PATH', 'cache' );
define( 'CACHE_DEFAULT_EXPIRE_TIME', 15 * 60 ); // 15min

define( 'DEFAULT_MOCKUP_BACKGROUND', '/' . DEFAULT_IMAGES_PATH . '/mid.jpg');
define( 'DEFAULT_MOCKUP_MENU', '/' . DEFAULT_IMAGES_PATH . '/menu.jpg');
define( 'DEFAULT_MOCKUP_BOTTOM', '/' . DEFAULT_IMAGES_PATH . '/bottom.jpg');
define( 'DEFAULT_MOCKUP_GRID', '/' . DEFAULT_IMAGES_PATH . '/grid.png');
define( 'DEFAULT_MOCKUP_ICON', '/' . DEFAULT_IMAGES_PATH . '/default-icon.gif');
define( 'DEFAULT_MOCKUP_DOWNLOAD_BUTTON', '/' . DEFAULT_IMAGES_PATH . '/default-widget-button.png');
define( 'DEFAULT_MOCKUP', '/php/mockup.php');
define( 'DEFAULT_MOCKUP_DOWNLOAD_BUTTON_POSTS', '/' . DEFAULT_IMAGES_PATH . '/default-post-button.png' );
define( 'DEFAULT_MOCKUP_DOWNLOAD_BUTTON_PAGES', '/' . DEFAULT_IMAGES_PATH . '/default-page-button.png' );

/** squaceml.php */
define( 'USE_CURL_MODULE', true );

define( 'SQUACE_DEFAULT_GRID_SIZE', 400 );
define( 'SQUACE_LAYOUT_INFOBOX', 1 );
define( 'SQUACE_LAYOUT_SQUAREGRID', 2 );
define( 'SQUACE_LAYOUT_COMMENTS', 3 );
define( 'SQUACE_LAYOUT_POSTACOMMENT', 4 );
define( 'SQUACE_LAYOUT_REDIRECT', 5 );
define( 'SQUACE_LAYOUT_POST', 6 );

define( 'SQUACE_LIGHT_FEED', 0 );
define( 'SQUACE_COMPLETE_FEED', 1 );
define( 'SQUACE_CUSTOM_FEED', 2 );
define( 'SQUACE_COMMENTS_FEED', 3 );

define( 'SQUACE_TYPE_FEED_POSTS', 0 );
define( 'SQUACE_TYPE_FEED_TAGS', 1 );
define( 'SQUACE_TYPE_FEED_PAGES', 2 );
define( 'SQUACE_TYPE_FEED_COMMENTS', 3 );

?>