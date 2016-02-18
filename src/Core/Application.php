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

namespace Core;
use Core\Analytics\Tracking\PageviewLog;
use Core\Database\Migrations\Migrator;
use Core\Http\Exceptions\AbortHttpException;
use Core\Auth\Auth;
use Core\Http\Client\Visitor;
use Core\Http\Request;
use Core\Http\Response;
use Core\Providers\HttpServiceProvider;
use Core\Registry;
use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Throwable;

/**
 * The structure for application
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.
 */
class Application extends \Laravel\Lumen\Application
{
	/**
	 * Get the name of the app
	 * @return bool
	 */
	public function getName()
	{
		return config('app.name');
	}

	/**
	 * Check if stateful
	 * @return bool
	 */
	public function isStateful()
	{
		return config('app.features.stateful', true);
	}

	/**
	 * Check if local environment
	 * @return bool
	 */
	public function isLocal()
	{
		return $this->environment('local');
	}

	/**
	 * Check if production environment
	 * @return bool
	 */
	public function isProduction()
	{
		return $this->environment('production');
	}

	/**
	 * Check if testing environment
	 * @return bool
	 */
	public function isTesting()
	{
		return $this->environment('testing');
	}

	/**
	 * Dispatch the incoming request.
	 *
	 * @param  SymfonyRequest|null $request
	 * @return Response
	 */
	public function dispatch($request = null)
	{
		try
		{
			if ($this->isDownForMaintenance())
			{
				abort(503);
			}

			$response = parent::dispatch($request);
		}
		catch (AbortHttpException $exception)
		{
			//
		}

		$this->logPageView(isset($exception) ? $exception : null);
		// and send them all
		Registry::warehouse()->flush();

		// attach extra's to response
		Response::getInstance()->attachExtras($response);

		return $response;;
	}

	/**
	 * Handle a route found by the dispatcher.
	 *
	 * @param  array  $routeInfo
	 * @return mixed
	 */
	protected function handleFoundRoute($routeInfo)
	{
		if (isset($routeInfo[1]['uses']))
		{
			list($controller, $method) = explode('@', $routeInfo[1]['uses']);

			// change method
			$routeInfo[1]['uses'] = implode('@', array($controller, 'responseFlow'));

			// change parameters
			$routeInfo[2] = array($method, $routeInfo[2]);
		}

		return parent::handleFoundRoute($routeInfo);
	}

	/**
	 * Throw an HttpException with the given data.
	 *
	 * @param  int     $code
	 * @param  string  $message
	 * @param  array   $headers
	 * @return void
	 *
	 * @throws \Symfony\Component\HttpKernel\Exception\HttpException
	 */
	public function abort($code, $message = '', array $headers = [])
	{
		switch($code)
		{
			case 200:
				throw new AbortHttpException($message);
			case 401:
				throw new UnauthorizedHttpException('Basic', $message);
			case 403:
				throw new AccessDeniedHttpException($message);
			case 404:
				throw new NotFoundHttpException($message);
			case 503:
				throw new ServiceUnavailableHttpException(null, $message);
			default:
				throw new HttpException($code, $message, null, $headers);
		}
	}

	/**
	 * Get the path to the given configuration file.
	 *
	 * If no name is provided, then we'll return the path to the config folder.
	 *
	 * @param  string|null  $name
	 * @return string
	 */
	public function getConfigurationPath($name = null)
	{
		if (!$name)
		{
			$appConfigDir = $this->basePath('config').'/';

			if (file_exists($appConfigDir))
			{
				return $appConfigDir;
			}
			elseif (file_exists($ath = CORE_BASE_PATH . '/config/'))
			{
				return $path;
			}
			elseif ($path = parent::getConfigurationPath($name))
			{
				return $path;
			}
		}
		else
		{
			$appConfigPath = $this->basePath('config').'/'.$name.'.php';

			if (file_exists($appConfigPath))
			{
				return $appConfigPath;
			}
			elseif (file_exists($path = CORE_BASE_PATH . "/config/$name.php"))
			{
				return $path;
			}
			elseif ($path = parent::getConfigurationPath($name))
			{
				return $path;
			}
		}
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerCookieBindings()
	{
		$this->singleton('cookie', function () {
			return $this->loadComponent('session', 'Illuminate\Cookie\CookieServiceProvider', 'cookie');
		});
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerSessionBindings()
	{
		$this->singleton('session', function () {
			return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider');
		});
		$this->singleton('session.store', function () {
			return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider', 'session.store');
		});
	}

	/**
     * Register an available binding with the application.
     *
     * @param  string  $abstract
     * @param  \Closure|string  $concrete
	 */
	public function bindAvailable($abstract, $concrete)
	{
        $abstract = $this->normalize($abstract);
        $concrete = $this->normalize($concrete);

        $this->availableBindings[$abstract] = $concrete;
	}

	/**
	 * Log the pageview
	 * @param Exception $exception
	 */
	protected function logPageView($exception = null)
	{
		if (!$this->runningInConsole())
		{
			$pageview = new PageviewLog();
			$pageview->title = $this->getName();

			$pageview->log();
		}
	}

}
