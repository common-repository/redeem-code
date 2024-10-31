<?php
/**
 * App initiator class
 *
 * @package redeem-code
 */

namespace Solidie_Redeem;

use Solidie_Redeem\Controllers\CodeController;
use SolidieLib\_Array;
use SolidieLib\Dispatcher;

use Solidie_Redeem\Setup\AdminPage;
use Solidie_Redeem\Helpers\Utilities;
use Solidie_Redeem\Setup\Scripts;
use Solidie_Redeem\Setup\Shortcode;
use SolidieLib\DB;

/**
 * Main class to initiate app
 */
class Main {
	/**
	 * Configs array
	 *
	 * @var object
	 */
	public static $configs;

	function __construct() {
		add_action( 'plugins_loaded', array( $this, 'registerControllers' ), 101 );
	}

	/**
	 * Initialize Plugin
	 *
	 * @param object $configs Plugin configs for start up
	 *
	 * @return void
	 */
	public function init( object $configs ) {

		// Store configs in runtime static property
		self::$configs           = $configs;
		self::$configs->dir      = dirname( $configs->file ) . '/';
		self::$configs->basename = plugin_basename( $configs->file );

		// Retrieve plugin info from index
		$manifest      = _Array::getManifestArray( $configs->file, ARRAY_A );
		self::$configs = (object) array_merge( $manifest, (array) self::$configs );

		// Prepare the unique app name
		self::$configs->app_id   = Utilities::getAppId( self::$configs->url );
		self::$configs->sql_path = self::$configs->dir . 'dist/libraries/db.sql';
		self::$configs->activation_hook = 'redeem_code_activated';

		// Register Activation/Deactivation Hook
		register_activation_hook( self::$configs->file, array( $this, 'activate' ) );

		// Core Modules
		new DB( self::$configs );
		new AdminPage();
		new Scripts();
		new Shortcode();
	}

	/**
	 * Register controller methods
	 *
	 * @return void
	 */
	public function registerControllers() {

		new Dispatcher(
			self::$configs->app_id,
			array(
				CodeController::class
			)
		);
	}

	/**
	 * Execute activation hook
	 *
	 * @return void
	 */
	public static function activate() {
		do_action( 'redeem_code_activated' );
	}
}
