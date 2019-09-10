<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 13.03.2019
 * Time: 21:54
 */

require_once __DIR__ . "/engine/autoload.php";

if (!empty($_POST['email']) AND !empty($_POST['password'])) {
    if ($users->login($_POST['email'], $_POST['password'])) {
        echo 'asd';
        header("Location: ".$config->sale_map_url);
    } else{
        $result = 'Логин или пароль указаны неверно';
    }
}

?>
<?php if (!empty($result)): ?>
    <?php echo $result ?>
<?php endif; ?>
<form action="" method="POST">
    <input type="text" name="email" placeholder="Email" autocomplete="email">
    <input type="password" name="password" placeholder="Пароль">
    <button type="submit">Войти</button>
</form>