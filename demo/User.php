<?php

/**
 * Static demo class User
 *
 * @author gbatanov
 * @version v.0.3
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
	 * Имитация получения данных из БД.
	 * 
	 * @return array
	 */
	private function getUserParams() {
		for ($i = 0; $i < 1000; ++$i) {
			
		};
		return $this->_params;
	}

	/**
	 * Добавляем параметры без чистки кэша.
	 * Данные в кэше становятся неактуальными.
	 * 
	 * @param mixed $params
	 */
	public function addParams($params) {
		$this->_params[] = $params;
	}

	/**
	 * Добавляем параметры и чистим кэш параметров этого пользователя.
	 * Данные в кэше будут обновлены при первом обращении к нему.
	 * 
	 * @param mixed $params
	 */
	public function addParamsWithAutoCleaning($params) {
		$this->_params[] = $params;
		ClearCache::clearTags((array) 'user_' . $this->Id);
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

	/**
	 * Сбрасываем параметры и очищаем кэш этого пользователя.
	 */
	public function resetParams() {
		$this->_params = array();
		ClearCache::clearTags((array) 'user_' . $this->Id);
	}

}

