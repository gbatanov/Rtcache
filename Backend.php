<?php

/**
 * Redis adapter for data cache 
 *
 * @version v.0.4
 * @package Rtcache
 */
class Rtcache_Backend {

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
	const MAX_LIFETIME = 2592000; //sec
	const DEFAULT_CONNECT_TIMEOUT = 2.5;
	const DEFAULT_CONNECT_RETRIES = 1;
	const DEFAULT_READ_TIMEOUT = 3000000; // 3 sec

	/** @var Credis_Client */

	protected $_redis = null;

	/**
	 * Contruct Rtcache_Backend backend
	 * 
	 * @param array $options
	 * @return Rtcache_Backend
	 */
	public function __construct($options = array()) {
		$server = isset($options['server']) ? $options['server'] : 'localhost';
		$port = isset($options['port']) ? $options['port'] : 6379;
		$timeout = isset($options['timeout']) ? $options['timeout'] : self::DEFAULT_CONNECT_TIMEOUT;
		$read_timeout = isset($options['read_timeout']) ? (float) $options['read_timeout'] : self::DEFAULT_READ_TIMEOUT;
		$persistent = isset($options['persistent']) ? $options['persistent'] : '';
		$connectRetries = isset($options['connect_retries']) ? (int) $options['connect_retries'] : self::DEFAULT_CONNECT_RETRIES;
		try {
			$this->_redis = new Rtcache_Client($server, $port, $timeout, $persistent);
			$this->_redis->setMaxConnectRetries($connectRetries);
			$this->_redis->setReadTimeout($read_timeout);

			if (!empty($options['password'])) {
				$this->_redis->auth($options['password']) or self::throwException('Unable to authenticate with the redis server.');
			}
		} catch (CredisException $e) {
			self::throwException($e->getMessage());
		}

		// Always select database on startup.
		// In case persistent connection is re-used by other code.
		if (empty($options['database'])) {
			$options['database'] = 0;
		}
		$this->_redis->select((int) $options['database']) or self::throwException('The redis database could not be selected.');

		// Lifetime cache records by default
		if (isset($options['lifetimelimit'])) {
			$this->_lifetimelimit = (int) min($options['lifetimelimit'], self::MAX_LIFETIME);
		}
	}

	/**
	 * Close backend
	 */
	public function close() {
		if (isset($this->_redis)) {
			$this->_redis->close();
			$this->_redis = null;
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

		// Start transaction
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
		// Ececute commands and end transaction
		$this->_redis->exec();

		return TRUE;
	}

	/**
	 * Remove a cache record by given Id. 
	 * The record is then deleted from lists for each tag associated with it. 
	 *
	 * @param  string $id Cache id
	 * @return boolean True if no problem
	 */
	public function remove($id) {
		// Get list of tags for this id
		$tags = $this->_redis->hGet(self::PREFIX_KEY_IDS . $id, self::FIELD_TAGS);
		// Start transaction
		$this->_redis->multi();
		// Remove data
		$this->_redis->del(self::PREFIX_KEY_IDS . $id);
		if (!empty($tags)) {
			$tags = (array) explode(',', $tags);
			// Update the id list for each tag
			foreach ($tags as $tag) {
				$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $id);
			}
		}
		// Execute all comand and end transaction
		$result = $this->_redis->exec();
		return (bool) $result[0];
	}

	/**
	 * Remove cache records by all given tags
	 * 
	 * @param array $tags
	 */
	protected function _removeByMatchingTags($tags) {
		//The list of records that have all of the tags specified.
		$ids = $this->getIdsMatchingTags($tags);
		if ($ids) {
			// Remove data
			$this->_redis->del($this->_preprocessIds($ids));
		}
	}

	/**
	 * Remove cache records by any given tags.
	 * applying - ????
	 * 
	 * @param array $tags
	 */
	protected function _removeByMatchingAnyTags($tags) {
		// List of records with at least one of the specified tag.
		$ids = $this->getIdsMatchingAnyTags($tags);
		// Start transaction
		$this->_redis->multi();
		if ($ids) {
			// Remove data
			$this->_redis->del($this->_preprocessIds($ids));
		}
		// Remove tag id lists
		$this->_redis->del($this->_preprocessTagIds($tags));
		// Remove tags from list of tags
		$this->_redis->sRem(self::SET_TAGS, $tags);
		// Execute all comand and end transaction
		$this->_redis->exec();
	}

	/**
	 * Clean up tag id lists since as keys expire the ids remain in the tag id lists
	 */
	protected function _collectGarbage() {
		// Clean up expired keys from tag id set and global id set
		$exists = array();
		$tags = (array) $this->_redis->sMembers(self::SET_TAGS);
		// Get list of expired ids for each tag
		foreach ($tags as $tag) {
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
					} else {
						$numExpired++;
						$expired[] = $id;

						// Remove incrementally to reduce memory usage
						if (count($expired) % 100 == 0 && $numNotExpired > 0) {
							$this->_redis->sRem(self::PREFIX_TAG_IDS . $tag, $expired);
							$expired = array();
						}
					}
				}
			}
			if (!count($expired))
				continue;

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
	 * 'all'   => remove all cache entries in current Db($tags is not used)
	 * 'old'   => runs _collectGarbage()
	 * 'matchingTag'(default)    => clears the entries in cache which contains
	 * 							 all of the given tag in the set of tags
	 * 'matchingAnyTag' => clears the cache entry, for which at least one 
	 * 						of the set of tags is present in a set of tags
	 *
	 * @param  string $mode Clean mode
	 * @param  array  $tags Array of tags
	 * @throws Rtcache_Exception
	 * @return boolean True if no problem
	 */
	public function clean($mode = self::CLEANING_MODE_MATCHING_TAG, $tags = array()) {
		if ($mode == self::CLEANING_MODE_ALL) {
			return $this->_redis->flushDb();
		}

		if ($mode == self::CLEANING_MODE_OLD) {
			$this->_collectGarbage();
			return TRUE;
		}

		if ($tags && !is_array($tags)) {
			$tags = array($tags);
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
	 * Get the life time of cache ids
	 *
	 * if $specificLifetime is not false, the given specific life time is used
	 * else the global lifetime is used
	 *
	 * @param  int $specificLifetime
	 * @return int Cache life time
	 */
	public function getLifetime($specificLifetime) {
		return (int) $specificLifetime > 0 ? (int) $specificLifetime : self::MAX_LIFETIME;
	}

	/**
	 * Return an array of stored cache ids which match given tags
	 * (logical AND  between tags)
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
	 * (logical OR between tags)
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
