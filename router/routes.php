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

Macaw::error(function() {
  echo '404 :: Not Found';
});