<?php

/**
 * Working UserSlot class
 *
 * @author gbatanov
 * @version v.0.3
 * @package rtcache.demo
 */
class InfoSlot extends Rtcache_Slot {

	protected $lifetime = 60;
	private $_infoId = 0;
	private $_userId = 0;

	public function __construct(Info $info, $params = array()) {
		global $backend;
		$this->backend = $backend;
		$this->_infoId = $info->getId();
		$this->_userId = isset($params['userId']) ? $params['userId'] : 0;
		parent::__construct('info_' . $this->_infoId . '_' . md5(serialize($params)));
	}

	public function save($data) {
		$this->_tags[] = 'info';
		$this->_tags[] = 'info_' . $this->_infoId;
		if ($this->_userId) {
			$this->_tags[] = 'user_' . $this->_userId;
		}
		parent::save($data);
	}

}

