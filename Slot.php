<?php

/**
 * Slot-based caching implementation.
 * 
 * @version v.0.4
 * @package Rtcache
 */
abstract class Rtcache_Slot {

	protected $backend = null;
	/**
	 * Tags attached to this slot.
	 * 
	 * @var array of tags
	 */
	protected $_tags=array();
	/**
	 * ID associated to this slot.
	 * 
	 * @var string
	 */
	private $_id = null;
	/**
	 * Lifetime of this slot. Default 1 hour.
	 * @var int
	 */
	protected $lifetime = 3600;

	/**
	 * Creates a new slot object.
	 * 
	 * @param string $id   ID of this slot.
	 * @return Rtcache_Slot
	 */
	public function __construct($id) {
		$this->_id = $id;
		$this->_tags = array();
	}

	/**
	 * Loads a data of this slot. If nothing is found, returns false.
	 * 
	 * @return mixed   Complex data or false if no cache entry is found.
	 */
	public function load() {
		$raw = $this->_getBackend()->load($this->_id);
		if ($raw === false)
			return FALSE;
		return unserialize($raw);
	}

	/**
	 * Saves a data for this slot. 
	 * 
	 * @param mixed $data   Data to be saved.
	 * @return void
	 */
	public function save($data) {
		$raw = serialize($data);
		$this->_getBackend()->save($raw, $this->_id, $this->_tags, $this->lifetime);
	}

	/**
	 * Removes a data of specified slot.
	 * 
	 * @return void
	 */
	public function remove() {
		$this->_getBackend()->remove($this->_id);
	}

	/**
	 * Returns backend object responsible for this cache slot.
	 * 
	 * @return Rtcache_Backend
	 */
	protected function _getBackend() {
		return $this->backend;
	}

}

class Rtcache_SlotException extends Exception {
	
}