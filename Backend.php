<?php

/**
 * Redis adapter 
 *
 * @version v.0.3
 * @package Rtcache
 */
class Rtcache_Backend {
//modes

	const CLEANING_MODE_ALL = 'all';
	const CLEANING_MODE_OLD = 'old';
	const CLEANING_MODE_MATCHING_TAG = 'matchingTag';
	const CLEANING_MODE_MATCHING_ANY_TAG = 'matchingAnyTag';
	// set all tags, need for garbadge clearing
	const SET_TAGS = 'rtc_tags';
	// prefixes
	const PREFIX_KEY_IDS = 'rtc_ki:';
	const PREFIX_TAG_IDS = 'rtc_ti:';
	// fields
	const FIELD_DATA = 'rtc_d';
	const FIELD_TAGS = 'rtc_t';
	// lifetime limit, default 1 month
	const MAX_LIFETIME = 2592000;
	const DEFAULT_CONNECT_TIMEOUT = 2.5;
	const DEFAULT_CONNECT_RETRIES = 1;

	/** @var Credis_Client */
	protected $_redis;

	/**
	 * Contruct Rtcache_Backend backend
	 * 
	 * @param array $options
	 * @return Rtcache_Backend
	 */
	public function __construct($options = array()) {
		if (empty($options['server'])) {
			self::throwException('Redis \'server\' not specified.');
		}

		if (empty($options['port']) && substr($options['server'], 0, 1) != '/') {
			self::throwException('Redis \'port\' not specified.');
		}

		$timeout = isset($options['timeout']) ? $options['timeout'] : self::DEFAULT_CONNECT_TIMEOUT;
		$persistent = isset($options['persistent']) ? $options['persistent'] : '';
		$this->_redis = new Rtcache_Client($options['server'], $options['port'], $timeout, $persistent);


		$connectRetries = isset($options['connect_retries']) ? (int) $options['connect_retries'] : self::DEFAULT_CONNECT_RETRIES;
		$this->_redis->setMaxConnectRetries($connectRetries);

		if (!empty($options['read_timeout']) && $options['read_timeout'] > 0) {
			$this->_redis->setReadTimeout((float) $options['read_timeout']);
		}

		if (!empty($options['password'])) {
			$this->_redis->auth($options['password']) or self::throwException('Unable to authenticate with the redis server.');
		}

		// Always select database on startup in case persistent connection is re-used by other code
		if (empty($options['database'])) {
			$options['database'] = 0;
		}
		$this->_redis->select((int) $options['database']) or self::throwException('The redis database could not be selected.');


		if (isset($options['lifetimelimit'])) {
			$this->_lifetimelimit = (int) min($options['lifetimelimit'], self::MAX_LIFETIME);
		}
	}

	/**
	 * Load value with given id from cache
	 *
	 * @param  string  $id Cache id
	 * @return bool|string
	 */
	public function load($id) {
		$data = $this->_redis->hGet(self::PREFIX_KEY_IDS . $id, self::FIELD_DATA);
		if ($data === NULL) {
			return FALSE;
		}
		return $data;
	}

	/**
	 * Save some string datas into a cache record
	 *
	 * Note : $data is always "string" (serialization is done by the
	 * core not by the backend)
	 *
	 * @param  string $data             Datas to cache
	 * @param  string $id               Cache id
	 * @param  array  $tags             Array of strings, the cache record will be tagged by each string entry
	 * @param  bool|int $specificLifetime If != false, set a specific lifetime for this cache record else MAX_LIFITIME
	 * @throws CredisException
	 * @return boolean True if no problem
	 */
	public function save($data, $id, $tags = array(), $specificLifetime = false) {
		if (!is_array($tags))
			$tags = array($tags);

		$lifetime = $this->getLifetime($specificLifetime);

		// Get list of tags previously assigned
		$oldTags = $this->_redis->hGet(self::PREFIX_KEY_IDS . $id, self::FIELD_TAGS);
		$oldTags = $oldTags ? explode(',', $oldTags) : array();

//		$this->_redis->pipeline()->multi();
		$this->_redis->multi();

		// Set the data
		$result = $this->_redis->hMSet(self::PREFIX_KEY_IDS . $id, array(
			self::FIELD_DATA => $data,
			self::FIELD_TAGS => implode(',', $tags),
		));
		if (!$result) {
			throw new Rtcache_Exception("Could not set cache key $id");
		}

		// Always expire so the volatile-* eviction policies may be safely used, otherwise
		// there is a risk that tag data could be evicted.
		$this->_redis->expire(self::PREFIX_KEY_IDS . $id, $lifetime);

		// Process added tags
		if ($addTags = ($oldTags ? array_diff($tags, $oldTags) : $tags)) {
			// Update the list with all the tags
			$this->_redis->sAdd(self::SET_TAGS, $addTags);
			// Update the id list for each tag
			foreach ($addTags as $tag) {
				$this->_redis->sAdd(self::PREFIX_TAG_IDS . $tag, $id);
			}
		}

		// Process removed tags
		if ($remTags = ($oldTags ? array_diff($oldTags, $tags) : FALSE)) {
			// Update the id list for each tag
			foreach ($remTags as $tag) {
				$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $id);
			}
		}
		$this->_redis->exec();

		return TRUE;
	}

	/**
	 * Remove a cache record
	 *
	 * @param  string $id Cache id
	 * @return boolean True if no problem
	 */
	public function remove($id) {
		// Get list of tags for this id
		$tags = explode(',', $this->_redis->hGet(self::PREFIX_KEY_IDS . $id, self::FIELD_TAGS));

//		$this->_redis->pipeline()->multi();
		$this->_redis->multi();

		// Remove data
		$this->_redis->del(self::PREFIX_KEY_IDS . $id);


		// Update the id list for each tag
		foreach ($tags as $tag) {
			$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $id);
		}

		$result = $this->_redis->exec();

		return (bool) $result[0];
	}

	/**
	 * Remove cache records by all given tags
	 * 
	 * @param array $tags
	 */
	protected function _removeByMatchingTags($tags) {
		$ids = $this->getIdsMatchingTags($tags);
		if ($ids) {
//			$this->_redis->pipeline()->multi();
			$this->_redis->multi();

			// Remove data
			$this->_redis->del($this->_preprocessIds($ids));

			$this->_redis->exec();
		}
	}

	/**
	 * @param array $tags
	 */
	protected function _removeByMatchingAnyTags($tags) {
		$ids = $this->getIdsMatchingAnyTags($tags);

//		$this->_redis->pipeline()->multi();
		$this->_redis->multi();

		if ($ids) {
			// Remove data
			$this->_redis->del($this->_preprocessIds($ids));
		}

		// Remove tag id lists
		$this->_redis->del($this->_preprocessTagIds($tags));

		// Remove tags from list of tags
		$this->_redis->sRem(self::SET_TAGS, $tags);

		$this->_redis->exec();
	}

	/**
	 * Clean up tag id lists since as keys expire the ids remain in the tag id lists
	 */
	protected function _collectGarbage() {
		// Clean up expired keys from tag id set and global id set
		$exists = array();
		$tags = (array) $this->_redis->sMembers(self::SET_TAGS);
		foreach ($tags as $tag) {
			// Get list of expired ids for each tag
			$tagMembers = $this->_redis->sMembers(self::PREFIX_TAG_IDS . $tag);
			$numTagMembers = count($tagMembers);
			$expired = array();
			$numExpired = $numNotExpired = 0;
			if ($numTagMembers) {
				while ($id = array_pop($tagMembers)) {
					if (!isset($exists[$id])) {
						$exists[$id] = $this->_redis->exists(self::PREFIX_KEY_IDS . $id);
					}
					if ($exists[$id]) {
						$numNotExpired++;
					}
					else {
						$numExpired++;
						$expired[] = $id;

						// Remove incrementally to reduce memory usage
						if (count($expired) % 100 == 0 && $numNotExpired > 0) {
							$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $expired);
							$expired = array();
						}
					}
				}
				if (!count($expired))
					continue;
			}

			// Remove empty tags or completely expired tags
			if ($numExpired == $numTagMembers) {
				$this->_redis->del(self::PREFIX_TAG_IDS . $tag);
				$this->_redis->sRem(self::SET_TAGS, $tag);
			}
			// Clean up expired ids from tag ids set
			else if (count($expired)) {
				$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $expired);
			}
			unset($expired);
		}
	}

	/**
	 * Clean some cache records
	 *
	 * Available modes are :
	 * 'all'   => remove all cache entries ($tags is not used)
	 * 'old'   => runs _collectGarbage()
	 * 'matchingTag'(default)    => clears the entries in cache which contains all of the given tag in the set of tags
	 * 'matchingAnyTag' => clears the cache entry, for which at least one of the set of tags is present in a set of tags
	 *
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @throws Rtcache_Exception
	 * @return boolean True if no problem
	 */
	public function clean($mode = self::CLEANING_MODE_MATCHING_TAG, $tags = array()) {
		if ($tags && !is_array($tags)) {
			$tags = array($tags);
		}

		if ($mode == self::CLEANING_MODE_ALL) {
			return $this->_redis->flushDb();
		}

		if ($mode == self::CLEANING_MODE_OLD) {
			$this->_collectGarbage();
			return TRUE;
		}

		if (!count($tags)) {
			return TRUE;
		}

		$result = TRUE;

		switch ($mode) {
			case self::CLEANING_MODE_MATCHING_TAG:
				$this->_removeByMatchingTags($tags);
				break;
			case self::CLEANING_MODE_MATCHING_ANY_TAG:
				$this->_removeByMatchingAnyTags($tags);
				break;
			default:
				self::throwException('Invalid mode for clean() method: ' . $mode);
		}
		return (bool) $result;
	}

	/**
	 * Get the life time
	 *
	 * if $specificLifetime is not false, the given specific life time is used
	 * else, the global lifetime is used
	 *
	 * @param  int $specificLifetime
	 * @return int Cache life time
	 */
	public function getLifetime($specificLifetime) {
		return (int) $specificLifetime > 0 ? (int) $specificLifetime : self::MAX_LIFETIME;
	}

	/**
	 * Return an array of stored cache ids which match given tags
	 *
	 * In case of multiple tags, a logical AND is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of matching cache ids (string)
	 */
	public function getIdsMatchingTags($tags = array()) {
		if ($tags) {
			return (array) $this->_redis->sInter($this->_preprocessTagIds($tags));
		}
		return array();
	}

	/**
	 * Return an array of stored cache ids which match any given tags
	 *
	 * In case of multiple tags, a logical OR is made between tags
	 *
	 * @param array $tags array of tags
	 * @return array array of any matching cache ids (string)
	 */
	public function getIdsMatchingAnyTags($tags = array()) {
		if ($tags) {
			return (array) $this->_redis->sUnion($this->_preprocessTagIds($tags));
		}
		return array();
	}

	/**
	 * @param $item
	 * @param $index
	 * @param $prefix
	 */
	protected function _preprocess(&$item, $index, $prefix) {
		$item = $prefix . $item;
	}

	/**
	 * @param $ids
	 * @return array
	 */
	protected function _preprocessIds($ids) {
		array_walk($ids, array($this, '_preprocess'), self::PREFIX_KEY_IDS);
		return $ids;
	}

	/**
	 * @param $tags
	 * @return array
	 */
	protected function _preprocessTagIds($tags) {
		array_walk($tags, array($this, '_preprocess'), self::PREFIX_TAG_IDS);
		return $tags;
	}

	public static function throwException($msg) {
		throw new Rtcache_Exception($msg);
	}

}

class Rtcache_Exception extends Exception {
	
}