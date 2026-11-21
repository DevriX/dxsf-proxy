<?php
/**
 * Signed requests between the proxy and the Stability Framework core.
 *
 * The same scheme is used in both directions: the proxy signs its pushes to the
 * core, and the core signs its pulls from the proxy. The site key never travels
 * in a request, only the HMAC made with it.
 *
 * @link       https://devrix.com
 * @since      3.0.0
 *
 * @package    Dxsf_Proxy
 * @subpackage Dxsf_Proxy/includes/classes
 * @author     DevriX <contact@devrix.com>
 */

namespace Dxsf_proxy;

/**
 * Signature class.
 */
class Signature {

	/**
	 * How far a request timestamp may drift from the local clock, in seconds.
	 *
	 * @var int
	 */
	const WINDOW = 300;

	/**
	 * Build the string that gets signed for a request.
	 *
	 * @param string $method    HTTP method.
	 * @param string $path      The path for core routes, the REST route for proxy routes.
	 * @param array  $query     Query parameters.
	 * @param string $timestamp Unix timestamp as a string.
	 * @param string $nonce     Random one-time value.
	 * @param string $body      Raw request body.
	 * @return string
	 */
	public static function canonical_request( $method, $path, $query, $timestamp, $nonce, $body ) {
		return implode(
			"\n",
			array(
				strtoupper( $method ),
				'/' . ltrim( $path, '/' ),
				self::canonical_query( $query ),
				$timestamp,
				$nonce,
				hash( 'sha256', (string) $body ),
			)
		);
	}

	/**
	 * Sorted, RFC 3986 encoded query string.
	 *
	 * @param array $query Query parameters.
	 * @return string
	 */
	public static function canonical_query( $query ) {
		if ( empty( $query ) || ! is_array( $query ) ) {
			return '';
		}

		ksort( $query );

		return http_build_query( $query, '', '&', PHP_QUERY_RFC3986 );
	}

	/**
	 * Build the string that gets signed for a response.
	 *
	 * Bound to the request nonce, so an old response cannot be replayed.
	 *
	 * @param string $request_nonce The nonce of the request being answered.
	 * @param string $timestamp     Unix timestamp as a string.
	 * @param string $body          Raw response body.
	 * @return string
	 */
	public static function canonical_response( $request_nonce, $timestamp, $body ) {
		return implode( "\n", array( 'RESPONSE', $request_nonce, $timestamp, hash( 'sha256', (string) $body ) ) );
	}

	/**
	 * HMAC-SHA256 of a canonical string.
	 *
	 * @param string $canonical The canonical string.
	 * @param string $key       The site key.
	 * @return string Hex digest.
	 */
	public static function sign( $canonical, $key ) {
		return hash_hmac( 'sha256', $canonical, $key );
	}

	/**
	 * Headers for an outgoing signed request.
	 *
	 * @param string $site_id The site id issued by the core.
	 * @param string $key     The site key.
	 * @param string $method  HTTP method.
	 * @param string $path    Request path.
	 * @param array  $query   Query parameters.
	 * @param string $body    Raw request body.
	 * @return array Headers, with the nonce under the key 'X-DXSF-Nonce'.
	 */
	public static function request_headers( $site_id, $key, $method, $path, $query, $body ) {
		$timestamp = (string) time();
		$nonce     = bin2hex( random_bytes( 16 ) );

		return array(
			'X-DXSF-Site'      => $site_id,
			'X-DXSF-Timestamp' => $timestamp,
			'X-DXSF-Nonce'     => $nonce,
			'X-DXSF-Signature' => self::sign( self::canonical_request( $method, $path, $query, $timestamp, $nonce, $body ), $key ),
		);
	}

	/**
	 * Whether a timestamp is inside the allowed window.
	 *
	 * @param string $timestamp Unix timestamp as a string.
	 * @return bool
	 */
	public static function fresh( $timestamp ) {
		if ( ! is_numeric( $timestamp ) ) {
			return false;
		}

		return abs( time() - (int) $timestamp ) <= self::WINDOW;
	}

	/**
	 * Verify a signature in constant time.
	 *
	 * @param string $canonical The canonical string.
	 * @param string $key       The site key.
	 * @param string $signature The signature received.
	 * @return bool
	 */
	public static function verify( $canonical, $key, $signature ) {
		if ( empty( $key ) || ! is_string( $signature ) || '' === $signature ) {
			return false;
		}

		return hash_equals( self::sign( $canonical, $key ), $signature );
	}
}
