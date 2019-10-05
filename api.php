<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 11.09.2018
 * Time: 21:06
 */

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Maps\Config;
use Maps\Maps;

require_once __DIR__."/composer/vendor/autoload.php";

header('Content-Type: application/json');

$result = [
    'ok' => false
];

$request = file_get_contents('php://input');

$maps = new Maps();
$config = new Config();

if (!empty($request)) {

    $request = json_decode($request, true);

    if (!empty($request['description']) AND !empty($request['persons']) AND !empty($request['address'])) {
        $map = $maps->GeneratePoint($request);
        if(is_numeric($map)) {
            $result = [
                'ok' => true,
                'url' => $config->base_domain.'map.php?id='.$map
            ];
        } elseif (!empty($map)){
            $map = [$map];
            $result = $maps->Add($map);

            if ($result !== false) {
                $result = [
                    'ok' => true,
                    'url' => $config->base_domain.'map.php?id='.$result
                ];
            } else{
                $result['error'] = 'Can\'t save map to database';
            }
        } else{
            $result['error'] = 'Can\'t create a map';
        }
    } elseif(!empty($request['function']) AND !empty($request['address'])) {
        if($request['function'] == 'data') {
            try {
                $result['data'] = $maps->getData($request['address']);
                $result['ok'] = true;
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
                $result['error_code'] = $e->getCode();
            }
        } else{
            $result['error'] = 'Undefined function.';
        }
    } elseif(!empty($request['address'])) {
        try {
            $coords = $maps->getCoords($request['address']);
            $result['ok'] = true;
            $result['data'] = $coords;
        } catch (Exception $e) {
            $result['error'] = $e->getMessage();
            $result['error_code'] = $e->getCode();
        }
    } elseif(!empty($result['id']) AND !empty($result['text'])) {
        if ($maps->AddTextToMap($result['id'], $result['text'])){
            $result['ok'] = true;
        } else{
            $result['error'] = 'Error on adding text to map';
        }
    } else{
        $result['error'] = 'Not all params';
    }
} else{
    $result['error'] = 'Not JSON data in POST';
}

echo json_encode($result);