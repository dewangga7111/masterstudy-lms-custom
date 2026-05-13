<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MSLC_OpenEdX_Client {

	private string $base_url;

	public function __construct() {
		$this->base_url = defined( 'MSLC_OPENEDX_URL' ) ? rtrim( MSLC_OPENEDX_URL, '/' ) : '';
	}

	public function is_configured(): bool {
		return ! empty( $this->base_url );
	}

	/**
	 * Fetch paginated course list.
	 *
	 * @param int $page     1-based page number
	 * @param int $per_page Items per page
	 * @return array|WP_Error
	 */
	public function get_courses( int $page = 1, int $per_page = 12 ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', 'Open edX base URL is not set.' );
		}

		$url = add_query_arg( [
			'page'      => $page,
			'page_size' => $per_page,
		], $this->base_url . '/api/courses/v1/courses/' );

		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'api_error', "Open edX API returned HTTP {$code}." );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! isset( $body['results'] ) ) {
			return new WP_Error( 'parse_error', 'Unexpected API response format.' );
		}

		return [
			'courses'    => array_map( [ $this, 'normalize_course' ], $body['results'] ),
			'count'      => (int) ( $body['pagination']['count'] ?? 0 ),
			'num_pages'  => (int) ( $body['pagination']['num_pages'] ?? 1 ),
			'next'       => $body['pagination']['next'] ?? null,
			'previous'   => $body['pagination']['previous'] ?? null,
		];
	}

	/**
	 * Fetch a single course by course key.
	 *
	 * @param string $course_key e.g. course-v1:Org+Number+Run
	 * @return array|WP_Error
	 */
	public function get_course( string $course_key ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'not_configured', 'Open edX base URL is not set.' );
		}

		$url      = $this->base_url . '/api/courses/v1/courses/' . urlencode( $course_key ) . '/';
		$response = wp_remote_get( $url, [ 'timeout' => 15 ] );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			return new WP_Error( 'api_error', "Open edX API returned HTTP {$code}." );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		return $this->normalize_course( $body );
	}

	private function normalize_course( array $raw ): array {
		$image_large = $raw['media']['image']['large'] ?? '';
		$image_small = $raw['media']['image']['small'] ?? '';

		// Prefer large, fall back to small, then banner
		$thumbnail = $image_large ?: $image_small ?: ( $raw['media']['banner_image']['uri_absolute'] ?? '' );

		// Build course URL on Open edX
		$course_url = '';
		if ( ! empty( $raw['id'] ) ) {
			$course_url = $this->base_url . '/courses/' . rawurlencode( $raw['id'] ) . '/about';
		}

		return [
			'id'                => $raw['id'] ?? '',
			'course_id'         => $raw['course_id'] ?? $raw['id'] ?? '',
			'name'              => $raw['name'] ?? '',
			'org'               => $raw['org'] ?? '',
			'number'            => $raw['number'] ?? '',
			'short_description' => $raw['short_description'] ?? '',
			'start'             => $raw['start'] ?? '',
			'start_display'     => $raw['start_display'] ?? '',
			'end'               => $raw['end'] ?? '',
			'pacing'            => $raw['pacing'] ?? '',
			'effort'            => $raw['effort'] ?? '',
			'thumbnail'         => $thumbnail,
			'course_url'        => $course_url,
		];
	}
}
