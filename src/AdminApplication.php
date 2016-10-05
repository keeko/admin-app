<?php
namespace keeko\application\admin;

use keeko\framework\foundation\AbstractApplication;
use Symfony\Component\HttpFoundation\Request;

/**
 * Keeko Admin Application
 * 
 * @license MIT
 * @author gossi
 */
class AdminApplication extends AbstractApplication {
	
	const EXT_MENU = 'keeko.account.menu';

	/**
	 * @param Request $request
	 * @param string $path
	 */
	public function run(Request $request, $path) {
		
	}
}
