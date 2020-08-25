<?php
/**
 * LSX Currency FacetWP Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2019 LightSpeed
 */

namespace lsx\currencies\classes;

/**
 * Holds the WooCommerce Integrations
 */
class FacetWP {

	/**
	 * Holds instance of the class
	 *
	 * @var object \lsx\currencies\classes\FacetWP()
	 */
	private static $instance;

	/**
	 * Holds the current currency.
	 *
	 * @var boolean
	 */
	public $currency = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'facetwp_indexer_row_data', array( $this, 'facetwp_index_row_data' ), 20, 2 );
		add_action( 'lsx_currencies_rates_refreshed', array( $this, 'refresh_the_currencies' ), 20 );
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return  object
	 */
	public static function init() {
		// If the single instance hasn't been set, set it now.
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 *  Alter the rows and include extra facets rows for the continents.
	 */
	public function facetwp_index_row_data( $rows, $params ) {
		switch ( $params['facet']['source'] ) {
			case 'cf/price':
				// only convert a price to the base currency if the setting is active.
				// If $rows is empty then there is no base currency set.
				if ( true === lsx_currencies()->convert_to_single && empty( $rows ) ) {
					lsx_currencies()->frontend->set_defaults();
					$additional_prices = get_post_meta( $params['defaults']['post_id'], 'additional_prices', false );

					if ( ! empty( $additional_prices ) && isset( $additional_prices[0] ) && ! empty( lsx_currencies()->frontend->rates ) ) {
						$row_currency     = $additional_prices[0]['currency'];
						$row_value        = $additional_prices[0]['amount'];
						$current_currency = lsx_currencies()->frontend->current_currency;
						$usd_value        = $row_value / lsx_currencies()->frontend->rates->$row_currency;
						if ( $row_currency !== $current_currency ) {
							$usd_value = $usd_value * lsx_currencies()->frontend->rates->$current_currency;
						}
						$new_row                        = $params['defaults'];
						$new_row['facet_value']         = round( $usd_value, 0 );
						$new_row['facet_display_value'] = round( $usd_value, 0 );
						$rows[]                         = $new_row;
					}
				}
				break;

			default:
				break;
		}
		return $rows;
	}

	/**
	 * This will refresh the saved currencies that ar not the same as the base currency.
	 *
	 * @return void
	 */
	public function refresh_the_currencies() {
		if ( true === lsx_currencies()->convert_to_single ) {
			add_action( 'wp_footer', array( $this, 'trigger_the_index' ) );
		}
	}

	/**
	 * Grabs the tour ids and runs them through the index.
	 *
	 * @return void
	 */
	public function trigger_the_index() {
		$tours_args  = array(
			'post_type'      => 'tour',
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
			'nopagin'        => true,
			'fields'         => 'ids',
		);
		$tours_query = new \WP_Query( $tours_args );
		if ( $tours_query->have_posts() ) {
			foreach ( $tours_query->posts as $tour_id ) {
				FWP()->indexer->index( $tour_id );
			}
		}
	}
}
