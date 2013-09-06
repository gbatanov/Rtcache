<?php

/**
 * Class cleaning cache by tags
 *
 * @author gbatanov
 */

namespace Rtcache\demo;

class ClearCache {
	public static function clearTags($tags = array()) {
		global $backend;
		$backend->clean(\Rtcache\Cache\Backend::CLEANING_MODE_MATCHING_TAG, $tags);
	}

	public static function clearAll() {
		global $backend;
		$backend->clean(\Rtcache\Cache\Backend::CLEANING_MODE_ALL);
	}

}

