<?php
/**
 * Plugin Name: OpenGraph Embed
 * Description: Making all URLs work like oEmbed through OpenGraph, even if the URL does not have an oEmbed endpoint.
 * Plugin URI: https://github.com/cameronterry/opengraph-embed/
 * Author: Automattic
 * Author URI: https://github.com/cameronterry/
 * Version: 0.0.1
 * Text Domain: opengraph-embed
 * Domain Path: /languages/
 * License: GPLv2 or later
 */

define( 'OG_EMBED_VERSION', '0.0.1' );
define( 'OG_EMBED_DIR', dirname( __FILE__ ) );

require_once( OG_EMBED_DIR . '/inc/parser.php' );
require_once( OG_EMBED_DIR . '/inc/template-tags.php' );

/**
 * ==================
 * ADMIN PREVIEW NOTE
 * ==================
 *
 * In the admin area, for each URL put on a line by itself will called an AJAX
 * action called "parse-embed". This in turn sets the property
 * "return_false_on_fail" on the global WP_Embed object instance as per the Trac
 * reference below.
 *
 * https://core.trac.wordpress.org/browser/tags/4.7/src/wp-admin/includes/ajax-actions.php#L2961
 *
 * This sadly prevents the call to WP_Embed::maybe_make_link() ever reaching the
 * "embed_maybe_make_link" filter.
 *
 * https://developer.wordpress.org/reference/classes/wp_embed/maybe_make_link/
 *
 * So until a action / filter hook between the two code references above can be
 * found, or a change to WordPress core. We'll have to rely on blind faith that
 * the URL will actually make it to a fancy embed. :-/
 */

/**
 * The filter "embed_maybe_make_link" is only called if oEmbed failed to create
 * an embed from any other mechanism within WordPress and it's plugins. To make
 * sure no one else has thought to use this filter, the prioirty has been put to
 * a high number (99).
 *
 * @param  string $output Essentially the finalise embed. The output from the URL to the text editor / front-end of WordPress.
 * @param  string $url    The URL which began the process of creating this Embed functionality.
 * @return string         Passed in $output variable or if still a URL and OpenGraph could be parsed, OpenGraph Embed.
 */
function ogembed_maybe_make_link( $output, $url ) {
	/**
	 * Ensure that we are working with a URL. This is to make sure that no other
	 * plugin has converted the URL into something else. Or so goes the
	 * theory...
	 */
	if ( false === filter_var( $output, FILTER_VALIDATE_URL ) ) {
		return $output;
	}

	/** Get the post data. */
	$post = get_post();

	/**
	 * Retrieve the data from the post metadata if available. This is used as a
	 * caching mechanism, as we can't rely on object cache for all WordPress
	 * setups.
	 */
	$key_suffix = md5( $url . 'opengraph_embed' );
	$cachekey = '_opengraph_embed_' . $key_suffix;
	$cachekey_time = '_opengraph_embed_time_' . $key_suffix;

	$cache = get_post_meta( $post->ID, $cachekey, true );
	$cache_time = get_post_meta( $post->ID, $cachekey_time, true );

	if ( ! $cache_time ) {
		$cache_time = 0;
	}

	$ttl = apply_filters( 'ogembed_ttl', DAY_IN_SECONDS, $url );

	$cached_recently = ( time() - $cache_time ) < $ttl;

	global $oge_embed;
	if ( $cached_recently ) {
		$oge_embed = $cache;
	}
	else {
		$response = wp_remote_get( $url, array() );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $output;
		}

		$html = wp_remote_retrieve_body( $response );
		$oge_embed = oge_get_opengraph_data( $html );

		update_post_meta( $post->ID, $cachekey, $oge_embed );
		update_post_meta( $post->ID, $cachekey_time, time() );
	}

	ob_start();
	require( OG_EMBED_DIR . '/template/embed.php' );
	$output = ob_get_clean();

	return $output;
}
add_filter( 'embed_maybe_make_link', 'ogembed_maybe_make_link', 99, 2 );

function ogembed_enqueue_styles() {
	wp_enqueue_style( 'opengraph-embed-css', plugins_url( 'template/embed.css', __FILE__ ), null, OG_EMBED_VERSION );
}
add_action( 'wp_enqueue_scripts', 'ogembed_enqueue_styles' );
