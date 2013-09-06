<?php

/**
 * Index file of demo project
 * 
 * @author George Batanov <gsb@gsbmail.ru>
 * @version v.0.1
 * @package rtcache.demo
 */
set_include_path(
	get_include_path() .
	PATH_SEPARATOR . dirname(__FILE__) .
	PATH_SEPARATOR . dirname(__FILE__) . '/../Cache'
);

ini_set('log_errors', 'on');
ini_set('error_log', 'php_errors.txt');
require_once dirname(__FILE__) . '/autoload.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

// Create backend
$options = array();
$options['server'] = 'localhost';
$options['port'] = 6379;
$backend = new Rtcache_Cache_Backend($options);
$user1 = new User();
$user2 = new User();
$info = new Info();

// Full cleaning
ClearCache::clearAll();

$user1->addParams('param_set1');
$user1->addParams('param_set2');
echo "Sets 1 and 2 for user1 <br />";
print_r($user1->getParams());
echo "<br />";
$user1->addParams('params_set3');
$user2->addParams('param_set1');
echo "Sets 1 for user2 before clean cache <br />";
print_r($user2->getParams());
echo "<br />";
$user2->addParams('param_set2');
$user2->addParams('params_set3');
echo "Sets 1 ,2,3 for user1 before clean cache <br />";
// user1 has three records, but since the cache is not changed, it really only get two
print_r($user1->getParams());
echo "<br />";
echo "Sets 1 ,2,3 for user2 before clean cache <br />";
// user2 have one record after caching , two other available
print_r($user2->getParams());
echo "<br />";
// Clean the cache for user1
$tag = new Tag('user_' . $user1->getId());
ClearCache::clearTags((array) $tag->getNativeId());
echo "Sets 1 ,2,3 for user1 after clean cache <br />";
// For user1 now displays all records
print_r($user1->getParams());
echo "<br />";
echo "Sets 1 ,2,3 for user2 after clean cache <br />";
// For user2 cache cleaning did not affect its data, output is still one record
print_r($user2->getParams());
echo "<br />";
// If you use a function with the addition of an automatic call to flush the cache,
//  it is always actual
echo '<br />With auto cleaning <br />';
$user1->resetParams();
$user1->addParamsWithAutoCleaning('param_set1');
$user1->addParamsWithAutoCleaning('param_set2');
echo "Sets 1 and 2 for user1 <br />";
print_r($user1->getParams());
echo "<br />";
$user1->addParamsWithAutoCleaning('params_set3');
echo "Sets 1 ,2,3 for user1 <br />";
print_r($user1->getParams());