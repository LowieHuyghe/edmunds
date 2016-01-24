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

namespace Core\Bases\Analytics;

use Core\Bases\Analytics\Tracking\BaseLog;
use Core\Bases\Structures\BaseStructure;
use Core\Jobs\QueueJob;
use Core\Jobs\Queue;
use Core\Registry\Registry;

/**
 * The warehouse base to extend from
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class BaseWarehouse extends BaseStructure
{
	/**
	 * All the logs bundled
	 * @var BaseLog[]
	 */
	protected $logs = array();

	/**
	 * Parameter mapping
	 * @var array
	 */
	protected $parameterMapping = array();

	/**
	 * Log something
	 * @param  BaseLog $log
	 * @return void
	 */
	public function log($log)
	{
		$this->logs[] = $log;
	}

	/**
	 * Flush all the saved up logs
	 */
	public function flush()
	{
		//
	}

	/**
	 * Queue something
	 * @param  array $argumentsArray
	 */
	protected function queue($callable, $argumentsArray)
	{
		(new QueueJob($callable, $argumentsArray, Queue::QUEUE_LOG, 1))->dispatch();
	}
}
