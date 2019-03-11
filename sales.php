<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 10.03.2019
 * Time: 21:49
 */

require_once __DIR__."/engine/autoload.php";

$sales = $maps->getSales();

$saleId = (!empty($_GET['sale'])) ? $_GET['sale'] : 0;
$statusName = (!empty($_GET['status'])) ? $_GET['status'] : '';
$statusesSelected = explode('|', $statusName);
$date = (!empty($_GET['date'])) ? $_GET['date'] : '';

?><!DOCTYPE html>
<html lang="ru">
<head>
    <!-- HTML and CSS by Anton Neverov | neverov12@gmail.com  -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Карта</title>
    <link rel="stylesheet" href="/assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap-datepicker.min.css">
    <link rel="stylesheet" href="/assets/css/bootstrap-select.min.css">

    <style>
        html, body, #map {
            width: 100%; height: 100%; padding: 0; margin: 0;
        }
        #map{
            height: calc(100% - 54px);
        }
        .right{
            height: 100%;
            width: 80%;
            float: right;
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
        #list .item p:nth-child(4){
            margin-top: 0px;
            color: #999;
            font-weight: 500;
            font-size: 12px;
        }
    </style>
</head>
<body>

<div id="list">
<?php if(!empty($map)): ?>
    <div id="list">
        <?php foreach ($map as $loc): ?>
            <div class="item" data-lat="<?php echo $loc['lat'] ?>" data-long="<?php echo $loc['lng'] ?>">
                <p><?php echo (!empty($loc['name'])) ? $loc['name'] : '' ?></p>
                <p><?php echo (!empty($loc['person'])) ? $loc['person'] : '' ?></p>
                <p><?php echo (!empty($loc['address'])) ? $loc['address'] : '' ?></p>
                <p><?php echo (!empty($loc['status'])) ? $loc['status'] : '' ?>, <?php echo (!empty($loc['date'])) ? $loc['date'] : '' ?></p>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
</div>

<div class="right">
    <header class="pb-2 pt-2 bg-white">
        <form class="form-inline justify-content-center" method="get">
            <input type="hidden" name="status" value="<?php echo $statusName ?>">
            <select class="form-control mr-sm-2" name="sale">
                <option value="" data-statuses="<?php echo implode('|', $sales[0]['statuses']) ?>">Все</option>
                <?php foreach($sales as $index => $sale): ?>
                    <?php if ($index > 0): ?>
                        <option value="<?php echo $sale['id'] ?>" data-statuses="<?php echo implode('|', $sale['statuses']) ?>" <?php if($saleId == $sale['id']): ?>selected<?php endif ?>><?php echo $sale['name'] ?></option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <div class="mr-sm-2" style="width: auto">
                <select class="selectpicker" name="statuses" multiple data-selected-text-format="count" data-actions-box="true">
                    <?php foreach($sales[0]['statuses'] as $status): ?>
                        <option value="<?php echo $status ?>" <?php if(in_array($status, $statusesSelected)): ?>selected<?php endif ?>><?php echo $status ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="text" class="form-control mr-sm-2" name="date" autocomplete="off" value="<?php echo $date ?>" readonly>

            <button type="submit" class="btn btn-primary">Показать</button>
        </form>
    </header>
    <div id="map"></div>
</div>

<script src="/assets/js/jquery.min.js"></script>
<script src="/assets/js/popper.min.js"></script>
<script src="/assets/js/bootstrap.min.js"></script>
<script src="/assets/js/bootstrap-select.min.js"></script>
<script src="/assets/js/i18n/defaults-ru_RU.min.js"></script>
<script src="/assets/js/bootstrap-datepicker.min.js"></script>
<script src="/assets/locales/bootstrap-datepicker.ru.min.js"></script>
<script src="//api-maps.yandex.ru/2.1/?lang=ru_RU" type="text/javascript"></script>
<script src="/assets/js/sales.js"></script>

</body>
</html>
