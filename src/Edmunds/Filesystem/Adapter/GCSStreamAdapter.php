<?php

/**
 * Edmunds
 *
 * The fast PHP framework for building web applications.
 *
 * @license   This file is subject to the terms and conditions defined in file 'license.md', which is part of this source code package.
 */

namespace Edmunds\Filesystem\Adapter;

use League\Flysystem\Adapter\Local as LocalAdapter;
use League\Flysystem\Config;


/**
 * The adapter for Google Cloud Storage stream
 *
 * The class overrides the existing methods in order to:
 *
 * - remove exclusive locks(not supported by GAE) while writing files,
 *
 * - 'ensureDirectory()' replace a call to 'reapath()' functions with
 * a call to 'gae_realpath()' function, which is
 * compatible with GCS buckets,
 *
 * - 'writeStream()' replace 'fopen()' mode from 'w+', which is not supported
 * on GCS buckets and replaces it with 'w', as for the
 * specific function both 'w+' and 'w' should work properly.
 *
 * - 'applyPathPrefix()' remove trailing directory separators, which prevent
 * listing of disk root directory on GAE. Originally Flysystem Local adapter
 * ends up with path 'gs://bucket/storage/app//' for disk root, then 'is_dir()'
 * is used to check that it is a folder path. The check fails due to the trailing
 * slash which is not supported by GCS and an empty directory listing is returned.
 * In order to make the check pass the path has to be 'gs://bucket/storage/app/'.
 */
class GCSStreamAdapter extends LocalAdapter
{
	/**
	 * Constructor
	 * @param string $root
	 */
	public function __construct($root)
	{
		parent::__construct($root, 0, self::DISALLOW_LINKS);
	}

	/**
	 * Ensure the root directory exists.
	 *
	 * @param string $root root directory path
	 * @return string real path to root
	 *
	 * @throws Exception in case the root directory can not be created
	 */
	protected function ensureDirectory($root)
	{
		if (is_dir($root) === false) {
			mkdir($root, 0755, true);
		}
		return gae_realpath($root);
	}

	/**
	 * @inheritdoc
	 */
	public function writeStream($path, $resource, Config $config)
	{
		$location = $this->applyPathPrefix($path);
		$this->ensureDirectory(dirname($location));
		if ( ! $stream = fopen($location, 'w'))
		{
			return false;
		}
		while ( ! feof($resource))
		{
			fwrite($stream, fread($resource, 1024), 1024);
		}
		if ( ! fclose($stream))
		{
			return false;
		}
		if ($visibility = $config->get('visibility'))
		{
			$this->setVisibility($path, $visibility);
		}
		return compact('path', 'visibility');
	}

	/**
	 * @inheritdoc
	 */
	public function applyPathPrefix($path)
	{
		$prefixedPath = parent::applyPathPrefix($path);
		return rtrim($prefixedPath, DIRECTORY_SEPARATOR);
	}
}