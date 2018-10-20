<?php

include 'includes/function-build-wordpress.php';
include 'includes/function-woocommerce.php';

// Add widget
function my_widgets_init()
{
    register_sidebar(array(
        'name' => 'Lang',
        'id' => 'lang',
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '',
        'after_title' => '',
    ));
}

add_action('widgets_init', 'my_widgets_init');


// Thumbnail
add_theme_support('post-thumbnails');
add_image_size('thumb', 500, 500, true);


// Style
function my_theme_enqueue_style()
{
    wp_enqueue_style('slick', get_template_directory_uri() . '/bower_components/slick-carousel/slick/slick.css');
    wp_enqueue_style('magnific', get_template_directory_uri() . '/bower_components/magnific-popup/dist/magnific-popup.css');
    wp_enqueue_style('style', get_template_directory_uri() . '/assets/css/style.css');
}

add_action('wp_enqueue_scripts', 'my_theme_enqueue_style');

// Scripts
function my_theme_add_scripts()
{

    if (is_front_page()) {
    	wp_enqueue_script('map', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyC7nl04gTQl-ZBg0gjus9KGEEOKiczTW7o', '', '', true);
    }
    wp_enqueue_script('slick', get_template_directory_uri() . '/bower_components/slick-carousel/slick/slick.min.js', '', '', true);
    wp_enqueue_script('magnific', get_template_directory_uri() . '/bower_components/magnific-popup/dist/jquery.magnific-popup.min.js', '', '', true);
    wp_enqueue_script('scripts', get_template_directory_uri() . '/assets/js/scripts.js', '', '', true);

    wp_localize_script('scripts', 'scripts_object',
        array(
            'url' => get_template_directory_uri(),
            'home' => get_home_url()
        )
    );

}

add_action('wp_enqueue_scripts', 'my_theme_add_scripts');
