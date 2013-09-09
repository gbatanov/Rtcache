<?php

/**
 * Class cleaning cache by tags
 *
 * @author gbatanov
 * @version v.0.3
 * @package rtcache.demo
 */
class ClearCache {
	/**
	 * Clearing by given tags
	 * 
	 * @global Rtcache_Backend $backend
	 * @param array|string $tags
	 * @return boolean
	 * @throws Exception
	 */
	public static function clearTags($tags) {
		global $backend;
//		try {
			if (is_string($tags))
				$tags = explode(',', $tags);
			if (!is_array($tags))
				throw new Exception('Tags should be array or string with comma delimiter.');
			return $backend->clean(Rtcache_Backend::CLEANING_MODE_MATCHING_TAG, $tags);
//		} catch (Exception $e) {
//			return false;
//		}
	}

	/**
	 * Full clearing
	 * 
	 * @global Rtcache_Backend $backend
	 */
	public static function clearAll() {
		global $backend;
		$backend->clean(Rtcache_Backend::CLEANING_MODE_ALL);
	}

	public static function clearOld(){
		global $backend;
		return $backend->clean(Rtcache_Backend::CLEANING_MODE_OLD);
	}
}

