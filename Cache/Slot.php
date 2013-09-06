<?php

/**
 * Slot-based caching frontend.
 * 
 * @version v.0.1
 * @package Rtcache
 */
abstract class Rtcache_Cache_Slot {

	/**
	 * Tags attached to this slot.
	 * 
	 * @var array of Rtcache_Cache_Tag
	 */
	private $_tags;
	/**
	 * ID associated to this slot.
	 * 
	 * @var string
	 */
	private $_id = null;
	/**
	 * Lifetime of this slot.
	 */
	protected $lifetime= 3600;

	/**
	 * Creates a new slot object.
	 * 
	 * @param string $id   ID of this slot.
	 * @return Rtcache_Cache_Slot
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
		return unserialize($raw);
	}

	/**
	 * Saves a data for this slot. 
	 * 
	 * @param mixed $data   Data to be saved.
	 * @return void
	 */
	public function save($data) {
		$tags = array();
		foreach ($this->_tags as $tag) {
			$id = $tag->getNativeId();
			$tags[] = $id;
		}
		$raw = serialize($data);
		$this->_getBackend()->save($raw, $this->_id, $tags, $this->lifetime);
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
	 * Associates a tag with current slot.
	 * 
	 * @param Rtcache_Cache_Tag $tag   Tag object to associate.
	 * @return void
	 */
	public function addTag(Rtcache_Cache_Tag $tag) {
		if ($tag->getBackend() !== $this->_getBackend()) {
			throw new Exception("Backends for tag " . get_class($tag) . " and slot " . get_class($this) . " must be same");
		}
		$this->_tags[] = $tag;
	}

	/**
	 * Returns backend object responsible for this cache slot.
	 * 
	 * @return Rtcache_Cache_Backend
	 */
	protected abstract function _getBackend();
}
