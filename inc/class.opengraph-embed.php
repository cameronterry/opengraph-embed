<?php

class OGEmbed {
	private $data = array();

	private $html = '';

	private $post_id = null;

	private $url = '';

	/**
	 * @param  string $post_id The Post ID which includes the OpenGraph embed.
	 * @param  string $url     The URL which is to be embedded into the post.
	 */
	public function __construct( $post_id, $url ) {
		$this->post_id = $post_id;
		$this->url = $url;

		$this->cache_keys( $url );
		$this->cache_init( $post_id, $url );
	}

	/**
	 * Initialises the cache storage of the OpenGraph embed and prepares the
	 * data - if any - for use.
	 *
	 * @param  string $post_id The Post ID which includes the OpenGraph embed.
	 * @param  string $url     The URL which is to be embedded into the post.
	 * @return array           A dictionary containing the various entries from the OpenGraph tags.
	 */
	private function cache_init( $post_id, $url ) {
		$cache = get_post_meta( $post_id, $this->cachekey, true );
		$cache_time = get_post_meta( $post_id, $this->cachekey_time, true );
		
		if ( ! $cache_time ) {
			$cache_time = 0;
		}

		/**
		 * Enable developers of themes and plugins to override the termination
		 * period for the OpenGraph Embed.
		 */
		$ttl = apply_filters( 'ogembed_ttl', DAY_IN_SECONDS, $url );

		/**
		 * If cached within the TTL, then populate the data property with the
		 * data stored in cache.
		 */
		$cached_recently = ( time() - $cache_time ) < $ttl;

		if ( $cached_recently ) {
			$this->data = $cache;
		}
	}

	private function cache_keys( $url ) {
		$key_suffix = md5( $url . 'opengraph_embed' );

		$this->cachekey = '_opengraph_embed_' . $key_suffix;
		$this->cachekey_time = '_opengraph_embed_time_' . $key_suffix;
	}

	/**
	 * Retrieve the HTML for the given URL. This will be in other steps to get
	 * the OpenGraph metadata in the HTML.
	 *
	 * @param string $url The URL to call.
	 */
	public function curl() {
		$response = wp_remote_get( $this->url, array() );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $output;
		}

		$this->html = wp_remote_retrieve_body( $response );
	}

	public function get_data() {
		/**
		 * If the URL has not been cached, then we will do the cURL and data
		 * parse.
		 */
		if ( empty( $this->data ) ) {
			$this->curl();
			$this->data = $this->parse_html();
		}

		return $this->data;
	}

	private function parse_html() {
		/**
		 * Effectively silence the errors so that the code does not bomb out at this
		 * point and continues to work. Despite the oddities through HTML of
		 * websites across the internet ...
		 */
		$old_libxml_error = libxml_use_internal_errors(true);

		$doc = new DOMDocument();
		$doc->loadHTML( $this->html );

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

		/**
		 * Update the cache on the Post Metadata to include this newly parsed
		 * URL / HTML.
		 */
		update_post_meta( $this->post_id, $this->cachekey, $opengraph );
		update_post_meta( $this->post_id, $this->cachekey_time, time() );

		return $opengraph;
	}
}
