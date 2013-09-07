<?php

/**
 * Working Tag class
 *
 * @author gbatanov
 * @version v.0.1
 * @package rtcache.demo
 */
namespace demo;
class Tag extends \Cache\Tag {
	public function getBackend() {
		global $backend;
		return $backend;
	}

}

