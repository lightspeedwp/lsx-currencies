<?php
/**
 * LSX Currencies Template Tags
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

/**
 * Returns the currency-wrapped HTML for a given price value.
 * Useful for theme template files that display prices outside of tour custom field queries.
 *
 * @param string    $value   Raw price string (numeric, may contain symbols).
 * @param int|false $post_id Post ID for currency override lookup. Defaults to current post.
 * @param array     $args    Options. 'currency_tag' (bool) — show/hide currency code label.
 * @return string
 */
function lsx_currencies_get_price_html( $value = '', $post_id = false, $args = array() ) {
	if ( ! function_exists( 'lsx_currencies' ) ) {
		return '';
	}

	$value = preg_replace( '/[^0-9.]+/', '', $value );

	// Handle multiple decimal points — keep only the last one.
	$decimal_count = substr_count( $value, '.' );
	if ( $decimal_count > 1 ) {
		$value = preg_replace( '/' . preg_quote( '.', '/' ) . '/', '', $value, $decimal_count - 1 );
	}

	if ( false === $post_id ) {
		$post_id = get_the_ID();
	}

	$args = wp_parse_args(
		$args,
		array( 'currency_tag' => true )
	);

	$money_format = lsx_currencies()->remove_decimals ? 0 : 2;

	// Determine the applicable currency for this post.
	$currency      = lsx_currencies()->base_currency;
	$tour_currency = get_post_meta( (int) $post_id, 'currency', true );
	if ( ! empty( $tour_currency ) ) {
		$currency = strtoupper( sanitize_key( $tour_currency ) );
	}

	// Currency label span.
	if ( ! empty( $args['currency_tag'] ) ) {
		$currency_span = '<span class="currency-icon ' . esc_attr( strtolower( $currency ) ) . '">' . esc_html( $currency ) . '</span>';
	} else {
		$currency_span = '<span class="currency-icon ' . esc_attr( strtolower( $currency ) ) . '"></span>';
	}

	$price_float      = (float) $value;
	$formatted_amount = number_format( $price_float, $money_format );

	$amount_span = '<span class="value" data-price-' . esc_attr( $currency ) . '="' . esc_attr( $price_float ) . '">'
		. esc_html( $formatted_amount )
		. '</span>';

	return '<span class="amount lsx-currencies">' . $currency_span . $amount_span . '</span>';
}
