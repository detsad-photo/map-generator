<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 05.10.2019
 * Time: 4:27
 */

use Maps\Exceptions\DatabaseException;
use Maps\Exceptions\MapsException;
use Maps\Maps;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/bootstrap.php');

$maps = new Maps();

try {
    $result = $maps->getEventLocation($_GET['row_id']);
} catch (DatabaseException $e) {
    $error = 'Ошибка базы данных: '.$e->getMessage();
} catch (\Yandex\Geo\Exception\MapsError $e) {
    $error = 'Ошибка Яндекс.Карт: '.$e->getMessage();
}

?><!DOCTYPE html>
<html>
<head>
    <title>Карта</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<?php if(!empty($result)): ?>
    <script>
        ymaps.ready(init);

        function init() {
            var myMap = new ymaps.Map("map", {
                center: [<?php echo $result['data']->getLat() ?>, <?php echo $result['data']->getLng() ?>],
                zoom: 10
            }, {
                searchControlProvider: 'yandex#search'
            });

            myMap.geoObjects
                .add(new ymaps.Placemark([<?php echo $result['data']->getLat() ?>, <?php echo $result['data']->getLng() ?>], {
                    balloonContentHeader: '<?php echo addslashes(str_replace("\n", '<br>', $result['name'])) ?>',
                    balloonContent: '<?php echo addslashes(str_replace("\n", '<br>', $result['comment']."\n\n".$result['person'])) ?>',
                    balloonContentFooter: '<?php echo addslashes(str_replace("\n", '<br>', $result['address'])) ?>',
                    iconCaption: '<?php echo addslashes(str_replace("\n", '<br>', $result['name'])) ?>'
                }))
        }

    </script>

    <div id="list">
        <div class="item" data-lat="<?php echo $result['data']->getLat() ?>" data-long="<?php echo $result['data']->getLng() ?>">
            <p><?php echo str_replace("\n", "<br>", $result['name']) ?></p>
            <p><?php echo str_replace("\n", "<br>", $result['comment']."\n\n".$result['person']) ?></p>
            <p><b>Фотостудия: <?php echo $result['company'] ?></b></p>
            <p><?php echo str_replace("\n", "<br>", $result['address']) ?></p>
        </div>
    </div>

    <div id="map"></div>
<?php else: ?>
    <h1><?php echo $error ?></h1>
<?php endif ?>
</body>
</html>