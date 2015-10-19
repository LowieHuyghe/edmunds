<?php

/**
 * Core
 *
 * The core of any web-project by Lowie Huyghe
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */

namespace Core\Controllers\Login;

/**
 * Controller to extend from which requires the user to log in
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class LoginRequiredController extends BaseController
{
	const	TYPE_LOGIN = 1,
			TYPE_BASIC = 2;

	/**
	 * Constructor
	 * @param int $type type of authentication
	 */
	function __construct($type = self::TYPE_LOGIN)
	{
		parent::__construct();

		$this->checkLogin($type);

		$this->response->assign('__login', $this->visitor->user);
	}

	/**
	 * Check if user is logged in
	 * @param int $type Type of authentication
	 */
	private function checkLogin($type = self::TYPE_LOGIN)
	{
		//If user is not logged in, redirect to other page
		if (!$this->visitor->loggedIn)
		{
			if ($type == self::TYPE_BASIC)
			{
				if ($email = $this->request->getServer('PHP_AUTH_USER') && $password = $this->request->getServer('PHP_AUTH_PW'))
				{
					$this->visitor->login($email, $password);
				}
				if (!$this->visitor->loggedIn)
				{
					$this->response->assignHeader('WWW-Authenticate', 'Basic' /*. 'realm="Comment"'*/);
					$this->response->response401('Invalid credentials.');
				}
			}
			else
			{
				$loginRoute = env('ROUTING_LOGINROUTE');
				$this->response->responseRedirect($loginRoute, null, true);
			}
		}
	}
}