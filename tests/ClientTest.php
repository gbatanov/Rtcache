<?php

/**
 * Test class for Rtcache_Client
 *
 * @author gbatanov
 */
class ClientTest extends PHPUnit_Framework_TestCase {

	private static $_redis = null;

	public function setUp() {
		parent::setUp();
	}

	public function tearDown() {
		if ($this->_redis)
			$this->_redis->close();
	}

	/**
	 * @dataProvider connectProvider
	 * @param string $host
	 * @param int $port
	 * @param int $database
	 * @param boolean $expected
	 */
	public function testConnect($host, $port, $database, $expected) {

		try {
			$this->_redis = new Rtcache_Client($host, $port);
			$result = $this->_redis->connect();
			$this->assertClassHasAttribute('connected', 'Rtcache_Client');
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
	 * @param type $timeout
	 */
	public function testSetReadTimeout($timeout, $expected) {
		try {
			$this->_redis = new Rtcache_Client('localhost', 6379);
			$this->_redis->connect();
			$this->_redis->setReadTimeout($timeout);
		} catch (Rtcache_Exception $e) {
			$this->assertEquals($expected, false);
		} catch (Exception $e) {
			$this->fail('An expected exception has not been raised.');
		}
	}

	public function setCall($name, $args, $expected) {
		try {
			
		} catch (Rtcache_Exception $e) {
			
		} catch (Exception $e) {
			
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
			array(1, true),
			array(-1, false),
		);
	}

}

