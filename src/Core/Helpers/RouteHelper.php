<?php

/**
 * LH Core
 *
 * The core of any web-project by Lowie Huyghe
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */

namespace LH\Core\Helpers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use LH\Core\Controllers\BaseController;
use LH\Core\Controllers\LoginRequiredController;
use LH\Core\Exceptions\ConfigNotFoundException;

/**
 * The helper responsible for the routing
 * To use it, just add the following to routes.php:
 *
	Route::any('{all}', [
		'uses' => '\LH\Core\Helpers\RouteHelper@route'
	])->where('all', '.*');
 *
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class RouteHelper extends Controller
{
	/**
	 * Do the route logic
	 * @param string $route
	 * @return mixed
	 */
	public function route($route)
	{
		$this->checkConfig();

		$response = $this->routeHandler($route);

		if (env('APP_DEBUG') && App::environment('local') && Config::get('app.routing.redirecthalt') && is_a($response, RedirectResponse::class))
		{
			return $this->viewRedirect($route, $response);
		}

		return $response;
	}

	/**
	 * Do the route logic
	 * @param string $route
	 * @return mixed
	 */
	private function routeHandler($route)
	{
		//Check if it is a valid route
		if (!$this->isValidRoute($route))
		{
			return abort(404);
		}

		//Get all the constants
		list($namespace, $defaultControllerName, $homeControllerName, $requestMethod, $requestAjax, $segments) = $this->getAllConstants();

		//Get the controller and make instance of defaultController
		list($controller, $remainingSegments) = $this->getController($namespace, $defaultControllerName, $homeControllerName, $requestAjax, $segments);
		if (!$controller)
		{
			return abort(404);
		}
		$this->prepareController($controller);
		$defaultController = App::make($defaultControllerName);

		//Get the name of the method
		list($methodName, $parameterSpecs, $parameters, $requiredRoles) = $this->getMethodName($controller, $requestMethod, $remainingSegments);
		if (!$methodName
			|| !$this->areParametersValid($parameterSpecs, $parameters))
		{
			return abort(404);
		}

		//Do authentication checks
		if (!$this->authenticationChecks($route, $controller, $requiredRoles))
		{
			//Return response
			return ResponseHelper::getInstance()->getResponse();
		}

		//Call the method
		return $this->callMethod($defaultController, $controller, $methodName, $parameters);
	}

	/**
	 * Validate the route
	 * @param string $route
	 * @return bool
	 */
	private function isValidRoute($route)
	{
		//Only let the accepted routes through
		return (preg_match('/^[\w\d\/$\-_\.\+!\*]*$/', $route) === 1);
	}

	/**
	 * Get all the constants to do the routing
	 * @return array
	 */
	private function getAllConstants()
	{
		//Fetch namespace
		$namespace = Config::get('app.routing.namespace');
		$namespace = trim($namespace, '\\');
		//Fetch defaultController
		$defaultController = Config::get('app.routing.default');
		$defaultController = $namespace . '\\' . $defaultController;
		//Fetch homeController
		$homeController = Config::get('app.routing.home');
		$homeController = $namespace . '\\' . $homeController;

		//Get the current request
		$request = $this->getRouter()->getCurrentRequest();
		//Get the call-method
		$requestMethod = strtolower($request->method());
		if ($requestMethod == 'patch')
		{
			$requestMethod = 'put';
		}
		//Check if ajax-call
		$requestAjax = $request->ajax();
		//Get route and its parts
		$segments = $request->segments();

		return array($namespace, $defaultController, $homeController, $requestMethod, $requestAjax, $segments);
	}

	/**
	 * Get the controller from the input
	 * @param string $namespace
	 * @param string $defaultControllerName
	 * @param string $homeControllerName
	 * @param bool $requestAjax
	 * @param array $segments
	 * @return array
	 */
	private function getController($namespace, $defaultControllerName, $homeControllerName, $requestAjax, $segments)
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
					$className .= '\\' . ucfirst($segments[$j]);
				}
				$className = $namespace . $className . ($requestAjax ? 'Ajax' : '') . 'Controller';

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
				$controller = App::make($className);
				$remainingSegments = array_slice($segments, ($i == $controllerLoopCount ? 0 : $i + 1));
				return array($controller, $remainingSegments);
			}
		}

		return array(null, null);
	}

	/**
	 * Prepare the controller
	 * @param BaseController $controller
	 */
	private function prepareController(&$controller)
	{
		//Preparing the routeMethods
		if (!isset($controller->routeMethods[0]))
		{
			$controller->routeMethods[0] = array();
		}
		foreach ($controller->routeMethods as $key => $value)
		{
			if (!is_numeric($key))
			{
				$controller->routeMethods[0][$key] = $value;
				unset($controller->routeMethods[$key]);
			}
		}

		//Preparing each route of the routeMethods

		foreach ($controller->routeMethods as $uriPosition => $v)
		{
			$routeMethodUriSpecs = &$controller->routeMethods[$uriPosition];
			foreach ($routeMethodUriSpecs as $route => &$routeMethodRouteSpecs)
			{
				//Method
				if (!isset($routeMethodRouteSpecs['m']))
				{
					$routeMethodRouteSpecs['m'] = array('get');
				}
				elseif (!is_array($routeMethodRouteSpecs['m']))
				{
					$routeMethodRouteSpecs['m'] = array($routeMethodRouteSpecs['m']);
				}

				//Parameters
				if (!isset($routeMethodRouteSpecs['p']))
				{
					$routeMethodRouteSpecs['p'] = array();
				}

				//Roles
				if (!isset($routeMethodRouteSpecs['r']))
				{
					$routeMethodRouteSpecs['r'] = array();
				}
				elseif (!is_array($routeMethodRouteSpecs['r']))
				{
					$routeMethodRouteSpecs['r'] = array($routeMethodRouteSpecs['r']);
				}
			}
		}
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
	 * @param $controller
	 * @param $requestMethod
	 * @param $remainingSegments
	 * @return array
	 */
	private function getMethodName($controller, $requestMethod, $remainingSegments)
	{
		//Check if it is index-page and otherwise filter it out
		if (empty($remainingSegments))
		{
			if (isset($controller->routeMethods[0]['index']))
			{
				$routeMethodNameOptions = $controller->routeMethods[0]['index'];
				if (in_array($requestMethod, $routeMethodNameOptions['m']))
				{
					return array($requestMethod .'Index', array(), array(), $routeMethodNameOptions['r']);
				}
			}
			return array(null, null, null, null);
		}
		elseif (isset($controller->routeMethods[0]['index']))
		{
			unset($controller->routeMethods[0]['index']);
		}

		//If root methods are enabled, fetch them but give priority to other methods
		$rootMethodOptions = null;
		if (isset($controller->routeMethods[0]['/']))
		{
			$rootMethodOptions = $controller->routeMethods[0]['/'];
			unset($controller->routeMethods[0]['/']);
		}

		$methodName = null;
		$parameterSpecs = array();
		$requiredRoles = null;
		//Looping through all the different options for this controller
		for ($uriPosition = 0; $uriPosition < count($remainingSegments); ++$uriPosition)
		{
			if (!isset($controller->routeMethods[$uriPosition]))
			{
				continue;
			}
			$controllerMethodsSpecs = $controller->routeMethods[$uriPosition];

			//2 => array( 'home' => array('get') )
			$methodNameRaw = null;
			$methodNameRawOptions = null;
			foreach ($controllerMethodsSpecs as $controllerMethodName => $routeMethodNameOptions)
			{
				if ($controllerMethodName == strtolower($remainingSegments[$uriPosition]))
				{
					$methodNameRaw = $controllerMethodName;
					$methodNameRawOptions = $routeMethodNameOptions;
					break;
				}
			}

			//If method is found, stop searching
			if ($methodNameRaw)
			{
				//Fetch parameter-count from array
				$parameterSpecs = $methodNameRawOptions['p'];
				//Fetch roles from array
				$requiredRoles = $methodNameRawOptions['r'];
				//If requestMethod is not available, 404
				if (!in_array($requestMethod, $methodNameRawOptions['m']))
				{
					return array(null, null, null, null);
				}
				//Remove the methodName from the segments
				array_splice($remainingSegments, $uriPosition, 1);

				$methodName = $requestMethod . ucfirst($methodNameRaw);
				break;
			}
		}

		//Check rootMethod option
		if (!$methodName && $rootMethodOptions)
		{
			$parameterSpecs = $rootMethodOptions['p'];
			$requiredRoles = $rootMethodOptions['r'];
			if (in_array($requestMethod, $rootMethodOptions['m']))
			{
				$methodName = $requestMethod;
			}
		}

		//Check if parameter-count is right
		if (count($parameterSpecs) != count($remainingSegments))
		{
			return array(null, null, null, null);
		}

		//Return the right method
		return array($methodName, $parameterSpecs, $remainingSegments, $requiredRoles);
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

	/**
	 * @param string $route
	 * @param BaseController $controller
	 * @param array $requiredRoles
	 * @return mixed
	 */
	private function authenticationChecks($route, $controller, $requiredRoles)
	{
		//Check if authentication is needed
		$authCheckMethod = 'authenticationCheck';
		if (method_exists($controller, $authCheckMethod))
		{
			if (!$controller->$authCheckMethod($requiredRoles))
			{
				Session::set(LoginRequiredController::SESSION_KEY_LOGIN_INTENDED_ROUTE, $route);
				return false;
			}
		}

		return true;
	}

	/**
	 * Call the method of the controller and return the response
	 * @param BaseController $defaultController
	 * @param BaseController $controller
	 * @param string $methodName
	 * @param array $parameters
	 * @return mixed
	 */
	private function callMethod($defaultController, $controller, $methodName, $parameters)
	{
		//Initialize of defaultController
		$defaultController->initialize();

		//Initialize
		$controller->initialize();

		//PreRender
		$controller->preRender();

		$response = null;
		//Call method with variables
		switch (count($parameters))
		{
			case 0:
				$controller->$methodName();
				break;
			case 1:
				$controller->$methodName($parameters[0]);
				break;
			case 2:
				$controller->$methodName($parameters[0], $parameters[1]);
				break;
			case 3:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2]);
				break;
			case 4:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3]);
				break;
			case 5:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4]);
				break;
			case 6:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5]);
				break;
			case 7:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6]);
				break;
			case 8:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6], $parameters[7]);
				break;
			case 9:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6], $parameters[7], $parameters[8]);
				break;
			case 10:
				$controller->$methodName($parameters[0], $parameters[1], $parameters[2], $parameters[3], $parameters[4], $parameters[5], $parameters[6], $parameters[7], $parameters[8], $parameters[9]);
				break;
			default:
				ResponseHelper::getInstance()->response404();
				break;
		}

		//PostRender
		$controller->postRender();

		//Return response
		return ResponseHelper::getInstance()->getResponse();
	}

	/**
	 * Check if all configuration is in place
	 * @throws \Exception
	 */
	private function checkConfig()
	{
		//Fetch the configuration
		$config = ConfigHelper::get('core.config.required');

		//Check if everything is in place
		$notFound = array();
		foreach ($config as $line)
		{
			if (!Config::get($line))
			{
				$notFound[] = $line;
			}
		}

		//Throw error if needed
		if (!empty($notFound))
		{
			throw new ConfigNotFoundException($notFound);
		}
	}

	/**
	 * Show the redirect page
	 * @param string $route
	 * @param $response
	 * @return string
	 */
	private function viewRedirect($route, $response)
	{
		$targetUrl = $response->getTargetUrl();
		$targetUrlString = preg_replace('/http:\/\/.+?\.\w\w\w\/?(.*)/i', '$1', $targetUrl);
		if (empty($targetUrlString))
		{
			$targetUrlString = '/';
		}
		return "<html>
					<head>
						<title>James The Redirector</title>
					</head>
					<body>
						<style>
							body {
								margin: 0;
								padding: 0;
								background-color: #F3F3F3;
							}
							div {
								margin-top: 30px;
								text-align: center;
								font-size: 20px;
								line-height: 30px;
								font-family: 'Veranda, Arial, Serif';
								color: #3F3F3F;
							}
							a, a:link, a:visited, a:hover, a:active {
								color: #3F3F3F;
							}
						</style>
						<div>
							<svg version='1.1' x='0px' y='0px'
								 width='100px' height='120px' viewBox='0 0 98.254 120.247' enable-background='new 0 0 98.254 120.247'
								 xml:space='preserve'>
								<path fill='#3F3F3F' d='M82.859,52.593c3.821,1.315,6.998,2.292,10.083,3.504c3.178,1.248,5.332,3.518,5.313,7.12
									c-0.019,3.519-2.449,5.18-5.392,6.439c-2.942,1.262-6.077,1.69-9.213,2.549c-2.623-1.521-3.187-5.181-7.239-6.371
									c-18.654,0.092-38.273-0.414-57.805,1.672c-1.706,1.01-1.538,3.188-3.127,4.256c-2.968-0.617-6.103-1.078-9.116-1.951
									c-3.084-0.893-6.07-2.191-6.343-6.104c-0.274-3.95,2.21-6.044,5.313-7.662c1.778-0.927,3.63-1.651,5.665-1.834
									c1.49-0.134,2.976-0.343,4.799-1.38c0.025-9.01,0.216-18.279-0.367-27.556c-0.221-3.489-0.324-6.986-0.398-10.481
									c-0.107-5.081,2.334-8.493,6.942-10.518c5.263-2.313,10.849-3.24,16.488-3.737c8.981-0.792,17.979-0.829,26.908,0.682
									c6.496,1.099,12.641,3.007,17.489,8.756C82.859,23.854,82.859,38.146,82.859,52.593z'/>
								<path fill='#3F3F3F' d='M22.051,110.233c4.365,2.918,7.852,2.159,11.208-0.265c1.348-0.974,2.742-1.88,4.11-2.822
									c2.967-2.046,6-2.967,9.315-0.237c4.288-3.388,8.095-1.861,11.732,1.086c0.646,0.522,1.293,1.045,1.959,1.541
									c3.178,2.364,6.515,3.914,10.506,1.582c0.023,4.618-1.517,6.541-5.709,7.537c-5.241,1.247-10.623,0.655-17.545-1.928
									C35.088,123.047,24.879,120.842,22.051,110.233z'/>
								<path fill='#3F3F3F' d='M38.869,79.263c-3.664-3.015-8.788-2.913-12.916,0.255c-3.965,3.043-4.206,7.006-0.66,14.24
									c-2.388,1.836-1.752,4.945-2.641,7.578c-0.974,2.879-4.799,3.5-5.33,7.01c6.402-2.252,5.82-9.229,9.754-13.113
									c7.042,1.641,12.217-0.096,14.331-4.533C43.059,87.232,41.83,81.698,38.869,79.263z M32.182,93.631
									c-3.949-0.045-6.721-2.979-6.707-7.094c0.016-4.158,3.346-7.257,7.808-7.265c4.029-0.007,6.915,2.929,6.823,6.938
									C40.02,89.959,36.049,93.678,32.182,93.631z'/>
							</svg> <br/><br/>
							Hello good sir! <br/>
							<br/>
							I will be obliged to excuse me. <br/>
							You intended to go to the following route: $route. <br/>
							But as it is my duty, I am required to redirect you to: <a href='$targetUrl'>$targetUrlString</a>. <br/>
							My utmost apologies for the inconvenience.  <br/>
							<br/>
							Signed, <br/>
							<br/>
							<svg version='1.1' x='0px' y='0px'
								 width='300px' height='95px' viewBox='0 0 413.447 131.391' enable-background='new 0 0 413.447 131.391'
								 xml:space='preserve'>
							<path fill-rule='evenodd' clip-rule='evenodd' fill='#3F403F' d='M233.115,0.976c-2.361-2.636-4.762,0.725-6.435,2.392
								c-13.116,13.069-29.161,21.836-45.169,30.546c-6.43,3.499-12.74,6.337-21.32,4.962c-26.137-4.188-52.521-5.449-78.855-0.437
								C59.95,42.51,39.727,49.389,22.351,62.873C12.087,70.838,4.12,80.67,0.779,93.686c-2.494,9.717,1.119,20.734,9.11,26.572
								c17.303,12.642,36.941,12.32,56.737,9.609c19.549-2.678,36.74-11.418,52.92-22.372c23.641-16.006,45.578-34.013,64.818-55.161
								c2.184-2.4,4.058-2.816,6.736-1.441c3.238,1.663,6.539,3.203,9.769,4.883c1.396,0.727,3.078,1.48,3.946-0.003
								c0.83-1.419-0.32-2.749-1.863-3.511c-3.993-1.975-7.951-4.021-12.361-6.264c1.432-1.616,2.463-2.918,3.633-4.079
								c11.804-11.725,22.875-24.191,35.683-34.909C231.592,5.6,235.689,3.852,233.115,0.976z M117.738,103.742
								c-15.761,10.723-32.442,19.379-51.466,22.134c-18.259,2.644-36.456,3.079-52.723-8.062c-8.121-5.563-11.42-15.05-8.556-24.456
								C8.25,82.662,14.835,74.176,23.35,67.302C40.377,53.557,60.377,46.56,81.596,42.461c25.986-5.02,51.929-3.201,78.859,0.15
								c-6.575,3.877-13.087,5.274-19.502,6.789c-7.097,1.674-14.266,3.08-21.621,3.109c-0.976,0.004-2.023,0.178-2.905,0.57
								c-0.955,0.424-2.444,0.842-1.991,2.287c0.368,1.172,1.526,1.479,2.861,1.432c7.161-0.251,14.241-1.25,21.227-2.676
								c8.085-1.649,16.24-3.529,23.579-7.32c7.141-3.689,13.232-0.34,20.413,1.273C162.955,69.893,141.195,87.782,117.738,103.742z
								 M187.736,43.129c-1.311,1.437-2.734,1.604-4.473,1.07c-2.492-0.765-5.016-1.43-8.473-2.404c9.58-4.484,18.22-9.242,26.998-13.847
								C197.099,33.004,192.382,38.035,187.736,43.129z'/>
							<path fill-rule='evenodd' clip-rule='evenodd' fill='#3F403F' d='M409.605,42.425c-1.492,0.108-2.998,0.083-4.482,0.251
								c-26.434,3.002-52.939,4.645-79.552,4.609c-3.515-0.005-4.679-0.688-2.819-4.082c0.691-1.262,1.445-2.801-0.363-3.9
								c-1.945-1.183-2.43,0.773-3.393,1.668c-7.068,6.582-15.414,9.545-25.161,9.242c0.151-2.197,1.874-4.256-0.648-5.695
								c-1.134-0.646-1.848,0.149-2.591,0.803c-5.52,4.846-7.738,4.859-12.873-0.079c-1.334-1.282-2.725-0.976-4.154-0.482
								c-1.297,0.446-2.341,1.659-3.973,1.467c0.264-1.925,1.217-3.866-0.739-5.115c-1.716-1.097-3.024,0.515-4.48,1.071
								c-3.542,1.352-7.064,2.764-10.642,4.018c-1.506,0.527-2.748,0.687-2.815-1.832c-0.096-3.537-2.257-3.369-4.815-2.059
								c-4.364,2.236-8.842,4.207-13.75,5.133c-0.693-7.738-4.936-10.074-12.162-6.996c-3.469,1.479-6.201,3.855-8.219,6.961
								c-1.094,1.682-3.206,3.459-1.316,5.745c1.623,1.966,3.766,1.324,5.966,0.796c4.364-1.05,7.204-4.432,11.348-7.326
								c-0.907,6.041,2.776,5.408,5.887,4.742c3.201-0.686,6.284-1.979,9.362-3.156c1.263-0.482,2.578-1.48,3.428,0.194
								c1.824,3.601,4.914,2.29,7.461,1.825c3.214-0.588,6.293-1.914,9.426-2.92c1.315,5.287,2.505,5.971,7.418,3.305
								c2.465-1.338,4.209-1.605,5.904,0.875c0.324,0.473,1.125,0.993,1.619,0.919c6.905-1.04,13.48,1.681,20.371,1.641
								c6.568-0.038,12.189-3.524,19.104-2.938c9.335,0.792,18.915,0.032,28.386-0.207c21.123-0.532,42.104-2.634,63.123-4.398
								c2.004-0.168,4.073-0.763,3.985-2.749C413.378,42.289,411.138,42.313,409.605,42.425z M215.393,50.197
								c2.168-3.776,4.548-5.223,7.446-5.978C221.429,46.956,219.259,48.683,215.393,50.197z'/>
							</svg> <br/>
							<br/>
						</div>
					</body>
				</html>";
	}
}