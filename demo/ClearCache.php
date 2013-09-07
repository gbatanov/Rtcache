<?php

/**
 * Class cleaning cache by tags
 *
 * @author gbatanov
 */

namespace demo;

class ClearCache {
	public static function clearTags($tags = array()) {
		global $backend;
		$backend->clean(\Cache\Backend::CLEANING_MODE_MATCHING_TAG, $tags);
	}

	public static function clearAll() {
		global $backend;
		$backend->clean(\Cache\Backend::CLEANING_MODE_ALL);
	}

}

