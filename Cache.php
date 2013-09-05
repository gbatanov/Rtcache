<?php

/**
 * 
 * @version v.0.1
 * @package Rtcache
 */
abstract class Rtcache_Cache {
	/**
	 * Consts for clean() method
	 */

	const CLEANING_MODE_ALL = 'all';
	const CLEANING_MODE_OLD = 'old';
	const CLEANING_MODE_MATCHING_TAG = 'matchingTag';
	const CLEANING_MODE_NOT_MATCHING_TAG = 'notMatchingTag';
	const CLEANING_MODE_MATCHING_ANY_TAG = 'all';

	/**
	 * Throw an exception
	 *
	 * @param  string $msg  Message for the exception
	 * @throws Exception
	 */
	public static function throwException($msg) {
		throw new Rtcache_Exception($msg);
	}

}

class Rtcache_Exception extends Exception {
	
}