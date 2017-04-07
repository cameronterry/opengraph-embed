<?php

function oge_get_opengraph_data( $html ) {
	$matches = array();
	$opengraph = array();

	preg_match_all('~<\s*meta\s+property="(og:[^"]+)"\s+content="([^"]*)~i', $html, $matches);
var_dump( $matches );
	$keys = $matches[1];
	$values = $matches[2];

	foreach ( $keys as $i => $value ) {
		$opengraph[str_ireplace( 'og:', '', $value )] = $values[$i];
	}

	return $opengraph;
}
