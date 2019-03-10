<?php
/**
 * Created by PhpStorm.
 * Project: paypal-online-kassa
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 18.07.2018
 * Time: 15:18
 */

/**
 * Класс всех настроек
 */
class Config {

    /**
     * Базовый домен приложения
     * @var string
     */
    public $base_domain = 'http://maps.pscripem.beget.tech/';

    /**
     * Хост MySQLi
     * @var string
     */
    private $mysqli_host = "localhost";
    /**
     * Логин MySQL
     * @var string
     */
    private $mysqli_login = "pscripem_s";
    /**
     * Пароль MySQL
     * @var string
     */
    private $mysqli_password = "AN&4hX8C";
    /**
     * База данных MySQL
     * @var string
     */
    private $mysqli_database = "pscripem_s";

    /**
     * Изначально null, но в конструкторе создаётся экземпляр класса /mysqli
     * @var mysqli|null
     */
    public $mysqli = null;

    /**
     * Пустой конструктор, в котором создаётся подключение mysqli
     */
    public function __construct(){
        $this->mysqli = new mysqli($this->mysqli_host, $this->mysqli_login, $this->mysqli_password, $this->mysqli_database);

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