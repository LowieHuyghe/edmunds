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

namespace Core\Foundation\Controllers;

use Core\Analytics\Tracking\EcommerceItem;
use Core\Analytics\Tracking\EcommerceLog;
use Core\Analytics\Tracking\ErrorLog;
use Core\Analytics\Tracking\EventLog;
use Core\Analytics\Tracking\PageviewLog;
use Core\Bases\Http\Controllers\BaseController;
use Core\Validation\Validator;
use ErrorException;

/**
 * Controller responsible for logging data
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class LogController extends BaseController
{
	protected $outputType = \Core\Http\Response::TYPE_JSON;
	/**
	 * Register the default routes for this controller
	 * @param  Application $app
	 * @param  string $prefix
	 * @param  array  $middleware
	 */
	public static function registerRoutes(&$app, $prefix ='', $middleware = array())
	{
		$app->post($prefix . 'log/{type}', '\\' . get_called_class() . '@postLog');
	}

	/**
	 * Post log data
	 */
	public function postLog($type)
	{
		switch (strtolower($type))
		{
			case 'error':
				return $this->processErrorLog();
				break;
			case 'event':
				return $this->processEventLog();
				break;
			case 'pageview':
				return $this->processPageviewLog();
				break;
			case 'ecommerce':
				return $this->processEcommerceLog();
				break;
		}

		return false;
	}

	/**
	 * Process error log
	 * @return bool
	 */
	protected function processErrorLog()
	{
		$message = $this->input->rule('message')->required()->get();
		$code = $this->input->rule('code')->fallback(0)->get();
		$file = $this->input->rule('file')->fallback('')->get();
		$line = $this->input->rule('line')->integer()->fallback(0)->get();

		// input has errors
		if ($this->input->hasErrors())
		{
			return false;
		}

		// it's ok, let's process this
		else
		{
			$log = new ErrorLog();
			$log->type = 'Javascript';
			$log->exception = new ErrorException($message, $code, 1, $file, $line);
			$log->log();

			return true;
		}
	}

	/**
	 * Process event log
	 * @return bool
	 */
	protected function processEventLog()
	{
		$log = new EventLog($this->input->only(array(
			'category', 'action', 'name', 'value'
		)));

		// input has errors
		if ($log->hasErrors())
		{
			return false;
		}

		// it's ok, let's process this
		else
		{
			$log->log();

			return true;
		}
	}

	/**
	 * Process pageview log
	 * @return bool
	 */
	protected function processPageviewLog()
	{
		$log = new PageviewLog($this->input->only(array(
			'url', 'referrer'
		)));

		$urlParts = parse_url($log->url);
		$log->host = $urlParts['host'];

		$path = $urlParts['path'];
		$log->path = (!$path || $path[0] != '/') ? "/$path" : $path;

		// input has errors
		if ($log->hasErrors())
		{
			return false;
		}

		// it's ok, let's process this
		else
		{
			$log->log();

			return true;
		}
	}

	/**
	 * Process ecommerce log
	 * @return bool
	 */
	protected function processEcommerceLog()
	{
		$log = new EcommerceLog($this->input->only(array(
			'id', 'revenue', 'subtotal', 'shipping', 'tax', 'discount', 'previous'
		)));

		// input has errors
		if ($log->hasErrors())
		{
			return false;
		}

		// it's ok, let's process this
		else
		{
			$items = array();

			foreach ($this->input->get('items') as $item)
			{
				$item = json_decode($item, true);

				$logItem = new EcommerceItem(array_only($item, array(
					'id', 'category', 'name', 'price', 'quantity'
				)));

				if (!$logItem->hasErrors())
				{
					$items[] = $logItem;
				}
			}

			$log->items = $items;
			$log->log();

			return true;
		}
	}
}