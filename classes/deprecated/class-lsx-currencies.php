<?php
/**
 * LSX Currency Deprecated Main Class
 *
 * @package   LSX Currencies
 * @author    LightSpeed
 * @license   GPL3
 * @link
 * @copyright 2019 LightSpeed
 */

/**
 * The main class
 */
class LSX_Currencies {

	/**
	 * A wrapper for the deprecated class.
	 *
	 * @var object
	 */
	public $class = '';

	/**
	 * Holds instance of the class
	 *
	 * @var object \lsx\currencies\classes\Currencies()
	 */
	private static $instance;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->class = lsx_currencies();
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
}
