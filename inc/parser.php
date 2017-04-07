<?php

function oge_get_opengraph_data( $html ) {
	/**
	 * Effectively silence the errors so that the code does not bomb out at this
	 * point and continues to work. Despite the oddities through HTML of
	 * websites across the internet ...
	 */
	$old_libxml_error = libxml_use_internal_errors(true);

	$doc = new DOMDocument();
	$doc->loadHTML( $html );

	libxml_use_internal_errors( $old_libxml_error );

	/**
	 * Grab all <meta> elements in the HTML response and make sure we have more
	 * than one element to work with.
	 */
	$tags = $doc->getElementsByTagName( 'meta' );

	if ( false === $tags || $tags->length === 0) {
		return false;
	}

	/**
	 * Loop through each metadata tag and construct an dictionary containing the
	 * data from OpenGraph.
	 */
	$opengraph = array();

	foreach ( $tags as $tag ) {
		if ( $tag->hasAttribute( 'property' ) && 0 === strpos( $tag->getAttribute( 'property' ), 'og:' ) ) {
			$opengraph[str_ireplace( 'og:', '', $tag->getAttribute( 'property' ) )] = $tag->getAttribute( 'content' );
		}
	}

	return $opengraph;
}
