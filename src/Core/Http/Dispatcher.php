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

namespace Core\Http;

use Core\Http\Client\Input;
use Core\Http\Client\Visitor;
use Core\Http\Controllers\Login\LoginRequiredController;
use Core\Http\Middleware\AuthMiddleware;
use Core\Http\Middleware\RightsMiddleware;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Route;
use Core\Registry;
use Illuminate\View\View;

/**
 * The helper responsible for the routing
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class Dispatcher implements \FastRoute\Dispatcher
{
	/**
	 * Dispatches against the provided HTTP method verb and URI.
	 *
	 * Returns array with one of the following formats:
	 *
	 *     [self::NOT_FOUND]
	 *     [self::METHOD_NOT_ALLOWED, ['GET', 'OTHER_ALLOWED_METHODS']]
	 *     [self::FOUND, $handler, ['varName' => 'value', ...]]
	 *
	 * @param string $httpMethod
	 * @param string $uri
	 *
	 * @return array
	 */
	public function dispatch($httpMethod, $uri)
	{
		//Get all the constants
		list($namespace, $defaultControllerName, $homeControllerName, $requestMethod, $segments) = $this->getAllConstants();

		//Get the controller and make instance of defaultController
		list($controllerName, $remainingSegments) = $this->getController($namespace, $defaultControllerName, $homeControllerName, $segments);

		//Get the name of the method
		if ($controllerName)
		{
			list($route, $parameters) = $this->getRoute($controllerName, $requestMethod, $remainingSegments);
			if ($route && $this->areParametersValid($route->parameters, $parameters))
			{
				//Set transaction name
				$transactionName = str_replace('\\', '/', substr($controllerName, strlen($namespace), -strlen('Controller'))) . '@' . $route->name;
				Registry::warehouse('newrelic')->setTransactionName($transactionName);

				//Prepare result
				$routeResults = array(
					self::FOUND,
					array(
						'uses' => '\\' . $controllerName . '@responseFlow',
					),
					array(
						$defaultControllerName,
						$route,
						$parameters,
					),
				);

				//Middleware
				$middleware = $route->middleware;
				if ($route->rights)
				{
					Visitor::$requiredRights = $route->rights;

					app()->bind('core.auth', AuthMiddleware::class);
					app()->bind('core.rights', RightsMiddleware::class);
					$middleware[] = 'core.auth';
					$middleware[] = 'core.rights';
				}
				elseif (is_subclass_of($controllerName, LoginRequiredController::class))
				{
					app()->bind('core.auth', AuthMiddleware::class);
					$middleware[] = 'core.auth';
				}
				if ($middleware)
				{
					$routeResults[1]['middleware'] = $middleware;
				}

				//Return result
				return $routeResults;
			}
		}

		//No route found
		return array(
			self::NOT_FOUND,
			array(),
			array(),
		);
	}

	/**
	 * Get all the constants to do the routing
	 * @return array
	 */
	private function getAllConstants()
	{
		$request = Request::getInstance();

		//Fetch namespace
		$namespace = config('app.routing.namespace');
		$namespace = trim($namespace, '\\');
		//Fetch defaultController
		$defaultController = config('app.routing.defaultcontroller');
		$defaultController = $namespace . '\\' . $defaultController;
		//Fetch homeController
		$homeController = config('app.routing.homecontroller');
		$homeController = $namespace . '\\' . $homeController;

		//Get the call-method
		$requestMethod = strtolower($request->method);
		if ($requestMethod == 'patch')
		{
			$requestMethod = 'put';
		}

		//Get route and its parts
		$segments = $request->segments;

		return array($namespace, $defaultController, $homeController, $requestMethod, $segments);
	}

	/**
	 * Get the controller from the input
	 * @param string $namespace
	 * @param string $defaultControllerName
	 * @param string $homeControllerName
	 * @param array $segments
	 * @return array
	 */
	private function getController($namespace, $defaultControllerName, $homeControllerName, $segments)
	{
		//Go through all the parts back to front
		$controllerLoopCount = min(3, count($segments));
		for ($i = 0; $i <= $controllerLoopCount; ++$i)
		{
			//Make the className
			if ($i == $controllerLoopCount)
			{
				//HomeController
				$className = $homeControllerName;
			}
			else
			{
				//Form class-name
				$className = '';
				for ($j = 0; $j <= $i; ++$j)
				{
					$className .= '\\' . ucfirst(strtolower($segments[$j]));
				}
				$className = $namespace . $className . 'Controller';

				//Home controller only approachable when called from '/'
				//Default controller not approachable
				if (in_array($className, array($homeControllerName, $defaultControllerName)))
				{
					break;
				}
			}

			//Check if it is valid and exists
			if ($this->isValidClass($className))
			{
				//Get the remaining segments
				$remainingSegments = array_slice($segments, ($i == $controllerLoopCount ? 0 : $i + 1));
				return array($className, $remainingSegments);
			}
		}

		return array(null, null);
	}

	/**
	 * Validate the className and check if it exists
	 * @param string $className
	 * @return bool
	 */
	private function isValidClass($className)
	{
		return (
			preg_match('/^[\w\\\\]*$/', $className) === 1
			&& class_exists($className)
		);
	}

	/**
	 * Get the method name
	 * @param string $controllerName
	 * @param string $requestMethod
	 * @param array $remainingSegments
	 * @return array
	 */
	private function getRoute($controllerName, $requestMethod, $remainingSegments)
	{
		$routes = collect(call_user_func(array($controllerName, 'getRoutes')));

		//Check if it is index-page and otherwise filter it out
		if (empty($remainingSegments))
		{
			if ($requestMethod == 'get' && $route = $routes->where('name', 'getIndex')->first())
			{
				return array($route, $remainingSegments);
			}
			return array(null, null);
		}
		else
		{
			foreach ($routes->where('name', 'getIndex') as $key => $route)
			{
				$routes->forget($key);
			}
		}

		//If root methods are enabled, fetch them but give priority to other methods
		$rootRoute = null;
		foreach (array('get', 'post', 'put', 'delete') as $name)
		{
			foreach ($routes->where('name', $name) as $key => $route)
			{
				if ($requestMethod == $name)
				{
					$rootRoute = $route;
				}
				$routes->forget($key);
			}
		}

		$currentRoute = null;
		//Looping through all the different options for this controller
		for ($namePosition = 0; $namePosition < count($remainingSegments); ++$namePosition)
		{
			$routesForPosition = $routes->where('namePosition', $namePosition);
			if (!$routesForPosition->count())
			{
				continue;
			}

			foreach ($routesForPosition as $key => $route)
			{
				if (strtolower($route->name) == strtolower($requestMethod . $remainingSegments[$namePosition]))
				{
					$currentRoute = $route;
					//Remove the methodName from the segments
					array_splice($remainingSegments, $namePosition, 1);
					return false;
				}
			}
		}

		if (!$currentRoute)
		{
			//Check rootMethod option
			if ($rootRoute)
			{
				$currentRoute = $rootRoute;
			}
			//Or just missing route
			else
			{
				return array(null, null);
			}
		}

		//Check if parameter-count is right
		if (count($currentRoute->parameters) != count($remainingSegments))
		{
			return array(null, null);
		}

		//Return the right method
		return array($currentRoute, $remainingSegments);
	}

	/**
	 * @param array $parameterSpecs
	 * @param array $parameters
	 * @return bool
	 */
	private function areParametersValid($parameterSpecs, $parameters)
	{
		for ($i = 0; $i < count($parameters); ++$i)
		{
			if (preg_match('/^'.$parameterSpecs[$i].'$/', $parameters[$i]) !== 1)
			{
				return false;
			}
		}

		return true;
	}
}
