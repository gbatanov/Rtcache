<?php

/**
 * Working UserSlot class
 *
 * @author gbatanov
 * @version v.0.1
 * @package rtcache.demo
 */
namespace Rtcache\demo;
class InfoSlot extends \Rtcache\Cache\Slot {

	protected $lifetime = 60;
	private $_infoId = 0;
	private $_userId = 0;

	public function __construct(Info $info, $userId) {
		$this->_infoId = $info->getId();
		$this->_userId = $userId;
		parent::__construct('info_' . $this->_infoId . '_' . $this->_userId);
	}

	public function _getBackend() {
		global $backend;
		return $backend;
	}

	public function save($data) {
		$tag = new Tag('info');
		$this->addTag($tag);
		$tag2 = new Tag('info_' . $this->_infoId);
		$this->addTag($tag2);
		$tag3 = new Tag('user_' . $this->_userId);
		$this->addTag($tag3);
		parent::save($data);
	}
}
	