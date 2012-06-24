<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| URI ROUTING
| -------------------------------------------------------------------------
| This file lets you re-map URI requests to specific controller functions.
|
| Typically there is a one-to-one relationship between a URL string
| and its corresponding controller class/method. The segments in a
| URL normally follow this pattern:
|
|	example.com/class/method/id/
|
| In some instances, however, you may want to remap this relationship
| so that a different class/function is called than the one
| corresponding to the URL.
|
| Please see the user guide for complete details:
|
|	http://codeigniter.com/user_guide/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There area two reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router what URI segments to use if those provided
| in the URL cannot be matched to a valid route.
|
*/

// if we're using special subdomains or if we're under system stuff
if(
	!defined('FOOL_SUBDOMAINS_ENABLED')
	|| strpos($_SERVER['HTTP_HOST'], FOOL_SUBDOMAINS_SYSTEM) !== FALSE
)
{
	$route['install'] = "install";
	$route['api'] = "api";
	$route['api/chan'] = "api/chan_api";
	$route['api/chan/(.*?)'] = "api/chan_api/$1";
	$route['cli'] = "cli";
	$route['admin/members/members'] = 'admin/members/membersa';
	$route['admin/plugins'] = "admin/plugins_admin/manage";
	$route['admin/plugins/(.*?)'] = "admin/plugins_admin/$1";

	$route_admin_controllers = array_merge(
		glob(APPPATH . 'controllers/admin/*.php'), 
		glob(APPPATH . 'controllers/' . FOOL_PACKAGE . '/*.php')
	);

	foreach($route_admin_controllers as $key => $item)
	{
		$item = str_replace(APPPATH . 'controllers/admin/', '', $item);
		$item = str_replace(APPPATH . 'controllers/' . FOOL_PACKAGE . '/', '', $item);
		$route_admin_controllers[$key] = substr($item, 0, strlen($item) - 4);
	}
	$route_admin_controllers[] = 'plugins';

	// routes to allow plugin.php to catch the files, could be automated...
	$route['admin/(?!(' . implode('|', $route_admin_controllers) . '))(\w+)'] = "admin/plugin/$2/";
	$route['admin/(?!(' . implode('|', $route_admin_controllers) . '))(\w+)/(.*?)'] = "admin/plugin/$2/$3";
}

//$route['default_controller'] = "foolfuuka/chan";
//$route['404_override'] = 'foolfuuka/chan/show_404';


include 'application/packages/' . FOOL_PACKAGE . '/config/routes.php';

/* End of file routes.php */
/* Location: ./application/config/routes.php */