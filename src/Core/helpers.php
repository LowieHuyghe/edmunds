<?php

	/**
	 * Function to check if the helpers are included in the project
	 */
	function lhCoreHelpersIncludedCheck() {}

	/**
	 * Translate a string
	 * @param string $message
	 * @param array $parameters
	 * @return string
	 */
	function trans($message, $parameters = array(), $locale = null)
	{
		return \LH\Core\Helpers\LocalizationHelper::getInstance()->trans($message, $parameters, $locale);
	}

	/**
	 * Translate a string with pluralization
	 * @param string $message
	 * @param int $count
	 * @param array $parameters
	 * @return string
	 */
	function trans_choice($message, $count, $parameters = array(), $locale = null)
	{
		return \LH\Core\Helpers\LocalizationHelper::getInstance()->transChoice($message, $count, $parameters, $locale);
	}