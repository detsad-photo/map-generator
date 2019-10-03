<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 13.03.2019
 * Time: 21:10
 */

namespace Maps;

class Users extends Config
{

    /**
     * Users constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function get(string $email): array
    {
        $email = $this->mysqli->escape_string($email);

        $user = $this->mysqli->query("SELECT `id`, `name`, `permissions` FROM `sales` WHERE `email` = '$email' LIMIT 1");

        if (mysqli_num_rows($user) > 0) {
            return mysqli_fetch_assoc($user);
        } else{
            return [];
        }
    }

    public function login(string $email, string $password): bool
    {
        $email = $this->mysqli->escape_string($email);
        $password = $this->mysqli->escape_string($password);

        $user = $this->mysqli->query("SELECT COUNT(*) as `count` FROM `sales` WHERE `password` = '$password' AND `email` = '$email' LIMIT 1");
        $user = mysqli_fetch_assoc($user);

        if ($user['count'] > 0) {

            $cookie_valid = time() + 604800;

            if (setcookie('email', $email, $cookie_valid, '/') AND setcookie('password', $password, $cookie_valid, '/')) {
                return true;
            } else{
                return false;
            }
        } else {
            return false;
        }
    }

    public function verify(): bool
    {
        if (!empty($_COOKIE['password']) AND !empty($_COOKIE['email'])) {

            $password = $this->mysqli->escape_string($_COOKIE['password']);
            $email = $this->mysqli->escape_string($_COOKIE['email']);

            $user = $this->mysqli->query("SELECT COUNT(*) as `count` FROM `sales` WHERE `password` = '$password' AND `email` = '$email' LIMIT 1");
            $user = mysqli_fetch_assoc($user);

            if ($user['count'] > 0) {
                return true;
            } else{
                return false;
            }
        } else{
            return false;
        }
    }

    public function logout(): bool
    {
        if (setcookie('password', '', 0, '/') AND setcookie('email', '', 0, '/')) {
            return true;
        } else{
            return false;
        }
    }
}