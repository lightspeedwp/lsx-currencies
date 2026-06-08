<?php
/**
 * LSX Currencies Admin Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @copyright 2024 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Manages the admin settings page integrated under the Tour Operator menu.
 */
class Admin {

	/**
	 * Singleton instance.
	 *
	 * @var \lsx\currencies\classes\Admin
	 */
	private static $instance;

	/**
	 * Option key used to store all plugin settings.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'lsx_currencies_settings';

	/**
	 * Nonce action used when saving settings.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'lsx_currencies_settings_save';

	/**
	 * Nonce field name.
	 *
	 * @var string
	 */
	const NONCE_FIELD = 'lsx_currencies_nonce';

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 110 );
		add_action( 'admin_init', array( $this, 'save_settings' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/**
	 * Return singleton instance.
	 *
	 * @return \lsx\currencies\classes\Admin
	 */
	public static function init() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register the Currencies submenu under the Tour Operator admin menu.
	 * Falls back to the Settings menu when Tour Operator is not active.
	 */
	public function add_settings_page() {
		$parent = function_exists( 'tour_operator' ) ? 'tour-operator' : 'options-general.php';

		add_submenu_page(
			$parent,
			esc_html__( 'Currency Settings', 'lsx-currencies' ),
			esc_html__( 'Currencies', 'lsx-currencies' ),
			'manage_options',
			'lsx-currencies-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Enqueue admin-only CSS on our settings page.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'lsx-currencies-settings' ) ) {
			return;
		}
		wp_enqueue_style(
			'lsx-currencies-admin',
			LSX_CURRENCIES_URL . 'assets/css/lsx-currencies-admin.css',
			array(),
			LSX_CURRENCIES_VER
		);
	}

	/**
	 * Output the settings page HTML.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'lsx-currencies' ) );
		}

		$options    = get_option( self::OPTION_KEY, array() );
		$currencies = lsx_currencies()->get_available_currencies();
		?>
		<div class="wrap lsx-currencies-settings">
			<h1><?php esc_html_e( 'Currency Settings', 'lsx-currencies' ); ?></h1>

			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['settings-updated'] ) && '1' === sanitize_key( $_GET['settings-updated'] ) ) :
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Settings saved.', 'lsx-currencies' ); ?></p>
				</div>
			<?php endif; ?>

			<form method="post" action="">
				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<h2><?php esc_html_e( 'General', 'lsx-currencies' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<?php echo wp_kses_post( $this->base_currency_field( $options, $currencies ) ); ?>
						<?php echo wp_kses_post( $this->additional_currencies_field( $options, $currencies ) ); ?>
						<?php echo wp_kses_post( $this->checkbox_field( $options, 'multi_price', esc_html__( 'Enable Multiple Prices', 'lsx-currencies' ), esc_html__( 'Allow per-currency pricing on tour posts (uses the additional_prices post meta).', 'lsx-currencies' ) ) ); ?>
						<?php echo wp_kses_post( $this->checkbox_field( $options, 'convert_to_single', esc_html__( 'Convert to Single Currency', 'lsx-currencies' ), esc_html__( 'Convert all prices to the base currency using live exchange rates.', 'lsx-currencies' ) ) ); ?>
						<?php echo wp_kses_post( $this->checkbox_field( $options, 'remove_decimals', esc_html__( 'Remove Decimals', 'lsx-currencies' ), esc_html__( 'Round all displayed prices to the nearest whole number.', 'lsx-currencies' ) ) ); ?>
					</tbody>
				</table>

				<h2><?php esc_html_e( 'Exchange Rate API', 'lsx-currencies' ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<?php echo wp_kses_post( $this->text_field( $options, 'openexchange_api', esc_html__( 'OpenExchangeRates API Key', 'lsx-currencies' ), esc_html__( 'Enter your API key to unlock additional currencies. Leave blank to use the free exchange rate endpoint.', 'lsx-currencies' ) ) ); ?>
					</tbody>
				</table>

				<?php submit_button( esc_html__( 'Save Settings', 'lsx-currencies' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle settings save on admin_init.
	 */
	public function save_settings() {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			wp_die( esc_html__( 'Security check failed.', 'lsx-currencies' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to save these settings.', 'lsx-currencies' ) );
		}

		$saved = array(
			'base_currency'         => isset( $_POST['base_currency'] ) ? sanitize_key( wp_unslash( $_POST['base_currency'] ) ) : 'USD',
			'additional_currencies' => '',
			'multi_price'           => isset( $_POST['multi_price'] ) ? '1' : '0',
			'convert_to_single'     => isset( $_POST['convert_to_single'] ) ? '1' : '0',
			'remove_decimals'       => isset( $_POST['remove_decimals'] ) ? '1' : '0',
			'openexchange_api'      => isset( $_POST['openexchange_api'] ) ? sanitize_text_field( wp_unslash( $_POST['openexchange_api'] ) ) : '',
		);

		// Additional currencies come in as an array of checked currency codes.
		if ( ! empty( $_POST['additional_currencies'] ) && is_array( $_POST['additional_currencies'] ) ) {
			$allowed                        = array_keys( lsx_currencies()->get_available_currencies() );
			$checked                        = array_map( 'sanitize_key', wp_unslash( $_POST['additional_currencies'] ) );
			$valid                          = array_intersect( $checked, $allowed );
			$saved['additional_currencies'] = implode( ',', $valid );
		}

		update_option( self::OPTION_KEY, $saved );

		$redirect = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : admin_url( 'admin.php?page=lsx-currencies-settings' );
		wp_safe_redirect( add_query_arg( 'settings-updated', '1', $redirect ) );
		exit;
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	/**
	 * Renders the base currency select field.
	 *
	 * @param array $options    Saved options.
	 * @param array $currencies Available currencies.
	 * @return string
	 */
	private function base_currency_field( array $options, array $currencies ) {
		$current = isset( $options['base_currency'] ) ? $options['base_currency'] : 'USD';
		$html    = '<tr class="form-field base-currency">';
		$html   .= '<th scope="row"><label for="base_currency">' . esc_html__( 'Base Currency', 'lsx-currencies' ) . '</label></th>';
		$html   .= '<td><select name="base_currency" id="base_currency">';
		foreach ( $currencies as $code => $label ) {
			$html .= '<option value="' . esc_attr( $code ) . '"' . selected( $current, $code, false ) . '>' . esc_html( $code . ' — ' . $label ) . '</option>';
		}
		$html .= '</select>';
		$html .= '<br /><small>' . esc_html__( 'The default currency used for pricing.', 'lsx-currencies' ) . '</small>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 * Renders the additional currencies checkboxes.
	 *
	 * @param array $options    Saved options.
	 * @param array $currencies Available currencies.
	 * @return string
	 */
	private function additional_currencies_field( array $options, array $currencies ) {
		$saved_raw = isset( $options['additional_currencies'] ) ? $options['additional_currencies'] : '';
		$saved     = array_filter( array_map( 'sanitize_key', explode( ',', $saved_raw ) ) );

		$html  = '<tr class="form-field additional-currencies">';
		$html .= '<th scope="row">' . esc_html__( 'Additional Currencies', 'lsx-currencies' ) . '</th>';
		$html .= '<td>';

		foreach ( $currencies as $code => $label ) {
			$checked = in_array( $code, $saved, true ) ? 'checked="checked"' : '';
			$html   .= '<label style="display:block;margin-bottom:4px;">';
			$html   .= '<input type="checkbox" name="additional_currencies[]" value="' . esc_attr( $code ) . '" ' . $checked . ' /> ';
			$html   .= esc_html( $code . ' — ' . $label );
			$html   .= '</label>';
		}

		$html .= '<br /><small>' . esc_html__( 'Currencies visitors can switch to using the Currency Switcher block.', 'lsx-currencies' ) . '</small>';
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 * Renders a generic checkbox field.
	 *
	 * @param array  $options Saved options.
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $desc    Description text.
	 * @return string
	 */
	private function checkbox_field( array $options, $key, $label, $desc = '' ) {
		$checked = ! empty( $options[ $key ] ) ? 'checked="checked"' : '';
		$html    = '<tr class="form-field ' . esc_attr( $key ) . '">';
		$html   .= '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
		$html   .= '<td>';
		$html   .= '<input type="checkbox" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="1" ' . $checked . ' />';
		if ( $desc ) {
			$html .= '<br /><small>' . esc_html( $desc ) . '</small>';
		}
		$html .= '</td></tr>';
		return $html;
	}

	/**
	 * Renders a generic text input field.
	 *
	 * @param array  $options Saved options.
	 * @param string $key     Option key.
	 * @param string $label   Field label.
	 * @param string $desc    Description text.
	 * @return string
	 */
	private function text_field( array $options, $key, $label, $desc = '' ) {
		$value = isset( $options[ $key ] ) ? $options[ $key ] : '';
		$html  = '<tr class="form-field ' . esc_attr( $key ) . '">';
		$html .= '<th scope="row"><label for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label></th>';
		$html .= '<td>';
		$html .= '<input type="text" name="' . esc_attr( $key ) . '" id="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" />';
		if ( $desc ) {
			$html .= '<br /><small>' . esc_html( $desc ) . '</small>';
		}
		$html .= '</td></tr>';
		return $html;
	}
}
