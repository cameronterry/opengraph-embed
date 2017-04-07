<?php
/**
 * Plugin Name: OpenGraph Embed
 * Description: Making all URLs work like oEmbed, even if the URL does not have an oEmbed endpoint.
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

	// $response = wp_remote_get( $url, array() );
	//
	// if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
	// 	return $output;
	// }
	//
	// $html = wp_remote_retrieve_body( $response );
	global $oge_embed;
	$oge_embed = oge_get_opengraph_data( file_get_contents( OG_EMBED_DIR . '/test.html' ) );
	ob_start();
	require( OG_EMBED_DIR . '/template/embed.php' );
	$html .= ob_get_clean();

	$oge_embed = oge_get_opengraph_data( file_get_contents( OG_EMBED_DIR . '/test2.html' ) );
	ob_start();
	require( OG_EMBED_DIR . '/template/embed.php' );
	$html .= ob_get_clean();

	$oge_embed = oge_get_opengraph_data( file_get_contents( OG_EMBED_DIR . '/test3.html' ) );
	ob_start();
	require( OG_EMBED_DIR . '/template/embed.php' );
	$html .= ob_get_clean();

	$oge_embed = oge_get_opengraph_data( file_get_contents( OG_EMBED_DIR . '/test4.html' ) );
	ob_start();
	require( OG_EMBED_DIR . '/template/embed.php' );
	$html .= ob_get_clean();

	return $html;
}
add_filter( 'embed_maybe_make_link', 'ogembed_maybe_make_link', 99, 2 );

function ogembed_enqueue_styles() {
	wp_enqueue_style( 'opengraph-embed-css', plugins_url( 'template/embed.css', __FILE__ ), null, OG_EMBED_VERSION );
}
add_action( 'wp_enqueue_scripts', 'ogembed_enqueue_styles' );
