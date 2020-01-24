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
	 *	Alter the rows and include extra facets rows for the continents.
	 */
	public function facetwp_index_row_data( $rows, $params ) {
		switch ( $params['facet']['source'] ) {
			case 'cf/price':
				print_r('<pre>');
				print_r($params);
				print_r($rows);
				print_r('</pre>');
				foreach ( $rows as $r_index => $row ) {
					$parent                        = wp_get_post_parent_id( $row['facet_value'] );
					$rows[ $r_index ]['parent_id'] = $parent;
				}
				break;

			default:
				break;
		}

		return $rows;
	}
}
