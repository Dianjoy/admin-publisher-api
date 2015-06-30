<?php
/**
 * Created by PhpStorm.
 * User: meathill
 * Date: 14/11/12
 * Time: 下午5:24
 */
use NoahBuscher\Macaw\Macaw;

Macaw::get(BASE . '', 'diy\controller\HomeController@home');

Macaw::get(BASE . 'dashboard/', 'diy\controller\HomeController@dashboard');

Macaw::options(BASE . 'file/', 'diy\controller\BaseController@on_options');

Macaw::post(BASE . 'file/', 'diy\controller\FileController@upload');

Macaw::options(BASE . 'fetch/', 'diy\controller\BaseController@on_options');
Macaw::post(BASE . 'fetch/', 'diy\controller\FileController@fetch');


Macaw::get(BASE . 'stat/', 'publisher\controller\PublisherController@stat');

Macaw::get(BASE . 'pubs/', 'publisher\controller\PublisherController@get_list');

Macaw::options(BASE . 'info/', 'diy\controller\BaseController@on_options');

Macaw::get(BASE . 'info/', 'publisher\controller\PublisherController@get_info');

Macaw::post(BASE . 'info/', 'publisher\controller\PublisherController@update');

Macaw::options(BASE . 'apply/', 'diy\controller\BaseController@on_options');

Macaw::post(BASE . 'apply/', 'publisher\controller\PublisherController@apply');

Macaw::get(BASE . 'apply/', 'publisher\controller\PublisherController@get_apply');

Macaw::post(BASE . 'password/', 'publisher\controller\PublisherController@update_password');

Macaw::post(BASE . 'create/', 'publisher\controller\PublisherController@create');


Macaw::get(BASE . 'user/', 'publisher\controller\UserController@get_info');

Macaw::post(BASE . 'user/', 'publisher\controller\UserController@login');

Macaw::options(BASE . 'user/', 'diy\controller\BaseController@on_options');

Macaw::delete(BASE . 'user/', 'publisher\controller\UserController@logout');

Macaw::error(function() {
  echo '404 :: Not Found';
});