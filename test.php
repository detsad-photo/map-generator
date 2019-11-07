<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 16.10.2019
 * Time: 21:31
 */


use Maps\Addresses;

header('Content-Type: application/json');

require_once __DIR__."/composer/vendor/autoload.php";
$maps = new \Maps\Maps();

$result = $maps->getData('Россия, Республика Крым, Евпатория, улица Пушкина, 79А');

print_r(json_encode($result));