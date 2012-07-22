<?php
return array(
	'_root_'  => 'foolfuuka/chan/index',  // The default route
	'admin/boards' => 'foolfuuka/admin/boards/manage',
	'admin/posts' => 'foolfuuka/admin/posts/reports',
	'admin/(boards|posts)/(:any)' => 'foolfuuka/admin/$1/$2',
	'admin/(:any)' => 'admin/$1',
	'search/(:any)' => 'foolfuuka/chan/search',
	'(?!(admin|api|content|assets|search))(\w+)' => 'foolfuuka/chan/$2/page',
	'(?!(admin|api|content|assets|search))(\w+)/(:any)' => 'foolfuuka/chan/$2/$3',
	'_404_'   => 'foolfuuka/chan/404',    // The main 404 route
);