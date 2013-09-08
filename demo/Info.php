<?php

/**
 * Static demo class User.
 * Пример класса, в качестве источника данных используется массив,
 * имитирующий модель.
 *
 * @author gbatanov
 * @version v.0.3
 * @package rtcache.demo
 */
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
	 * Imitation get data from database.
	 * Имитация получения данных из модели.
	 * 
	 * @return array
	 */
	private function _getInfoData($userId) {
		for ($i = 0; $i < 1000; ++$i) {
			
		};
		return isset($this->_infoArray[$userId]) ? $this->_infoArray[$userId] : null;
	}

	/**
	 * Функция получения параметров пользователя по его ID.
	 * Поскольку для одного пользователя может существовать несколько
	 * различных функций, получающих различные наборы данных, в параметрах, 
	 * передаваемых в слот, кроме ID пользователя передаем имя метода.
	 * 
	 * @param int $userId
	 * @return array
	 */
	public function getInfo($userId) {
		$params = array();
		$params['funcName'] = __METHOD__;
		$params['userId'] = $userId;
		$slot = new InfoSlot($this, $params);
		$data = $slot->load();
		if ($data === false) {
			$data = $this->_getInfoData($userId);
		}
		return $data;
	}

}

