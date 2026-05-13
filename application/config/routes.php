<?php
defined('BASEPATH') OR exit('No direct script access allowed');

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
|	https://codeigniter.com/userguide3/general/routing.html
|
| -------------------------------------------------------------------------
| RESERVED ROUTES
| -------------------------------------------------------------------------
|
| There are three reserved routes:
|
|	$route['default_controller'] = 'welcome';
|
| This route indicates which controller class should be loaded if the
| URI contains no data. In the above example, the "welcome" class
| would be loaded.
|
|	$route['404_override'] = 'errors/page_missing';
|
| This route will tell the Router which controller/method to use if those
| provided in the URL cannot be matched to a valid route.
|
|	$route['translate_uri_dashes'] = FALSE;
|
| This is not exactly a route, but allows you to automatically route
| controller and method names that contain dashes. '-' isn't a valid
| class or method name character, so it requires translation.
| When you set this option to TRUE, it will replace ALL dashes in the
| controller and method URI segments.
|
| Examples:	my-controller/index	-> my_controller/index
|		my-controller/my-method	-> my_controller/my_method
*/
$route['default_controller'] = 'Dashboard';
$route['404_override'] = '';
$route['translate_uri_dashes'] = FALSE;
// Auth
$route['login']             = 'Auth/login';
$route['login/post']        = 'Auth/do_login';
$route['register']          = 'Auth/register';
$route['register/post']     = 'Auth/do_register';
$route['logout']            = 'Auth/logout';

$route['auth/google_login']    = 'Auth/google_login';
$route['auth/google_callback'] = 'Auth/google_callback';


// Dashboard
$route['dashboard']         = 'Dashboard/index';

// Collections
$route['collections']                   = 'Collections/index';
$route['collections/create']            = 'Collections/create';
$route['collections/store']             = 'Collections/store';
$route['collections/delete/(:num)']     = 'Collections/delete/$1';

// Requests
$route['requests/new']                  = 'Requests/create';
$route['requests/save']                 = 'Requests/save';
$route['requests/load/(:num)']          = 'Requests/load/$1';
$route['requests/delete/(:num)']        = 'Requests/delete/$1';
$route['requests/duplicate/(:num)']     = 'Requests/duplicate/$1';

// API Sender (the main send engine)
$route['api/send']                      = 'ApiSender/send';
$route['api/send_cors']                 = 'ApiSender/send_cors';

// Environments
$route['environments']                  = 'Environments/index';
$route['environments/save']             = 'Environments/save';
$route['environments/set_active/(:num)']= 'Environments/set_active/$1';
$route['environments/delete/(:num)']    = 'Environments/delete/$1';
$route['environments/get/(:num)']       = 'Environments/get/$1';

// History
$route['history']                       = 'History/index';
$route['history/clear']                 = 'History/clear';
$route['history/delete/(:num)']         = 'History/delete/$1';
$route['history/load/(:num)']           = 'History/load/$1';

// Tabs
$route['tabs/save']                     = 'Tabs/save';
$route['tabs/load']                     = 'Tabs/load';
$route['tabs/close/(:num)']             = 'Tabs/close/$1';

// Profile
$route['profile']                       = 'Profile/index';
$route['profile/update']                = 'Profile/update';

// Workspaces
$route['workspaces']                    = 'Workspaces/index';
$route['workspaces/store']              = 'Workspaces/store';
$route['workspaces/delete/(:num)']      = 'Workspaces/delete/$1';
$route['workspaces/set_active/(:num)']  = 'Workspaces/set_active/$1';
