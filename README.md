Rtcache
====

Redis tagged cache engine

Folder structure:
----------------------
	
	|-rtcache
		|-Backend.php
		|-Slot.php
	|-demo 
		|-ClearCache.php
		|-User.php
		|-Info.php
		|-UserSlot.php
		|-InfoSlot.php
		|-autoload.php
		|-index.php
	|-vendor
		|-colinmollenhour
			|-credis
				|-Client.php 
				|-other files

After downloading run in command line:

	>cd path/to/Rtcache

	>php composer.phar install


Rtcache_Client is using [Credis](https://github.com/colinmollenhour/credis).
 
Rtcache_CacheBackend based on code and ideas:
 - [Cm_Cache_Backend_Redis](https://github.com/colinmollenhour/Cm_Cache_Backend_Redis)
 - [Zend_Cache](http://framework.zend.com/manual/1.8/en/zend.cache.html)
 - [Dklab](http://dklab.ru/lib/Dklab_Cache/)


Rtcache is not tied to a specific Frameworks.
 
 - 05.09.2013 v.0.1
 - 08.09.2013 v.0.3
 
George Batanov
gsb@gsbmail.ru
