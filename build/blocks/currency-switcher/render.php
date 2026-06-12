<?php
/**
 * Currency Switcher Block — Server-side render.
 *
 * Mirrors core/navigation-submenu output exactly, including all Interactivity
 * API directives, so the currency switcher participates in the navigation
 * block's open/close behaviour without any extra JS.
 *
 * Variables provided by WordPress:
 *   $attributes  (array)    Block attributes.
 *   $content     (string)   Inner block content (unused — no inner blocks).
 *   $block       (WP_Block) Block instance, used for context.
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

// Full list: base currency first, then additional.
$all_currencies = array_merge( array( $base_currency => $base_currency ), (array) $currencies );

// Require at least two currencies.
if ( count( $all_currencies ) < 2 ) {
	return;
}

// Determine selected currency from cookie; fall back to base.
$current = $base_currency;
if ( isset( $_COOKIE['lsx_currencies_choice'] ) ) {
	$current = strtoupper( sanitize_key( $_COOKIE['lsx_currencies_choice'] ) );
}
if ( ! array_key_exists( $current, $all_currencies ) ) {
	$current = $base_currency;
}

// Non-selected currencies go in the submenu.
$submenu_currencies = array_filter(
	$all_currencies,
	function ( $code ) use ( $current ) {
		return strtoupper( sanitize_key( $code ) ) !== $current;
	},
	ARRAY_FILTER_USE_KEY
);

// ── Context from parent navigation block ─────────────────────────────────────
$nav_context = $block->context;

// Inline block_core_navigation_submenu_get_submenu_visibility() logic.
// Priority: legacy boolean openSubmenusOnClick → new submenuVisibility enum.
$deprecated_open_on_click = isset( $nav_context['openSubmenusOnClick'] ) ? $nav_context['openSubmenusOnClick'] : null;
if ( null !== $deprecated_open_on_click ) {
	$computed_visibility = ! empty( $deprecated_open_on_click ) ? 'click' : 'hover';
} else {
	$computed_visibility = isset( $nav_context['submenuVisibility'] ) ? $nav_context['submenuVisibility'] : 'hover';
}

$show_submenu_icon       = isset( $nav_context['showSubmenuIcon'] ) && $nav_context['showSubmenuIcon'];
$open_on_click           = 'click' === $computed_visibility;
$open_on_hover           = 'hover' === $computed_visibility;
$open_on_hover_and_click = $open_on_hover && $show_submenu_icon; // matches core logic exactly

// ── Font sizes from context ───────────────────────────────────────────────────
$font_size_classes = array();
$font_size_styles  = '';
if ( ! empty( $nav_context['fontSize'] ) ) {
	$font_size_classes[] = 'has-' . sanitize_html_class( $nav_context['fontSize'] ) . '-font-size';
} elseif ( ! empty( $nav_context['style']['typography']['fontSize'] ) ) {
	$font_size_styles = sprintf( 'font-size:%s;', esc_attr( $nav_context['style']['typography']['fontSize'] ) );
}

// ── <li> CSS classes (mirrors navigation-submenu exactly) ────────────────────
$li_classes = array_merge( array( 'wp-block-navigation-item', 'has-child' ), $font_size_classes );

if ( $open_on_click ) {
	$li_classes[] = 'open-on-click';
}
if ( $open_on_hover_and_click ) {
	$li_classes[] = 'open-on-hover-click';
}
if ( 'always' === $computed_visibility ) {
	$li_classes[] = 'open-always';
}
// 'wp-block-navigation-submenu' is the block CSS class for core/navigation-submenu;
// we add it so themes/plugins targeting that class also style our switcher.
$li_classes[] = 'wp-block-navigation-submenu';

// ── Interactivity API context JSON for core/navigation store ──────────────────
$wp_context_json = wp_json_encode(
	array(
		'submenuOpenedBy' => array(
			'click' => false,
			'hover' => false,
			'focus' => false,
		),
		'type'          => 'submenu',
		'modal'         => null,
		'previousFocus' => null,
	)
);

// ── Aria label ────────────────────────────────────────────────────────────────
$aria_label = sprintf(
	/* translators: %s: current currency code shown in the navigation. */
	__( '%s submenu', 'lsx-currencies' ),
	esc_html( $current )
);

// ── <li> wrapper attributes ───────────────────────────────────────────────────
// get_block_wrapper_attributes() also adds the block's own CSS class
// (wp-block-lsx-currencies-currency-switcher) which is used by view.js.
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'                    => implode( ' ', $li_classes ),
		'style'                    => $font_size_styles,
		// Interactivity API — all directives reference the core/navigation store
		// so this element participates in the navigation's hover/click/focus state machine.
		'data-wp-context'          => $wp_context_json,
		'data-wp-interactive'      => 'core/navigation',
		'data-wp-on--focusout'     => 'actions.handleMenuFocusout',
		'data-wp-on--keydown'      => 'actions.handleMenuKeydown',
		'data-wp-on--pointerenter' => 'actions.openMenuOnHover',
		'data-wp-on--pointerleave' => 'actions.closeMenuOnHover',
		'data-wp-watch'            => 'callbacks.initMenu',
		'tabindex'                 => '-1',
		// Data used by view.js for dynamic DOM updates when switching currency.
		'data-display-flags'       => $display_flags ? '1' : '0',
		'data-flag-position'       => $flag_position,
		'data-flag-relations'      => wp_json_encode( $flag_relations ),
	)
);

// ── SVG caret — identical to block_core_shared_navigation_render_submenu_icon() ──
$caret_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 12 12" fill="none" aria-hidden="true" focusable="false"><path d="M1.50002 4L6.00002 8L10.5 4" stroke-width="1.5"></path></svg>';

// ── Label helper ──────────────────────────────────────────────────────────────
$render_label = function ( $code ) use ( $display_flags, $flag_position, $flag_relations, $currency_symbols, $show_symbol ) {
	$code     = strtoupper( sanitize_key( $code ) );
	$flag_key = isset( $flag_relations[ $code ] ) ? $flag_relations[ $code ] : '';
	$flag     = ( $display_flags && $flag_key )
		? '<span class="flag-icon flag-icon-' . esc_attr( $flag_key ) . '" aria-hidden="true"></span>'
		: '';

	$label = '';
	if ( $display_flags && $flag_key && 'left' === $flag_position ) {
		$label .= $flag . ' ';
	}
	$label .= '<span class="wp-block-navigation-item__label">' . esc_html( $code );
	if ( $show_symbol && ! empty( $currency_symbols[ $code ] ) ) {
		$label .= ' <span class="lsx-currency-symbol" aria-hidden="true">' . esc_html( $currency_symbols[ $code ] ) . '</span>';
	}
	$label .= '</span>';
	if ( $display_flags && $flag_key && 'right' === $flag_position ) {
		$label .= ' ' . $flag;
	}
	return $label;
};

// ── Submenu <ul> CSS classes + overlay colours from context ───────────────────
// Matches the colour-class logic in navigation-submenu's wp_apply_colors_support() call.
$submenu_classes = array( 'wp-block-navigation__submenu-container', 'wp-block-navigation-submenu' );
$submenu_style   = '';

if ( ! empty( $nav_context['overlayTextColor'] ) ) {
	$submenu_classes[] = 'has-text-color';
	$submenu_classes[] = 'has-' . sanitize_html_class( $nav_context['overlayTextColor'] ) . '-color';
}
if ( ! empty( $nav_context['customOverlayTextColor'] ) ) {
	$submenu_classes[] = 'has-text-color';
	$submenu_style    .= 'color:' . esc_attr( $nav_context['customOverlayTextColor'] ) . ';';
}
if ( ! empty( $nav_context['overlayBackgroundColor'] ) ) {
	$submenu_classes[] = 'has-background';
	$submenu_classes[] = 'has-' . sanitize_html_class( $nav_context['overlayBackgroundColor'] ) . '-background-color';
}
if ( ! empty( $nav_context['customOverlayBackgroundColor'] ) ) {
	$submenu_classes[] = 'has-background';
	$submenu_style    .= 'background-color:' . esc_attr( $nav_context['customOverlayBackgroundColor'] ) . ';';
}
?>
<li <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() is safe. ?>>

	<?php if ( $open_on_click ) : ?>
		<?php /* open-on-click: top-level is a <button> that toggles the submenu */ ?>
		<button
			aria-label="<?php echo esc_attr( $aria_label ); ?>"
			class="wp-block-navigation-item__content wp-block-navigation-submenu__toggle"
			data-wp-bind--aria-expanded="state.isMenuOpen"
			data-wp-on--click="actions.toggleMenuOnClick"
			aria-expanded="false">
			<?php echo $render_label( $current ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</button>
		<?php if ( ! empty( $submenu_currencies ) ) : ?>
			<span class="wp-block-navigation__submenu-icon"><?php echo $caret_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>

	<?php else : ?>
		<?php /* open-on-hover / always: top-level is an <a>, optional caret <button> */ ?>
		<a class="wp-block-navigation-item__content" href="#<?php echo esc_attr( strtolower( $current ) ); ?>">
			<?php echo $render_label( $current ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</a>
		<?php if ( $show_submenu_icon && ! empty( $submenu_currencies ) ) : ?>
			<button
				aria-label="<?php echo esc_attr( $aria_label ); ?>"
				class="wp-block-navigation__submenu-icon wp-block-navigation-submenu__toggle"
				data-wp-bind--aria-expanded="state.isMenuOpen"
				data-wp-on--click="actions.toggleMenuOnClick"
				aria-expanded="false">
				<?php echo $caret_svg; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</button>
		<?php endif; ?>
	<?php endif; ?>

	<?php if ( ! empty( $submenu_currencies ) ) : ?>
		<ul
			data-wp-on--focus="actions.openMenuOnFocus"
			class="<?php echo esc_attr( implode( ' ', $submenu_classes ) ); ?>"
			<?php if ( $submenu_style ) : ?>style="<?php echo esc_attr( $submenu_style ); ?>"<?php endif; ?>>
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
