<?php

/**
 * Core
 *
 * The core of any web-project by Lowie Huyghe
 *
 * @author      Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright   Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license     http://LicenseUrl
 * @since       Version 0.1
 */

namespace Core\Auth\Middleware;

use Core\Auth\Auth;
use Core\Auth\Guards\BasicStatefulGuard;
use Core\Auth\Guards\BasicStatelessGuard;
use Core\Bases\Http\Middleware\BaseMiddleware;
use Core\Http\Request;
use Core\Http\Response;

/**
 * Middleware for authentication
 *
 * @author      Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright   Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license     http://LicenseUrl
 * @since       Version 0.1
 */
class AuthMiddleware extends BaseMiddleware
{
	/**
	 * Handle an incoming request.
	 * @param  \Illuminate\Http\Request $r
	 * @param  \Closure  $next
     * @param  string|null  $guard
	 * @return mixed
	 */
	public function handle($r, \Closure $next, $guard = null)
	{
		$auth = Auth::getInstance();
		$request = Request::getInstance();
		$response = Response::getInstance();

		// check if guest
		if (!$auth->loggedIn)
		{
			$guard = $auth->getGuard();
			if ($guard instanceof BasicStatefulGuard
				|| $guard instanceof BasicStatelessGuard)
			{
				abort(401);
			}
			elseif ($request->ajax
				|| $request->json
				|| $request->xml)
			{
				abort(403);
			}
			else
			{
				$response->redirect(config('app.routing.loginroute'), true);
			}
		}

		return $next($r);
	}
}