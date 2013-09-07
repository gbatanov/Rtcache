<?php

/**
 * Static demo class User
 *
 * @author gbatanov
 * @version v.0.2
 * @package rtcache.demo
 */

class User {

	private static $_id = 0;
	private $Id = 0;
	private $_params = array();

	public function __construct() {
		$this->Id = ++self::$_id;
	}

	public function getId() {
		return $this->Id;
	}

	/**
	 * Imitation get data from database
	 * 
	 * @return array
	 */
	private function getUserParams() {
		for ($i = 0; $i < 1000; ++$i) {
			
		};
		return $this->_params;
	}

	public function addParams($params) {
		$this->_params[] = $params;
	}

	public function addParamsWithAutoCleaning($params) {
		$this->_params[] = $params;
		$tag = new Tag('user_' . $this->Id);
		ClearCache::clearTags((array) $tag->getNativeId());
	}

	public function getParams() {
		$slot = new UserSlot($this);
		$data = $slot->load();
		if ($data === false) {
			$data = $this->getUserParams();
			$slot->save($data);
		}
		return $data;
	}

	public function resetParams() {
		$this->_params = array();
		$tag = new Tag('user_' . $this->Id);
		ClearCache::clearTags((array) $tag->getNativeId());
	}

}

