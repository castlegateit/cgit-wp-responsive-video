<?php

/*

Plugin Name: Castlegate IT WP Responsive Video
Plugin URI: http://github.com/castlegateit/cgit-wp-responsive-video
Description: Embeds videos responsively when embedding in post content.
Version: 1.5.4
Author: Castlegate IT
Author URI: http://www.castlegateit.co.uk/
License: MIT

*/

/**
 * Filter post content and ACF fields
 */
add_filter('the_content', 'cgit_wp_responsive_video_sanitize_embed', 20);
add_filter('acf/format_value/type=oembed', 'cgit_wp_responsive_video_sanitize_embed', 20);
add_filter('acf/format_value/type=wysiwyg', 'cgit_wp_responsive_video_sanitize_embed', 20);

/**
 * Sanitize embed HTML
 *
 * Set the lazy loading attribute on all iframe elements. Set width, height, and
 * aspect ratio styles on all video iframe elements.
 *
 * @param string $embed Embed HTML
 * @return string
 */
function cgit_wp_responsive_video_sanitize_embed(string $embed): string
{
    if ($embed === '') {
        return '';
    }

    $document = new DOMDocument();

    // Suppress LibXML HTML5 errors
    libxml_use_internal_errors(true);

    // Add UTF-8 encoding meta tag
    $embed = '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . $embed;

    $document->loadHTML($embed, LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);

    // Clear LibXML HTML5 errors
    libxml_clear_errors();

    foreach ($document->getElementsByTagName('iframe') as $iframe) {
        cgit_wp_responsive_video_sanitize_dom_element_iframe($iframe);
    }

    $body = $document->getElementsByTagName('body')->item(0);

    if ($body) {
        $embed = '';

        foreach ($body->childNodes as $node) {
            $embed .= $document->saveHTML($node);
        }
    }

    return $embed;
}

/**
 * Sanitize iframe DOMElement
 *
 * @param DOMElement $element
 * @return void
 */
function cgit_wp_responsive_video_sanitize_dom_element_iframe(DOMElement $element): void
{
    // Ignore non-iframe elements
    if ($element->tagName !== 'iframe') {
        return;
    }

    $style = $element->getAttribute('style');

    // Ignore elements that already have a width or aspect ratio
    if (str_contains($style, 'width:') || str_contains($style, 'aspect-ratio:')) {
        return;
    }

    $element->setAttribute('loading', 'lazy');
    cgit_wp_responsive_video_sanitize_dom_element_iframe_video($element);
}

/**
 * Sanitize video iframe DOMElement
 *
 * If the aspect ratio can be determined, set appropriate width, height, and
 * aspect ratio styles.
 *
 * @param DOMElement $element
 * @return void
 */
function cgit_wp_responsive_video_sanitize_dom_element_iframe_video(DOMElement $element): void
{
    if (!cgit_wp_responsive_video_is_dom_element_iframe_video($element)) {
        return;
    }

    $width = (int) $element->getAttribute('width');
    $height = (int) $element->getAttribute('height');
    $style = 'height: auto; width: 100%;';

    if ($width && $height) {
        $style = "aspect-ratio: $width / $height; $style";
    }

    $element->setAttribute('style', $style);
}

/**
 * Is DOMElement a video iframe?
 *
 * @param DOMElement $element
 * @return bool
 */
function cgit_wp_responsive_video_is_dom_element_iframe_video(DOMElement $element): bool
{
    if ($element->tagName !== 'iframe') {
        return false;
    }

    $src = $element->getAttribute('src');
    $domain = parse_url($src, PHP_URL_HOST);

    if (!is_string($domain)) {
        return false;
    }

    $domain = strtolower($domain);

    $video_domains = [
        'vimeo.com',
        'youtube.com',
    ];

    foreach ($video_domains as $video_domain) {
        if (
            $domain === $video_domain ||
            str_ends_with($domain, '.' . $video_domain)
        ) {
            return true;
        }
    }

    return false;
}
