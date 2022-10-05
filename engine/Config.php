<?php

namespace Maps;

use mysqli;

/**
 * Класс всех настроек
 */
class Config {

    /**
     * Базовый домен приложения
     * @var string
     */
    public $base_domain = 'http://maps.detsad.photo/';

    /**
     * Изначально null, но в конструкторе создаётся экземпляр класса /mysqli
     * @var mysqli
     */
    public $mysqli = null;

    public $sale_map_url = 'https://maps.detsad.photo/sales.php';

    /**
     * Пустой конструктор, в котором создаётся подключение mysqli
     */
    public function __construct() {
        $this->mysqli = new mysqli(
            $_ENV['MYSQL_HOST'],
            $_ENV['MYSQL_LOGIN'],
            $_ENV['MYSQL_PASSWORD'],
            $_ENV['MYSQL_DATABASE']
        );

        if (mysqli_connect_errno()){
            printf("Не удалось подключиться: %s\n", mysqli_connect_error());
            die();
        }
        if (!$this->mysqli->set_charset("utf8")) {
            printf("Ошибка при загрузке набора символов utf8: %s\n", $this->mysqli->error);
            die();
        }
    }
}