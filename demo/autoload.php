<?php

/**
 * 
 */
function autoload($class) {
	$path = get_include_path();
	$dirs = explode(';', get_include_path());
	$dirs[] = dirname(__FILE__) . '/../../';

	$classFile = $class . '.php';
	$classFileUndercore = preg_replace('/_/s', '/', $class) . '.php';

	foreach ($dirs as $dir) {
		$dirLastChar = mb_substr($dir, -1);
		if ($dirLastChar != '/' && $dirLastChar != '\\')
			$dir .= '/';

		$classPath = $dir . $classFile;
		$classPathUndercore = $dir . $classFileUndercore;

		if (file_exists($classPath) && is_file($classPath)) {
			require_once($classPath);
			return;
		} else if (file_exists($classPathUndercore) && is_file($classPathUndercore)) {
			require_once($classPathUndercore);
			return;
		}
	}
}
spl_autoload_register('autoload');