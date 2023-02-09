<?php

/*

Plugin Name: Castlegate IT WP Responsive Video
Plugin URI: http://github.com/castlegateit/cgit-wp-responsive-video
Description: Embeds videos responsively when embedding in post content.
Version: 1.3.2
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
        $width = (float) $match_width[1];
        $height = (float) $match_height[1];
    }

    $ratio = $height / $width;

    // Get video iframe title
    $title = null;

    if (preg_match('/ title="(.+?)"/i', $html, $match_title)) {
        $title = $match_title[1];
    }

    // Check the embed matches a supported service and return the embed code
    foreach ($supported as $service) {

        $detect = 'cgit_wp_responsive_video_detect_' . $service;
        $embed = 'cgit_wp_responsive_video_embed_' . $service;

        if ($code = $detect($url)) {
            return $embed($code, $ratio, $title);
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
function cgit_wp_responsive_video_embed_youtube($code, $ratio, $title = null) {
    if (is_null($title)) {
        $title = 'YouTube video';
    }

    $attributes = [
        'src' => "//www.youtube.com/embed/$code",
        'title' => $title,
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'loading' => 'lazy',
    ];

    return cgit_responsive_video_wrap($attributes, $ratio);
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
function cgit_wp_responsive_video_embed_vimeo($code, $ratio, $title = null) {
    if (is_null($title)) {
        $title = 'Vimeo video';
    }

    $attributes = [
        'src' => "//player.vimeo.com/video/$code?portrait=0&amp;byline=0&amp;badge=0&amp;color=E70871",
        'title' => $title,
        'style' => "padding-bottom: $padding%;",
        'frameborder' => '0',
        'allowfullscreen' => 'allowfullscreen',
        'loading' => 'lazy',
    ];

    return cgit_responsive_video_wrap($attributes, $ratio);
}

/**
 * Generic responsive video wrapper and iframe
 *
 * @param array $attributes
 * @param float $ratio
 * @return string
 */
function cgit_responsive_video_wrap(array $attributes, float $ratio = null)
{
    $padding = 56.25;

    if (!is_null($ratio)) {
        $padding = round($ratio * 100, 2);
    }

    $attributes = array_merge([
        'allowfullscreen' => 'allowfullscreen',
        'frameborder' => '0',
        'loading' => 'lazy',
    ], $attributes);

    return '<div class="cgit-wp-responsive-video-wrapper modified">'
        . '<div class="cgit-wp-responsive-video" style="padding-bottom: ' . $padding . '%;">'
        . '<iframe ' . cgit_responsive_video_attributes($attributes) . '></iframe>'
        . '</div>'
        . '</div>';
}

/**
 * Convert associative array to attributes
 *
 * @param array
 * @return string
 */
function cgit_responsive_video_attributes(array $attributes)
{
    $formatted = [];

    foreach ($attributes as $key => $value) {
        if ($key === $value) {
            $formatted[] = $key;
            continue;
        }

        if (is_array($value)) {
            $value = implode(' ', $value);
        }

        $formatted[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
    }

    return implode(' ', $formatted);
}
