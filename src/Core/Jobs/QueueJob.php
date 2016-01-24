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

namespace Core\Jobs;

use Core\Bases\Jobs\BaseJob;
use Core\Jobs\Queue;
use Core\Registry\Registry;

/**
 * Queue to use
 *
 * @author		Lowie Huyghe <iam@lowiehuyghe.com>
 * @copyright	Copyright (C) 2015, Lowie Huyghe. All rights reserved. Unauthorized copying of this file, via any medium is strictly prohibited. Proprietary and confidential.
 * @license		http://LicenseUrl
 * @since		Version 0.1
 */
class QueueJob extends BaseJob
{
	/**
	 * @var callable
	 */
	private $callable;

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var int
	 */
	private $attempts;

	/**
	 * Constructor
	 * @param callable $callable
	 * @param array $args
	 * @param int $attempts
	 */
	public function __construct($callable, $args = array(), $queue = Queue::QUEUE_DEFAULT, $attempts = 1)
	{
		$this->callable = $callable;
		$this->args = $args;
		$this->attempts = $attempts;

		$this->onQueue($queue);
	}

	/**
	 * Execute the job.
	 */
	public function handle()
	{
		if ($this->attempts() <= $this->attempts)
		{
			call_user_func_array($this->callable, $this->args);
		}
	}

	/**
	 * Queue this job
	 * @return \Illuminate\Http\Response
	 */
	public function dispatch()
	{
		return Registry::queue()->dispatch($this);
	}
}
