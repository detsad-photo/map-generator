<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 11.03.2019
 * Time: 3:29
 */

use Maps\Maps;

require_once __DIR__."/composer/vendor/autoload.php";

$saleId = (!empty($_GET['sale'])) ? $_GET['sale'] : 0;
$statusName = (!empty($_GET['status'])) ? $_GET['status'] : '';
$date = (!empty($_GET['date'])) ? $_GET['date'] : '';

$maps = new Maps();
$map = $maps->getMap($saleId, $statusName, $date);

header('Content-Type: application/json');
echo json_encode($map);
die();