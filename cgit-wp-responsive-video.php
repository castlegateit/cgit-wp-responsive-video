<?php

/*

Plugin Name: Castlegate IT WP Responsive Video
Plugin URI: http://github.com/castlegateit/cgit-wp-responsive-video
Description: Embeds videos responsively when embedding in post content.
Version: 1.1
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/


/**
 * Filters the embed code HTML provided by WordPress to something more
 * appropriate for responsive websites. Detects the embed type and called the
 * specific embed function.
 *
 * @param string $html HTML embed code
 * @param string $url  Media URL
 * @param array  $args Array of embed arguments
 *
 * @author Andy Reading <andy@castlegateit.co.uk>
 *
 * @return string HTML embed code
 */
function cgit_wp_responsive_video_embed($html, $url, $args) {

    // Supported embedded services
    $supported = array(
        'youtube',
        'vimeo'
    );

    // Calculate embed ratio
    $width = $args['width'];
    $height = $args['height'];

    /**
     * WordPress seems to return incorrect height and width. Extract them from
     * the embed code
     */
    if (preg_match('%width=\"(\d+)\"%i', $html, $match_width)
        && preg_match('%height=\"(\d+)\"%i', $html, $match_height)
    ) {
        $width = $match_width[1];
        $height = $match_height[1];
    }

    $ratio = $height / $width;

    // Check the embed matches a supported service and return the embed code
    foreach ($supported as $service) {

        $detect = 'cgit_wp_responsive_video_detect_' . $service;
        $embed = 'cgit_wp_responsive_video_embed_' . $service;

        if ($code = $detect($url)) {
            return $embed($code, $ratio);
        }
    }

    // No support, return unmodified code
    return $html;
}

add_filter('embed_oembed_html','cgit_wp_responsive_video_embed', 10, 3);


/**
 * Enqueue the custom styles
 */
function cgit_wp_responsive_video_enqueue() {
    wp_enqueue_style(
        'cgit-wp-responsive-video',
        plugins_url('css/video-styles.css', __FILE__ ),
        '1'
    );
}

add_action('wp_enqueue_scripts', 'cgit_wp_responsive_video_enqueue');
add_action('admin_enqueue_scripts', 'cgit_wp_responsive_video_enqueue');

function cgit_wp_responsive_video_editor_enqueue() {
    add_editor_style(plugins_url('css/video-styles.css', __FILE__ ));
}

add_action('admin_init', 'cgit_wp_responsive_video_editor_enqueue');


/**
 * Takes a media URL and attempts to determine if it is a YouTube video,
 * returning the YouTube ID
 *
 * @param string $url Media URL
 *
 * @author Andy Reading <andy@castlegateit.co.uk>
 *
 * @return string YouTube ID
 */
function cgit_wp_responsive_video_detect_youtube($url) {

    $regex = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|';
    $regex.= '(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

    if (preg_match($regex, $url, $match)) {
        return $match[1];
    }

    return false;
}


/**
 * Returns YouTube embed code, optimised for responsive code.
 *
 * @param string  $code  YouTube ID
 * @param integer $ratio Width/height ratio
 *
 * @author Andy Reading <andy@castlegateit.co.uk>
 *
 * @return string HTML embed code
 */
function cgit_wp_responsive_video_embed_youtube($code, $ratio) {

    $padding = ' style="padding-bottom:' . round($ratio * 100, 2) . '%"';

    $return = '<div class="cgit-wp-responsive-video-wrapper">' . "\n";
    $return.= '<div class="cgit-wp-responsive-video"' . $padding . '>' . "\n";
    $return.= '    <iframe src="//www.youtube.com/embed/';
    $return.= $code . '" frameborder="0" allowfullscreen></iframe>' . "\n";
    $return.= '</div>';
    $return.= '</div>';

    return $return;
}


/**
 * Takes a media URL and attempts to determine if it is a Vimeo video,
 * returning the Vimeo ID
 *
 * @param string $url Media URL
 *
 * @author Andy Reading <andy@castlegateit.co.uk>
 *
 * @return string Vimeo ID
 */
function cgit_wp_responsive_video_detect_vimeo($url) {

    $regex = '/^(http(s)?:\/\/)?(www\.)?vimeo\.com\/([\d]{1,9})$/i';
    $regex_embed = '/player\.vimeo\.com\/video\/([\d]{1,9})"/i';

    if (preg_match($regex, $url, $match)) {
        // Normal link
        return $match[4];
    }
    else {
        // Embed link
        if (preg_match($regex_embed, $url, $match)) {
            return $match[1];
        }
    }

    return false;
}


/**
 * Returns YouTube embed code, optimised for responsive sites.
 *
 * @param string  $code  Vimeo ID
 * @param integer $ratio Width/height ratio
 *
 * @author Andy Reading <andy@castlegateit.co.uk>
 *
 * @return string HTML embed code
 */
function cgit_wp_responsive_video_embed_vimeo($code, $ratio) {

    $padding = ' style="padding-bottom:' . round($ratio * 100, 2) . '%"';

    $params = '?portrait=0&amp;byline=0&amp;badge=0&amp;color=E70871';

    $return = '<div class="cgit-wp-responsive-video-wrapper">' . "\n";
    $return.= '<div class="cgit-wp-responsive-video"' . $padding . '>' . "\n";
    $return.= '   <iframe src="//player.vimeo.com/video/' . $code . $params;
    $return.= '" frameborder="0" webkitallowfullscreen mozallowfullscreen ';
    $return.= 'allowfullscreen></iframe>' . "\n";
    $return.= '</div>';
    $return.= '</div>';

    return $return;
}
