<?php

/**
 * Test class for Rtcache_Client
 *
 * @author gbatanov
 * @version v.0.4
 * @package rtcache.test
 */
class ClientTest extends PHPUnit_Framework_TestCase {

	private $_redis = null;

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		if ($this->_redis)
			$this->_redis->close();
	}

	/**
	 * @dataProvider connectProvider
	 * @covers Rtcache_Client::connect
	 * @param string $host
	 * @param int $port
	 * @param int $database
	 * @param boolean $expected
	 */
	public function testConnect($host, $port, $database, $expected) {

		try {
			$this->_redis = new Rtcache_Client($host, $port);
			$this->_redis->connect();
			$this->assertEquals(true, (boolean) $this->_redis->select($database));
		} catch (Rtcache_Exception $e) {
			print($e->getMessage());
			$this->assertEquals($expected, false);
		} catch (Exception $e) {
			print($e->getMessage());
			$this->fail('An expected exception has not been raised.');
		}
	}

	/**
	 * @dataProvider setReadTimeoutProvider
	 * @covers Rtcache_Client::setReadTimeout
	 * @param type $timeout
	 * @param boolean $expected
	 */
	public function testSetReadTimeout($timeout, $expected) {
		try {
			$this->_redis = new Rtcache_Client('localhost', 6379);
			$this->_redis->connect();
			$this->assertEquals($this->_redis->setReadTimeout($timeout), $expected);
		} catch (Rtcache_Exception $e) {
			$this->assertEquals($expected, false);
		} catch (Exception $e) {
			$this->fail('An expected exception has not been raised.');
		}
	}

	/**
	 * @dataProvider callProvider
	 * @covers Rtcache::__call
	 * @param string $name
	 * @param array $args
	 * @param boolean $expected
	 */
	public function testCall($name, $args, $expected) {
		try {
			$this->_redis = new Rtcache_Client('localhost', 6379);
			$this->_redis->connect();
			$this->assertEquals($this->_redis->__call($name, $args), $expected);
		} catch (Rtcache_Exception $e) {
			$this->assertEquals($expected, false);
		} catch (Exception $e) {
			$this->fail('An expected exception has not been raised.');
		}
	}

	/**
	 * @covers Rtcache_Client::close
	 */
	public function testClose() {
		$this->assertEquals(false, false);
	}

	/**
	 * @dataProvider setMaxConnectRetriesProvider
	 * @covers Rtcache_Client::setMaxConnectRetries
	 * @param type $retries
	 * @param type $expected
	 */
	public function testSetMaxConnectRetries($retries, $expected) {
		try {
			$this->_redis = new Rtcache_Client('localhost', 6379);
			$this->_redis->connect();
			$this->assertEquals($this->_redis->setMaxConnectRetries($retries), $expected);
		} catch (Rtcache_Exception $e) {
			$this->fail('An expected exception has not been raised.');
		} catch (Exception $e) {
			$this->fail('An expected exception has not been raised.');
		}
	}

	public function connectProvider() {
		return array(
			array('localhost', 6379, 1, true),
			array('loclhost', 6379, 1, false),
			array('localhost', 6380, 1, false),
		);
	}

	public function setReadTimeoutProvider() {
		return array(
			array(1.67, 1.67),
			array(1, 1),
			array(-1, false),
		);
	}

	public function callProvider() {
		return array(
			array('select', 1, true),
			array('flushdb', null, true),
			array('get', array('mykey'), false),
			array('set', array('mykey', 'hello'), true),
			array('get', array('mykey'), 'hello'),
			array('set', array('mykey', 'hello', 'px', 1), true),
			array('get', array('mykey'), false),
			array('hset', array('mykey', 'field1', 'hello'), 1),
			array('hset', array('mykey', 'field1', 'hello2'), 0),
			array('hget', array('mykey', 'field2'), false),
			array('hget', array('mykey', 'field1'), 'hello2'),
			array('flushdb', null, true),
			array('sadd', array('mykey1', 'hello1'), 1),
			array('sadd', array('mykey1', 'hello1'), 0),
			array('sadd', array('mykey1', 'hello2'), 1),
			array('smembers', array('mykey1'), array("hello2", "hello1")),
			array('sadd', array('mykey2', 'hello2'), 1),
			array('sinter', array('mykey1', 'mykey2'), array('hello2')),
			array('sunion', array('mykey1', 'mykey2'), array('hello1', 'hello2')),
		);
	}

	public function setMaxConnectRetriesProvider() {
		return array(
			array(2, 2),
			array(0, 0),
			array(-2, 0),
		);
	}

}

