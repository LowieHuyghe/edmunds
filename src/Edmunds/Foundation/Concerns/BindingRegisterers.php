<?php

/**
 * Edmunds
 *
 * The fast PHP framework for building web applications.
 *
 * @license   This file is subject to the terms and conditions defined in file 'license.md', which is part of this source code package.
 */

namespace Edmunds\Foundation\Concerns;

use Edmunds\Encryption\ObfuscatorServiceProvider;

/**
 * The RegistersExceptionHandlers concern
 */
trait BindingRegisterers
{
	/**
	 * register the additional bindings
	 */
	protected function registerAdditionalBindings()
	{
		$this->availableBindings['obfuscator'] = 'registerObfuscatorBindings';
		$this->availableBindings['filesystem'] = 'registerFilesystemBindings';
		$this->availableBindings['mailer'] = 'registerMailBindings';
		$this->availableBindings['queue.worker'] = 'registerQueueBindings';
		$this->availableBindings['edmunds.request'] = 'registerEdmundsBindings';
		$this->availableBindings['edmunds.response'] = 'registerEdmundsBindings';
		$this->availableBindings['edmunds.visitor'] = 'registerEdmundsBindings';
		$this->availableBindings['edmunds.input'] = 'registerEdmundsBindings';

		$this->aliases['kernel'] = \Illuminate\Contracts\Console\Kernel::class;
	}

	/**
	 * Register obfuscator bindings
	 */
	protected function registerObfuscatorBindings()
	{
		$this->singleton('obfuscator', function ()
		{
			return $this->loadComponent('app', ObfuscatorServiceProvider::class, 'obfuscator');
		});
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerCookieBindings()
	{
		$this->singleton('cookie', function ()
		{
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
		$this->singleton('session', function ()
		{
			return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider');
		});
		$this->singleton('session.store', function ()
		{
			return $this->loadComponent('session', 'Illuminate\Session\SessionServiceProvider', 'session.store');
		});
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerFilesystemBindings()
	{
		$this->singleton('filesystem', function ()
		{
			return $this->loadComponent('filesystems', 'Edmunds\Filesystem\Providers\FilesystemServiceProvider', 'filesystem');
		});
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerMailBindings()
	{
		$this->singleton('mailer', function ()
		{
			return $this->loadComponent('mail', 'Edmunds\Mail\Providers\MailServiceProvider', 'mailer');
		});
	}

	/**
	 * Register container bindings for the application.
	 *
	 * @return void
	 */
	protected function registerQueueBindings()
	{
		$this->singleton('queue', function ()
		{
			return $this->loadComponent('queue', 'Edmunds\Queue\Providers\QueueServiceProvider', 'queue');
		});
		$this->singleton('queue.connection', function ()
		{
			return $this->loadComponent('queue', 'Edmunds\Queue\Providers\QueueServiceProvider', 'queue.connection');
		});
		$this->singleton('queue.worker', function ()
		{
			return $this->loadComponent('queue', 'Edmunds\Queue\Providers\QueueServiceProvider', 'queue.worker');
		});
	}

	/**
	 * Register edmunds bindings for the application.
	 *
	 * @return void
	 */
	protected function registerEdmundsBindings()
	{
		$this->singleton('edmunds.request', function ()
		{
			return $this->loadComponent('app', 'Edmunds\Foundation\Providers\EdmundsServiceProvider', 'edmunds.request');
		});
		$this->singleton('edmunds.response', function ()
		{
			return $this->loadComponent('app', 'Edmunds\Foundation\Providers\EdmundsServiceProvider', 'edmunds.response');
		});
		$this->singleton('edmunds.visitor', function ()
		{
			return $this->loadComponent('app', 'Edmunds\Foundation\Providers\EdmundsServiceProvider', 'edmunds.visitor');
		});
		$this->singleton('edmunds.input', function ()
		{
			return $this->loadComponent('app', 'Edmunds\Foundation\Providers\EdmundsServiceProvider', 'edmunds.input');
		});
	}
}