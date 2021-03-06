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

	/**
	 * Put together the cache keys, which is an MD5 representation of the URL to
	 * be embedded.
	 */
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
		$response = wp_remote_get( $this->url, array(
			'user-agent' => sprintf( 'OpenGraph Embed for WordPress/%1$s; %2$s (this website is sharing a direct link to your website, yay)', OG_EMBED_VERSION, home_url() )
		) );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$this->html = wp_remote_retrieve_body( $response );

		return true;
	}

	/**
	 * Returns the OpenGraph data, either from cache in the post metdata or by
	 * calling the URL and parsing the HTML response. This is the enabling part
	 * of the class which will actually make things happen.
	 *
	 * @return array Dictionary containing the keys and values from the URLs OpenGraph meta tags.
	 */
	public function get_data() {
		/**
		 * If the URL has not been cached, then we will do the cURL and data
		 * parse.
		 */
		if ( empty( $this->data ) ) {
			if ( $this->curl() ) {
				$this->data = $this->parse_html();
			}
			else {
				$this->data = array();
			}
		}

		return $this->data;
	}

	/**
	 * Takes the HTML and retrieves the OpenGraph data from it.
	 */
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

		if ( false === $tags || 0 === $tags->length ) {
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

		$url_components = parse_url( $this->url );
		$opengraph['domain'] = $url_components['scheme'] . '://' . $url_components['host'];

		/**
		 * If the og:title is omitted (like Wikipedia), then set the Title key
		 * to be the title tag.
		 */
		if ( false === array_key_exists( 'title', $opengraph ) || empty( $opengraph['title'] ) ) {
			$tags = $doc->getElementsByTagName( 'title' );

			if ( 0 < $tags->length ) {
				$opengraph['title'] = $tags->item( 0 )->textContent;
			}
		}

		if ( false === array_key_exists( 'url', $opengraph ) || empty( $opengraph['url'] ) ) {
			$opengraph['url'] = $this->url;
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
