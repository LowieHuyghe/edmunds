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

namespace Core\Bases\Http\Controllers;

use Core\Http\Controllers\Login\LoginRequiredController;
use Core\Http\Client\Input;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Client\Visitor;
use Core\Http\Route;
use Core\Io\Validation\Validation;
use Laravel\Lumen\Routing\Controller;

/**
 * Controller base to extend from
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class BaseController extends Controller
{
	/**
	 * Get the accepted methods for routing
	 * @return Route[]
	 */
	public static function getRoutes()
	{
		return array();
	}

	/**
	 * The default output type of the response, only used when set
	 * @var int
	 */
	protected $outputType; //Response::TYPE_VIEW by default

	/**
	 * The current request
	 * @var Request
	 */
	protected $request;

	/**
	 * The current request
	 * @var Response
	 */
	protected $response;

	/**
	 * The input
	 * @var Input
	 */
	protected $input;

	/**
	 * The validator
	 * @var Validation
	 */
	protected $validator;

	/**
	 * The visitor
	 * @var Visitor
	 */
	protected $visitor;

	/**
	 * The constructor for the BaseController
	 */
	function __construct()
	{
		$this->request = Request::getInstance();
		$this->response = Response::getInstance();
		$this->visitor = Visitor::getInstance();
		$this->input = Input::getInstance();
		$this->validator = new Validation($this->input->all());

		if (isset($this->outputType))
		{
			$this->response->outputType = $this->outputType;
		}
	}

	/**
	 * [responseFlow description]
	 * @param string $defaultControllerName
	 * @param Route $route
	 * @param array $parameters
	 * @return Illuminate\Http\Response
	 */
	public function responseFlow($defaultControllerName, $route, $parameters)
	{
		//Assign default values
		$this->assignDefaults();

		//Make default controller
		$defaultController = app($defaultControllerName);

		//Initialize default controller
		$defaultController->initialize();

		//Initialiaz this controller
		$this->initialize();
		//Call the tight method
		$response = call_user_func_array(array($this, $route->name), $parameters);
		//Finalize this controller
		$this->finalize();

		//Finalize default controller
		$defaultController->finalize();

		//set the status of the response
		if ($response === true || $response === false)
		{
			$this->response->assign('success', $response);
		}

		//Return response
		return $this->response->getResponse();
	}

	/**
	 * Assign some default values
	 */
	protected function assignDefaults()
	{
		$this->response->assign('__root', $this->request->root);
		$this->response->assign('__siteName', config('app.name'));

		$this->response->assign('__local', app()->isLocal());

		$this->response->assign('__login', $this->visitor->user);

		$this->response->assign('__rtl', $this->visitor->localization->rtl);
	}

	/**
	 * Function called after construct
	 */
	public function initialize()
	{
		//
	}

	/**
	 * Function called after method
	 */
	public function finalize()
	{
		//
	}

}