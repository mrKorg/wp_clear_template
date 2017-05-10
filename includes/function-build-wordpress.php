<?php

remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'wp_generator');

add_filter('show_admin_bar', '__return_false');
add_theme_support('post-thumbnails');


// Add menu
if (function_exists('add_theme_support')) {
    add_theme_support('menus');
}

// Jquery
add_action('wp_enqueue_scripts', 'true_peremeshhaem_jquery_v_futer');
function true_peremeshhaem_jquery_v_futer()
{
    wp_deregister_script('jquery');
    wp_register_script('jquery', includes_url('/js/jquery/jquery.js'), false, null, true);
    wp_enqueue_script('jquery');
}

// Delete anchor
function no_more_jumping($post)
{
    return '<a href="' . get_permalink($post->ID) . '" class="more-link">' . 'Подробнее' . '</a>';
}

add_filter('the_content_more_link', 'no_more_jumping');

// Post Gallery
add_filter('use_default_gallery_style', '__return_false');  // delete style
add_filter('the_content', 'remove_br_gallery', 11, 2);     // delete BR
function remove_br_gallery($output)
{
    return preg_replace('/\<br[^\>]*\>/', '', $output);
}

/**
 * @return mixed
 */
function getCurrentCatId()
{
    return get_query_var('cat');
}

/**
 * @return mixed
 */
function getCurrentCat()
{
    return get_category(getCurrentCatId());
}

/**
 * @return bool
 */
function hasCatChildCategories()
{
    $child = get_categories(['parent' => getCurrentCatId()]);
    if (count($child) != 0) return true;
    return false;
}

/**
 * Returns ID of top-level parent category, or current category if you are viewing a top-level
 * @param  string $catid Category ID to be checked
 * @return  string    $catParent  ID of top-level parent category
 */
function pa_category_top_parent_id($catid = NULL)
{
    if (!$catid) $catid = getCurrentCatId();
    while ($catid) {
        $cat = get_category($catid);
        $catid = $cat->category_parent;
        $catParent = $cat->cat_ID;
    }
    return $catParent;
}

/**
 * Text cropping
 * @param $text
 * @param $size
 * @param bool $showEllipsis
 * @return string
 */
function textCropping($text, $size, $showEllipsis = true)
{
    $ellipsis = '';
    if ($showEllipsis) $ellipsis = "...";
    if (strlen($text) > $size) return mb_substr($text, 0, $size) . $ellipsis;
    return $text;
}

// Reset Thumbnail
add_filter('wp_calculate_image_srcset_meta', '__return_null');
add_filter('post_thumbnail_html', 'remove_width_attribute', 10);
add_filter('image_send_to_editor', 'remove_width_attribute', 10);
function remove_width_attribute($html)
{
    $html = preg_replace('/(width|height)="\d*"\s/', "", $html);
    return $html;
}

/**
 * Get Current Url
 * @return string
 */
function curPageURL()
{
    $pageURL = 'http';
    if ($_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $uri = explode('?', $_SERVER["REQUEST_URI"]);
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $uri[0];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $uri[0];
    }
    return $pageURL;
}

// Get Youtube ID
function getYoutubeVideoId($url)
{
    parse_str( parse_url( $url, PHP_URL_QUERY ), $my_array_of_vars );
    return $my_array_of_vars['v'];
}

// Remove menu items
// add_action('admin_menu', 'remove_menus');
// add_action('admin_menu', 'remove_sub_menu_items');
// function remove_menus()
// {
//     remove_menu_page('index.php');                  //Консоль
//     remove_menu_page('users.php');                  //Пользователи
//     remove_menu_page('edit-comments.php');          //Комментарии
//     remove_menu_page('theme-editor.php');           //Эдитор
//     remove_menu_page('themes.php');                 //Внешний вид
//     remove_menu_page('plugins.php');                //Плагины
//     remove_menu_page('tools.php');                  //Инструменты
//     remove_menu_page('options-general.php');        //Настройки
// }

// function remove_sub_menu_items()
// {
//     remove_submenu_page('themes.php', 'themes.php');
//     remove_submenu_page('themes.php', 'theme-editor.php');
//     remove_submenu_page('themes.php', 'customize.php');
//     remove_submenu_page('themes.php', 'customize.php?return=%2Fwp-admin%2Fadmin.php%3Fpage%3Dwpcf7');
//     remove_submenu_page('tools.php', 'import.php');
//     remove_submenu_page('tools.php', 'export.php');
//     remove_submenu_page('tools.php', 'tools.php');
//     remove_submenu_page('edit.php', 'edit-tags.php?taxonomy=post_tag');
// }

// function remove_acf_menu() {
//     remove_menu_page('edit.php?post_type=acf');
// }
// add_action( 'admin_menu', 'remove_acf_menu', 999);