<?php
/**
 * The utilities functionalities
 *
 * @package redeem-code
 */

namespace Solidie_Redeem\Helpers;

use Solidie_Redeem\Setup\AdminPage;
use SolidieLib\Utilities as LibUtils;

/**
 * The class
 */
class Utilities extends LibUtils {

	/**
	 * Check if the page is a Crew Dashboard
	 *
	 * @param string $sub_page Optional sub page name to match too
	 * @return boolean
	 */
	public static function isAdminDashboard() {
		return self::isAdminScreen( 'woocommerce', AdminPage::MENU_SLUG );
	}

	/**
	 * Wrapper function for gmdate('Y-m-d H:i:s')
	 *
	 * @return string
	 */
	public static function gmDate() {
		return gmdate( 'Y-m-d H:i:s' );
	}
}
