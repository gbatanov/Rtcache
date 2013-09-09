<?php

/**
 * Working UserSlot class
 *
 * @author gbatanov
 * @version v.0.3
 * @package rtcache.demo
 */
class UserSlot extends Rtcache_Slot {

	protected $lifetime = 60;
	private $_userId = 0;

	public function __construct(User $user, $params = array()) {
		global $backend;
		$this->backend = $backend;
		$this->_userId = $user->getId();
		parent::__construct('user_' . $this->_userId . '_' . md5(serialize($params)));
	}

	public function save($data) {
		$this->_tags[] = 'user';
		$this->_tags[] = 'user_' . $this->_userId;
		parent::save($data);
	}

}

