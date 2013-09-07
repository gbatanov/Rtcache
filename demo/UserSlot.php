<?php

/**
 * Working UserSlot class
 *
 * @author gbatanov
 * @version v.0.2
 * @package rtcache.demo
 */

class UserSlot extends Rtcache_Slot {

	protected $lifetime = 3600;
	private $_userId = 0;

	public function __construct(User $user) {
		$this->_userId = $user->getId();
		parent::__construct('user' . $this->_userId);
	}

	public function _getBackend() {
		global $backend;
		return $backend;
	}

	public function save($data) {
		$tag = new Tag('user');
		$this->addTag($tag);
		$tag2 = new Tag('user_' . $this->_userId);
		$this->addTag($tag2);
		parent::save($data);
	}

}

