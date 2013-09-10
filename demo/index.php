<?php

/**
 * Index file of demo project.
 * 
 * Simulated by adding parameters to the object and display a list of options
 * in a variety of situations: before clear the cache by the tag,
 * after clearing the cache by the tag.
 * 
 * @author George Batanov <gsb@gsbmail.ru>
 * @version v.0.4
 * @package rtcache.demo
 */
ini_set('log_errors', 'on');
ini_set('error_log', 'php_errors.txt');
require_once dirname(__FILE__) . '/autoload.php';
// Create backend
$options = array();
$options['server'] = 'localhost';
$options['port'] = 6379;
$options['database'] = 1; // different from working database = 0
$backend = new Rtcache_Backend($options);
$user1 = new User();
$user2 = new User();
$info = new Info();
if (isset($_GET['old'])) {
	ClearCache::clearOld();
	exit();
}
// Full clearing the cache
ClearCache::clearAll();
echo '<br />With manual clearing <br /><br />';
$user1->addParams('param_set1');
$user1->addParams('param_set2');
echo "Sets 1 and 2 for user1 <br />";
print_r($user1->getParams());
echo "<br />";
$user1->addParams('params_set3');
$user2->addParams('param_set1');
echo "Sets 1 for user2 before clear cache for user1<br />";
print_r($user2->getParams());
echo "<br /><br />";
$user2->addParams('param_set2');
$user2->addParams('params_set3');
echo "Sets 1,2,3 for user1,user2 before clear cache for user1<br />";
// user1 has three records, but since the cache is not changed, it really only get two
print_r($user1->getParams());
echo "<br />";
// user2 have one record after caching , two other unavailable
print_r($user2->getParams());
echo "<br /><br />";
// Clean the cache for user1
ClearCache::clearTags('user_' . $user1->getId());
echo "Sets 1,2,3 for user1, user2 after clear cache for user1<br />";
// For user1 now displays all records
print_r($user1->getParams());
echo "<br />";
// For user2 cache cleaning did not affect its data, output is still one record
print_r($user2->getParams());
echo "<br /><br />";
// If you use a function with the addition of an automatic call to flush the cache,
//  it is always actual
echo '<br />With auto clearing for user<br />';
$user1->resetParams();
$user1->addParamsWithAutoCleaning('param_set1');
$user1->addParamsWithAutoCleaning('param_set2');
echo "Sets 1 and 2 for user1 <br />";
print_r($user1->getParams());
echo "<br />";
$user1->addParamsWithAutoCleaning('params_set3');
echo "Sets 1,2,3 for user1 <br />";
print_r($user1->getParams());
