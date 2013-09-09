<?php

/**
 * Rtcache_Client (a fork of Credis_Client)
 * 
 * @version v.0.4
 * @package rtcache
 */
if (!defined('CRLF'))
	define('CRLF', sprintf('%s%s', chr(13), chr(10)));

class Rtcache_Client {

	const FREAD_BLOCK_SIZE = 8192;

	/**
	 * Socket connection to the Redis server
	 * @var resource|Redis
	 */
	protected $redis;

	/**
	 * Host of the Redis server
	 * @var string
	 */
	protected $host;

	/**
	 * Port on which the Redis server is running
	 * @var integer
	 */
	protected $port;

	/**
	 * Timeout for connecting to Redis server
	 * @var float
	 */
	protected $timeout;

	/**
	 * Timeout for reading response from Redis server
	 * @var float
	 */
	protected $readTimeout;

	/**
	 * Unique identifier for persistent connections
	 * @var string
	 */
	protected $persistent;

	/**
	 * @var bool
	 */
	protected $closeOnDestruct = TRUE;

	/**
	 * @var bool
	 */
	protected $connected = FALSE;

	/**
	 * @var bool
	 */
//	protected $standalone;
	/**
	 * @var int
	 */
	protected $maxConnectRetries = 0;

	/**
	 * @var int
	 */
	protected $connectFailures = 0;

	/**
	 * @var array
	 */
	protected $commandNames;

	/**
	 * @var string
	 */
	protected $commands;

	/**
	 * @var bool
	 */
	protected $isMulti = FALSE;

	/**
	 * @var string
	 */
	protected $authPassword;

	/**
	 * @var int
	 */
	protected $selectedDb = 0;


	/**
	 * Creates a Redisent connection to the Redis server on host {@link $host} and port {@link $port}.
	 * string in the form of tcp://[hostname]:[port] 
	 *
	 * @param string $host The hostname of the Redis server
	 * @param integer $port The port number of the Redis server
	 * @param float $timeout  Timeout period in seconds
	 * @param string $persistent  Flag to establish persistent connection
	 */
	public function __construct($host = '127.0.0.1', $port = 6379, $timeout = null, $persistent = '') {
		$this->host = (string) $host;
		$this->port = (int) $port;
		$this->timeout = $timeout;
		$this->persistent = (string) $persistent;
	}

	public function __destruct() {
		if ($this->closeOnDestruct) {
			$this->close();
		}
	}

	/**
	 * @param int $retries
	 * @return Credis_Client
	 */
	public function setMaxConnectRetries($retries) {
		$this->maxConnectRetries = $retries;
		return $this;
	}

	/**
	 * @param bool $flag
	 * @return Credis_Client
	 */
	public function setCloseOnDestruct($flag) {
		$this->closeOnDestruct = $flag;
		return $this;
	}

	/**
	 * @throws Rtcache_Exception
	 * @return Rtcache_Client
	 */
	public function connect() {
		if ($this->connected) {
			return $this;
		}
		$errno = false;
		$errstr = false;
	
		$flags = STREAM_CLIENT_CONNECT;
		$remote_socket =  'tcp://' . $this->host . ':' . $this->port;
		if ($this->persistent) {
			$remote_socket .= '/' . $this->persistent;
			$flags = $flags | STREAM_CLIENT_PERSISTENT;
		}
		$result = $this->redis = @stream_socket_client($remote_socket, $errno, $errstr, $this->timeout !== null ? $this->timeout : 2.5, $flags);


		// Use recursion for connection retries
		if (!$result) {
			$this->connectFailures++;
			if ($this->connectFailures <= $this->maxConnectRetries) {
				return $this->connect();
			}
			$failures = $this->connectFailures;
			$this->connectFailures = 0;
			throw new Rtcache_Exception("Connection to Redis failed after $failures failures.");
		}

		$this->connectFailures = 0;
		$this->connected = TRUE;

		// Set read timeout
		if ($this->readTimeout) {
			$this->setReadTimeout($this->readTimeout);
		}

		return $this;
	}

	/**
	 * Set the read timeout for the connection. If falsey, a timeout will not be set. Negative values not supported.
	 *
	 * @param $timeout
	 * @throws Rtcache_Exception
	 * @return Rtcache_Client
	 */
	public function setReadTimeout($timeout) {
		if ($timeout < 0) {
			throw new Rtcache_Exception('Negative read timeout values are not supported.');
		}
		$this->readTimeout = $timeout;
		if ($this->connected) {
			stream_set_timeout($this->redis, (int) floor($timeout), ($timeout - floor($timeout)) * 1000000);
		}
		return $this;
	}

	/**
	 * closing connection
	 * 
	 * @return bool
	 */
	public function close() {
		$result = TRUE;
		if ($this->connected && !$this->persistent) {
			try {
				$result = fclose($this->redis);
				$this->connected = FALSE;
			} catch (Exception $e) {
				$result = false; // Ignore exceptions on close
			}
		}
		return $result;
	}

	/**
	 * Authenification query
	 * 
	 * @param string $password
	 * @return bool
	 */
	public function auth($password) {
		$this->authPassword = $password;
		$response = $this->__call('auth', array($this->authPassword));
		return $response;
	}

	/**
	 * Select database
	 * 
	 * @param int $index
	 * @return bool
	 */
	public function select($index) {
		$this->selectedDb = (int) $index;
		$response = $this->__call('select', array($this->selectedDb));
		return $response;
	}

	/**
	 * Magic method that performs a specified command
	 * 
	 * @param string $name
	 * @param array $args
	 * @return Rtcache_Client
	 */
	public function __call($name, $args) {

		$this->connect();

		$name = strtolower($name);

		// Flatten arguments
		$argsFlat = NULL;
		foreach ($args as $index => $arg) {
			if (is_array($arg)) {
				if ($argsFlat === NULL) {
					$argsFlat = array_slice($args, 0, $index);
				}
				if ($name == 'mset' || $name == 'msetnx' || $name == 'hmset') {
					foreach ($arg as $key => $value) {
						$argsFlat[] = $key;
						$argsFlat[] = $value;
					}
				} else {
					$argsFlat = array_merge($argsFlat, $arg);
				}
			} else if ($argsFlat !== NULL) {
				$argsFlat[] = $arg;
			}
		}
		if ($argsFlat !== NULL) {
			$args = $argsFlat;
			$argsFlat = NULL;
		}

		// transaction mode
		if ($this->isMulti) {
			if ($name == 'exec') {
				$this->commandNames[] = $name;
				$this->commands .= self::_prepare_command(array($name));

				// Write request to server
				if ($this->commands) {
					$this->write_command($this->commands);
				}
				$this->commands = NULL;

				// Read response fom server
				$response = array();
				foreach ($this->commandNames as $command) {
					$response[] = $this->readReply();
				}
				$this->commandNames = NULL;

				$response = array_pop($response);
				$this->isMulti = FALSE;
				return $response;
			} else {
				array_unshift($args, $name); // name will be the first argument
				$this->commandNames[] = $name;
				$this->commands .= self::_prepare_command($args);
				return $this;
			}
		}
		// Start transaction mode
		if ($name == 'multi') {
			$this->isMulti = TRUE;
			$this->commandNames = array();
			$this->commands = '';
			return $this;
		}

		// standard mode
		array_unshift($args, $name); // name will be the first argument
		$command = self::_prepare_command($args);
		$this->write_command($command);
		$response = $this->readReply();

		return $response;
	}

	/**
	 * Write command to socket
	 * 
	 * @param string $command
	 * @throws Rtcache_Exception
	 */
	protected function write_command($command) {
		// Reconnect on lost connection (Redis server "timeout" exceeded since last command)
		if (feof($this->redis)) {
			$this->close();
			// If transaction was in progress and connection was lost, throw error rather than reconnect
			// since transaction state will be lost.
			if ($this->isMulti) {
				$this->isMulti = FALSE;
				throw new Rtcache_Exception('Lost connection to Redis server during transaction.');
			}
			$this->connected = FALSE;
			$this->connect();
			if ($this->authPassword) {
				$this->auth($this->authPassword);
			}
			if ($this->selectedDb != 0) {
				$this->select($this->selectedDb);
			}
		}

		$commandLen = strlen($command);
		for ($written = 0; $written < $commandLen; $written += $fwrite) {
			$fwrite = fwrite($this->redis, substr($command, $written));
			if ($fwrite === FALSE) {
				throw new Rtcache_Exception('Failed to write entire command to stream');
			}
		}
	}

	/**
	 * Read reply from Redis server
	 * 
	 * @return mixed
	 * @throws Rtcache_Exception
	 */
	protected function readReply() {
		$reply = fgets($this->redis);
		if ($reply === FALSE) {
			throw new Rtcache_Exception('Lost connection to Redis server.');
		}
		$reply = rtrim($reply, CRLF);
		$replyType = substr($reply, 0, 1);
		switch ($replyType) {
			case '-': //Negative reply
				if ($this->isMulti ) {
					$response = FALSE;
				} else {
					throw new Rtcache_Exception(substr($reply, 4));
				}
				break;
			case '+': // Positive reply
				$response = substr($reply, 1);
				if ($response == 'OK' || $response == 'QUEUED') {
					return TRUE;
				}
				break;

			case '$':// Bulk reply 
				if ($reply == '$-1')
					return FALSE;
				$size = (int) substr($reply, 1);
				$response = stream_get_contents($this->redis, $size + 2);
				if (!$response)
					throw new Rtcache_Exception('Error reading reply.');
				$response = substr($response, 0, $size);
				break;

			case '*':// Multi-bulk reply 
				$count = substr($reply, 1);
				if ($count == '-1')
					return FALSE;

				$response = array();
				for ($i = 0; $i < $count; $i++) {
					$response[] = $this->readReply();
				}
				break;

			case ':':// Integer reply 
				$response = intval(substr($reply, 1));
				break;
			default:
				throw new Rtcache_Exception('Invalid response: ' . print_r($reply, TRUE));
				break;
		}

		return $response;
	}

	/**
	 * Build the Redis unified protocol command <http://redis.io/commands>
	 *
	 * @param array $args
	 * @return string
	 */
	private static function _prepare_command($args) {
		return sprintf('*%d%s%s%s', count($args), CRLF, implode(CRLF, array_map(array('self', '_map'), $args)), CRLF);
	}

	private static function _map($arg) {
		return sprintf('$%d%s%s', strlen($arg), CRLF, $arg);
	}

}
