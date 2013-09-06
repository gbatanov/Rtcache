<?php

/**
 * Class cleaning cache by tags
 *
 * @author gbatanov
 */
class ClearCache {
	
	public static function clearTags($tags = array()){
		global $backend;
		$backend->clean(Rtcache_Cache_Backend::CLEANING_MODE_MATCHING_TAG, $tags);
	}
	
	public static function clearAll(){
		global $backend;
		$backend->clean(Rtcache_Cache_Backend::CLEANING_MODE_ALL);
	}
}


