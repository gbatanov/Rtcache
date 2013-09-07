<?php

/**
 * autoloader for demo project
 * 
 * @author George Batanov <gsb@gsbmail.ru>
 * @version v.0.2
 * @package rtcache.demo
 */
function autoload($class) {

	$dirs = explode(';', get_include_path());

	$classFile = $class . '.php';
	$classFileUndercore = preg_replace('/_/s', '/', $class) . '.php';

	foreach ($dirs as $dir) {
		$dirLastChar = mb_substr($dir, -1);
		if ($dirLastChar != '/' && $dirLastChar != '\\')
			$dir .= '/';

		$classPath = $dir . $classFile;
		$classPathUndercore = $dir . $classFileUndercore;

		if (file_exists($classPath) && is_file($classPath)) {
			require_once(realpath($classPath));
			return;
		} else if (file_exists($classPathUndercore) && is_file($classPathUndercore)) {
			require_once(realpath($classPathUndercore));
			return;
		}
	}
}

set_include_path(
	get_include_path() .
	PATH_SEPARATOR . dirname(__FILE__) .
	PATH_SEPARATOR . dirname(__FILE__) . '/../..'
);

spl_autoload_register('autoload');