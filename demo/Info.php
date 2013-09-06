<?php

/**
 * Static demo class User
 *
 * @author gbatanov
 * @version v.0.1
 * @package rtcache.demo
 */
namespace Rtcache\demo;
class Info {

	private static $_id = 0;
	private $Id = 0;
	private $_infoArray = array();

	public function __construct() {
		$this->Id = ++self::$_id;
	}

	public function getId() {
		return $this->Id;
	}

	public function setInfo($userId, $info) {
		$this->_infoArray[$userId] = $info;
	}

	/**
	 * Imitation get data from database
	 * 
	 * @return array
	 */
	private function _getInfoData($userId) {
		for ($i = 0; $i < 1000; ++$i) {
			
		};
		return isset($this->_infoArray[$userId]) ? $this->_infoArray[$userId] : null;
	}

	public function getInfo($userId) {
		$slot = new InfoSlot($this, $userId);
		$data = $slot->load();
		if ($data === false) {
			$data = $this->_getInfoData($userId);
		}
		return $data;
	}

}

