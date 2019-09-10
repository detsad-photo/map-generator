<?php
/**
 * Created by PhpStorm.
 * Project: paypal-online-kassa
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 18.07.2018
 * Time: 15:18
 */

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

header('Content-Type: text/html; charset=utf-8', true);

require_once __DIR__."/../composer/vendor/autoload.php";

require_once __DIR__."/Config.php";
$config = new Config();

require_once __DIR__."/Maps.php";
$maps = new Maps();

require_once __DIR__."/Users.php";
$users = new Users();