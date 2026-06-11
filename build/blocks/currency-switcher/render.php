<?php
/**
 * Currency Switcher Block — Server-side render.
 *
 * Outputs HTML that mirrors core/navigation-submenu so the currency switcher
 * integrates seamlessly into the navigation block's existing styling and
 * interactive submenu behaviour.
 *
 * Variables provided by register_block_type():
 *   $attributes  (array)   Block attributes.
 *   $content     (string)  Inner block content (unused).
 *   $block       (WP_Block) Block instance (used for context).
 *
 * @package LSX Currencies
 */

if ( ! function_exists( 'lsx_currencies' ) ) {
	return;
}

/* @var WP_Block $block WordPress passes this variable automatically for render.php files. */

$currencies       = lsx_currencies()->additional_currencies;
$base_currency    = lsx_currencies()->base_currency;
$flag_relations   = lsx_currencies()->flag_relations;
$currency_symbols = lsx_currencies()->currency_symbols;
$display_flags    = ! empty( $attributes['displayFlags'] );
$flag_position    = isset( $attributes['flagPosition'] ) ? sanitize_key( $attributes['flagPosition'] ) : 'left';
$show_symbol      = ! empty( $attributes['showSymbol'] );

// Build the full list: base first, then additional currencies.
$all_currencies = array_merge( array( $base_currency => $base_currency ), (array) $currencies );

// Need at least two currencies to show a switcher.
if ( count( $all_currencies ) < 2 ) {
	return;
}

// Determine currently selected currency from cookie.
$current = $base_currency;
if ( isset( $_COOKIE['lsx_currencies_choice'] ) ) {
	$current = strtoupper( sanitize_key( $_COOKIE['lsx_currencies_choice'] ) );
}
// Ensure the selected currency is in our list; fall back to base.
if ( ! array_key_exists( $current, $all_currencies ) ) {
	$current = $base_currency;
}

// Build the list of non-selected (submenu) currencies.
$submenu_currencies = array_filter(
	$all_currencies,
	function ( $code ) use ( $current ) {
		return strtoupper( sanitize_key( $code ) ) !== $current;
	},
	ARRAY_FILTER_USE_KEY
);

// ── Context from the parent navigation block ──────────────────────────────────
$context           = $block->context;
$open_on_click     = ! empty( $context['openSubmenusOnClick'] );
$show_submenu_icon = isset( $context['showSubmenuIcon'] ) ? (bool) $context['showSubmenuIcon'] : true;

// ── Wrapper <li> classes (mirrors navigation-submenu) ────────────────────────
$li_classes = array( 'wp-block-navigation-item', 'has-child' );

if ( $open_on_click ) {
	$li_classes[] = 'open-on-click';
} else {
	$li_classes[] = 'open-on-hover-click';
}

// Inherit colour/typography classes from navigation context where possible.
if ( ! empty( $context['textColor'] ) ) {
	$li_classes[] = 'has-text-color';
	$li_classes[] = 'has-' . sanitize_html_class( $context['textColor'] ) . '-color';
}
if ( ! empty( $context['backgroundColor'] ) ) {
	$li_classes[] = 'has-background';
	$li_classes[] = 'has-' . sanitize_html_class( $context['backgroundColor'] ) . '-background-color';
}
if ( ! empty( $context['fontSize'] ) ) {
	$li_classes[] = 'has-' . sanitize_html_class( $context['fontSize'] ) . '-font-size';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => implode( ' ', $li_classes ),
		'data-display-flags'  => $display_flags ? '1' : '0',
		'data-flag-position'  => esc_attr( $flag_position ),
		'data-flag-relations' => wp_json_encode( $flag_relations ),
	)
);

// ── SVG caret (identical to core/navigation-submenu) ─────────────────────────
$caret_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" role="presentation" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"/></svg>';

// ── Helper: render a currency label with optional flag and/or symbol ─────────
$render_label = function ( $code ) use ( $display_flags, $flag_position, $flag_relations, $currency_symbols, $show_symbol ) {
	$code     = strtoupper( sanitize_key( $code ) );
	$flag_key = isset( $flag_relations[ $code ] ) ? $flag_relations[ $code ] : '';
	$flag     = ( $display_flags && $flag_key )
		? '<span class="flag-icon flag-icon-' . esc_attr( $flag_key ) . '" aria-hidden="true"></span>'
		: '';

	$symbol_html = '';
	if ( $show_symbol && ! empty( $currency_symbols[ $code ] ) ) {
		$symbol_html = '<span class="lsx-currency-symbol" aria-hidden="true">' . esc_html( $currency_symbols[ $code ] ) . '</span>';
	}

	$label = '';
	if ( $display_flags && $flag_key && 'left' === $flag_position ) {
		$label .= $flag . ' ';
	}
	$label .= '<span class="wp-block-navigation-item__label">' . esc_html( $code );
	if ( $show_symbol ) {
		$label .= ' ' . $symbol_html;
	}
	$label .= '</span>';
	if ( $display_flags && $flag_key && 'right' === $flag_position ) {
		$label .= ' ' . $flag;
	}
	return $label;
};
?>
<li <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is safe. ?>>

	<?php if ( $open_on_click ) : ?>
		<?php /* Open-on-click: top-level is a button, caret is a separate <span> */ ?>
		<button class="wp-block-navigation-item__content wp-block-navigation-submenu__toggle"
			aria-label="<?php esc_attr_e( 'Currency switcher', 'lsx-currencies' ); ?>"
			aria-expanded="false"
			aria-haspopup="true">
			<?php echo $render_label( $current ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<?php if ( $show_submenu_icon ) : ?>
			<span class="wp-block-navigation__submenu-icon">
				<?php echo $caret_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</span>
		<?php endif; ?>

	<?php else : ?>
		<?php /* Open-on-hover / always: top-level is an <a>, caret is a <button> */ ?>
		<a class="wp-block-navigation-item__content"
			href="#<?php echo esc_attr( strtolower( $current ) ); ?>">
			<?php echo $render_label( $current ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<?php if ( $show_submenu_icon ) : ?>
			<button class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle"
				aria-label="<?php esc_attr_e( 'Currency switcher', 'lsx-currencies' ); ?>"
				aria-expanded="false"
				aria-haspopup="true">
				<?php echo $caret_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		<?php endif; ?>

	<?php endif; ?>

	<?php if ( ! empty( $submenu_currencies ) ) : ?>
		<ul class="wp-block-navigation__submenu-container">
			<?php foreach ( $submenu_currencies as $code => $code_val ) :
				$code = strtoupper( sanitize_key( $code ) );
				?>
				<li class="wp-block-navigation-item wp-block-navigation-link">
					<a class="wp-block-navigation-item__content"
						href="#<?php echo esc_attr( strtolower( $code ) ); ?>"
						data-lsx-currency="<?php echo esc_attr( $code ); ?>">
						<?php echo $render_label( $code ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

</li>
