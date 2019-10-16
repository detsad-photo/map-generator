<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 16.10.2019
 * Time: 21:31
 */


use Maps\Addresses;

require_once __DIR__."/composer/vendor/autoload.php";
$addresses = new Addresses();

//$addresses->getData('Россия, Республика Крым, Евпатория, улица Пушкина, 79А');