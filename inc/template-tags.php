<?php

function opengraph_embed_classes() {
	global $oge_embed;

	$classes = array( 'opengraph-embed', sanitize_title( $oge_embed['site_name'] ) );

	if ( array_key_exists( 'locale', $oge_embed ) ) {
		$classes[] = str_replace( '_', '-', sanitize_title( $oge_embed['locale'] ) );
	}

	if ( array_key_exists( 'type', $oge_embed ) ) {
		$classes[] = str_replace( '_', '-', sanitize_title( $oge_embed['type'] ) );
	}

	printf( ' class="%1$s"', join( ' ', $classes ) );
}

function opengraph_the_content() {
	global $oge_embed;
	echo( $oge_embed['description'] );
}

function opengraph_the_permalink() {
	global $oge_embed;
	echo( $oge_embed['url'] );
}

function opengraph_the_site_name() {
	global $oge_embed;
	echo( $oge_embed['site_name'] );
}

function opengraph_the_thumnbnail() {
	global $oge_embed;

	printf( '<img src="%1$s" />', $oge_embed['image'] );
}

function opengraph_the_title() {
	global $oge_embed;
	echo( $oge_embed['title'] );
}
