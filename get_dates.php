<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 11.03.2019
 * Time: 3:29
 */

require_once __DIR__."/engine/autoload.php";

$saleId = (!empty($_GET['sale'])) ? $_GET['sale'] : 0;
$statusName = (!empty($_GET['status'])) ? $_GET['status'] : '';

$dates = $maps->getAvalibleDates($saleId, $statusName);

header('Content-Type: application/json');
echo json_encode($dates);
die();