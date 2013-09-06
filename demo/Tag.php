<?php

/**
 * Working Tag class
 *
 * @author gbatanov
 * @version v.0.1
 * @package rtcache.demo
 */
namespace Rtcache\demo;
class Tag extends \Rtcache\Cache\Tag {
	public function getBackend() {
		global $backend;
		return $backend;
	}

}

