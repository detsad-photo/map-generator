<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 21.08.2018
 * Time: 21:24
 */

require_once __DIR__."/engine/autoload.php";

if (!empty($_GET['id'])) {

    $map = $maps->Get($_GET['id']);

    if ($map === false) {
        die();
    }
} else{
    die();
}

?><!DOCTYPE html>
<html>
<head>
    <title>Карта</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <script src="https://api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
    <style>
        html{
            font-family: 'Arial';
        }
        html, body, #map {
            width: 100%; height: 100%; padding: 0; margin: 0;
        }
        p{
            margin: 0px;
        }
        #list{
            overflow-y: scroll;
            max-height: 100%;
            box-sizing: border-box;
            width: 20%;
            float: left;
        }
        #list .item{
            padding: 15px 10px;
            cursor: pointer;
        }
        #list .item:hover{
            background: #EEEEEE;
        }
        #list .item.active{
            background: #FFEEEE;
        }
        #list .item p:nth-child(1){
            font-weight: 600;
            font-size: 14px;
        }
        #list .item p:nth-child(2){
            margin-top: 5px;
            font-size: 14px;
        }
        #list .item p:nth-child(3){
            margin-top: 5px;
            color: #999;
            font-weight: 500;
            font-size: 12px;
        }
        #map{
            width: 80%;
            float: left;
        }
    </style>
</head>
<body>

<script>
    ymaps.ready(init);

    function init() {
        var myMap = new ymaps.Map("map", {
                <?php
                    $lat_avg = 0;
                    $long_avg = 0;
                    foreach ($map as $loc){
                        $lat_avg += $loc['lat'];
                        $long_avg += $loc['long'];
                    }
                    $lat_avg /= count($map);
                    $long_avg /= count($map);
                ?>
                center: [<?php echo $lat_avg ?>, <?php echo $long_avg ?>],
                zoom: 10
            }, {
                searchControlProvider: 'yandex#search'
            });

        myMap.geoObjects
            <?php foreach ($map as $loc): ?>
            .add(new ymaps.Placemark([<?php echo $loc['lat'] ?>, <?php echo $loc['long'] ?>], {
                balloonContentHeader: '<?php echo addslashes(str_replace("\n", ' ', $loc['description'])) ?>',
                balloonContent: '<?php echo addslashes(str_replace("\n", ' ', $loc['persons'])) ?>',
                balloonContentFooter: '<?php echo addslashes(str_replace("\n", ' ', $loc['address'])) ?>',
                iconCaption: '<?php echo addslashes(str_replace("\n", ' ', $loc['description'])) ?>'
            }))
            <?php endforeach; ?>;

        $(".item").click(function (e) {
            $(".item").removeClass('active');
            $(this).addClass('active');

            let lat = $(this).data('lat');
            let long = $(this).data('long');

            myMap.panTo([lat, long]);
        });
    }

</script>

<div id="list">
    <?php foreach ($map as $loc): ?>
        <div class="item" data-lat="<?php echo $loc['lat'] ?>" data-long="<?php echo $loc['long'] ?>">
            <p><?php echo $loc['description'] ?></p>
            <p><?php echo $loc['persons'] ?></p>
            <p><?php echo $loc['address'] ?></p>
        </div>
    <?php endforeach; ?>
</div>

<div id="map"></div>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

</body>
</html>