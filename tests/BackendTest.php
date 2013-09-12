<?php

/**
 * Test class for Rtcache_Backend
 *
 * @author gbatanov
 * @version v.0.4
 * @package rtcache.test
 */
class BackendTest extends PHPUnit_Framework_TestCase {

	private $_backend = null;
	private $_options = array('server' => 'localhost', 'port' => 6379, 'database' => 1);

	public function setUp() {
		
	}

	public function tearDown() {
		if ($this->_backend) {
			$this->_backend->clean(Rtcache_Backend::CLEANING_MODE_ALL);
			$this->_backend->close();
		}
	}

	/**
	 * @dataProvider constructProvider
	 * @covers Rtcache_Backend::__construct
	 * @param array $options
	 * @param boolean $expected expected result
	 * @param boolean $exception expected exception or no
	 */
	public function testConstruct($options, $exception) {
		try {
			$this->_backend = new Rtcache_Backend($options);
			$this->assertClassHasAttribute('_redis', 'Rtcache_Backend');
		} catch (Rtcache_Exception $re) {
			$this->assertEquals($exception, TRUE);
		} catch (CredisException $ce) {
			$this->assertEquals($exception, TRUE);
		} catch (Exception $e) {
			$this->fail("Unexpected exception");
		}
	}

	/**
	 * @covers Rtcache_Backend::close
	 */
	public function testClose() {
		try {
			$backend = new Rtcache_Backend($this->_options);
			$backend->close();
			$this->assertEquals(TRUE, TRUE);
		} catch (Exception $e) {
			$this->fail("Unexpected exception");
		}
	}

	/**
	 * @dataProvider saveProvider
	 * @covers Rtcache_Backend::save
	 * @param mixed $data
	 * @param string $id
	 * @param array $tags
	 * @param int $specificLifetime
	 */
	public function testSave($data, $id, $tags = array(), $specificLifetime = false) {
		try {
			$data1 = serialize($data);
			$this->_backend = new Rtcache_Backend($this->_options);
			$this->assertEquals($this->_backend->save($data1, $id, $tags, $specificLifetime), TRUE);
			$this->assertEquals($this->_backend->load($id), $data1);
			sleep($specificLifetime + 1);
			$this->assertEquals($this->_backend->load($id), FALSE);
		} catch (Exception $e) {
			$this->fail('Unexpected exception: ' . $e->getMessage());
		}
	}

	/**
	 * @dataProvider saveProvider
	 * @covers Rtcache_Backend::load
	 * @param mixed $data
	 * @param string $id
	 * @param array $tags
	 * @param int $specificLifetime
	 */
	public function testLoad($data, $id, $tags = array(), $specificLifetime = false) {
		try {
			$data1 = serialize($data);
			$this->_backend = new Rtcache_Backend($this->_options);
			$this->assertEquals($this->_backend->save($data1, $id, $tags, $specificLifetime), TRUE);
			$this->assertEquals($this->_backend->load($id), $data1);
			$this->assertEquals($this->_backend->load($id . 'bad'), FALSE);
		} catch (Exception $e) {
			$this->fail('Unexpected exception: ' . $e->getMessage());
		}
	}

	/**
	 * @dataProvider removeProvider
	 * @covers Rtcache_Backend::remove
	 * @param mixed $data
	 * @param string $id
	 * @param array $tags
	 * @param int $specificLifetime
	 */
	public function testRemove($data, $id, $tags = array(), $specificLifetime = false) {
		try {
			$this->_backend = new Rtcache_Backend($this->_options);
			$this->assertEquals($this->_backend->save($data, $id, $tags, $specificLifetime), TRUE);
			$this->assertEquals($this->_backend->load($id), $data);
			$this->assertEquals($this->_backend->remove($id), TRUE);
			$this->assertEquals($this->_backend->load($id), FALSE);
			$this->assertEquals($this->_backend->save($data, $id, $tags, $specificLifetime), TRUE);
			$this->assertEquals($this->_backend->load($id), $data);
			$this->assertEquals($this->_backend->remove($id . 'bad'), FALSE);
			$this->assertEquals($this->_backend->load($id), $data);
		} catch (Exception $e) {
			$this->fail('Unexpected exception: ' . $e->getMessage());
		}
	}

	/**
	 * @covers Rtcache_Backend::clean
	 */
	public function testClean() {
		try {
			$this->_backend = new Rtcache_Backend($this->_options);
			$this->assertEquals($this->_backend->save('data1', 'id1', array('tag1', 'tag2', 'tag3'), false), TRUE);
			$this->assertEquals($this->_backend->save('data2', 'id2', array('tag1', 'tag2', 'tag4'), false), TRUE);
			$this->assertEquals($this->_backend->load('id1'), 'data1');
			$this->assertEquals($this->_backend->load('id2'), 'data2');
			$this->_backend->clean(Rtcache_Backend::CLEANING_MODE_ALL);
			$this->assertEquals($this->_backend->load('id1'), FALSE);
			$this->assertEquals($this->_backend->load('id2'), FALSE);
			$this->assertEquals($this->_backend->save('data1', 'id1', array('tag1', 'tag2', 'tag3'), 2), TRUE);
			$this->assertEquals($this->_backend->save('data2', 'id2', array('tag1', 'tag2', 'tag4'), 200), TRUE);
			$this->assertEquals($this->_backend->load('id1'), 'data1');
			$this->assertEquals($this->_backend->load('id2'), 'data2');
			sleep(2);
			$this->_backend->clean(Rtcache_Backend::CLEANING_MODE_OLD);
			$this->assertEquals($this->_backend->load('id1'), FALSE);
			$this->assertEquals($this->_backend->load('id2'), 'data2');
			$this->assertEquals($this->_backend->save('data1', 'id1', array('tag2', 'tag3'), false), TRUE);
			$this->assertEquals($this->_backend->save('data2', 'id2', array('tag2', 'tag4'), false), TRUE);
			$this->assertEquals($this->_backend->load('id1'), 'data1');
			$this->assertEquals($this->_backend->load('id2'), 'data2');
			$this->_backend->clean(Rtcache_Backend::CLEANING_MODE_MATCHING_TAG, array('tag2'));
			$this->assertEquals($this->_backend->load('id1'), FALSE);
			$this->assertEquals($this->_backend->load('id2'), FALSE);
			$this->assertEquals($this->_backend->save('data1', 'id1', array('tag2', 'tag3'), false), TRUE);
			$this->assertEquals($this->_backend->save('data2', 'id2', array('tag2', 'tag4'), false), TRUE);
			$this->assertEquals($this->_backend->load('id1'), 'data1');
			$this->assertEquals($this->_backend->load('id2'), 'data2');
			$this->_backend->clean(Rtcache_Backend::CLEANING_MODE_MATCHING_TAG, array('tag2', 'tag3'));
			$this->assertEquals($this->_backend->load('id1'), FALSE);
			$this->assertEquals($this->_backend->load('id2'), 'data2');
		} catch (Exception $e) {
			$this->fail('Unexpected exception: ' . $e->getMessage());
		}
	}

	public function constructProvider() {
		return array(
			array(array('server' => 'localhost', 'port' => 6379), false),
			array(array('server' => 'localhost2', 'port' => 6379), true),
			array(array('server' => 'localhost', 'port' => 80), true),
		);
	}

	public function saveProvider() {
		return array(
			array('data1', 'id1', array('tag1', 'tag2'), 1),
			array(array('data1', 'data2'), 'id1', array('tag1', 'tag2'), 1),
			array(null, 'id1', array(), 1),
			array(false, 'id1', array(), 1),
		);
	}

	public function removeProvider() {
		return array(
			array('data1', 'id1', array('tag1', 'tag2'), 10),
			array('data1', 'id2', array(), 10),
		);
	}

}

