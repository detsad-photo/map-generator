<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 10.03.2019
 * Time: 20:17
 */

use Maps\Maps;

require_once __DIR__."/../composer/vendor/autoload.php";

$maps = new Maps();
$maps->updateSalesEvents();

die();