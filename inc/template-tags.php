<?php

function opengraph_embed_classes() {
	global $og_embed_data;

	$classes = array( 'opengraph-embed', sanitize_title( $og_embed_data['site_name'] ) );

	if ( array_key_exists( 'locale', $og_embed_data ) ) {
		$classes[] = str_replace( '_', '-', sanitize_title( $og_embed_data['locale'] ) );
	}

	if ( array_key_exists( 'type', $og_embed_data ) ) {
		$classes[] = str_replace( '_', '-', sanitize_title( $og_embed_data['type'] ) );
	}

	printf( ' class="%1$s"', join( ' ', $classes ) );
}

function opengraph_the_content() {
	global $og_embed_data;
	echo( $og_embed_data['description'] );
}

function opengraph_the_permalink() {
	global $og_embed_data;
	echo( $og_embed_data['url'] );
}

function opengraph_the_site_name() {
	global $og_embed_data;
	echo( $og_embed_data['site_name'] );
}

function opengraph_the_thumnbnail() {
	global $og_embed_data;

	printf( '<img src="%1$s" />', $og_embed_data['image'] );
}

function opengraph_the_title() {
	global $og_embed_data;
	echo( $og_embed_data['title'] );
}
