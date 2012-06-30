<?php
return array(
	'_root_'  => 'chan/index',  // The default route
	'admin/(:any)' => 'admin/$1',
	'search/(:any)' => 'chan/search',
	'(?!(admin|api|content|assets|search))(\w+)' => 'chan/$2/page',
	'(?!(admin|api|content|assets|search))(\w+)/(:any)' => 'chan/$2/$3',
	'_404_'   => 'chan/404',    // The main 404 route
);