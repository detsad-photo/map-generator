<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 18.03.2019
 * Time: 13:32
 */

setcookie('email', '', time() - 3600, '/');
setcookie('password', '', time() - 3600, '/');
header('Location: /login.php');