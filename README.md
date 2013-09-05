Rtcache
====

Redis tagged cache engine

Folder structure:
----------------------
	
	|-rtcache
		|-Cache
		|-vendor
			|-colinmollenhour
				|-credis
					|-Client.php
					|-other files

:

	>cd path/to/Rtcache

	>php composer.phar install

Rtcache_Client is using [Credis](https://github.com/colinmollenhour/credis). 
Rtcache_CacheBackend based on code and ideas:
 -[Cm_Cache_Backend_Redis](https://github.com/colinmollenhour/Cm_Cache_Backend_Redis)
 -[Zend_Cache](http://framework.zend.com/manual/1.8/en/zend.cache.html)
 -[Dklab](http://dklab.ru/lib/Dklab_Cache/)

Rtcache is not tied to a specific Frameworks.
 
 - 05.09.2013 v.0.1
 
George Batanov
gsb@gsbmail.ru
