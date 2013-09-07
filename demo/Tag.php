<?php

/**
 * Working Tag class
 *
 * @author gbatanov
 * @version v.0.1
 * @package rtcache.demo
 */
class Tag extends Rtcache_Tag {

	public function getBackend() {
		global $backend;
		return $backend;
	}

}

