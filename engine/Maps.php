<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 21.08.2018
 * Time: 20:41
 */

namespace Maps;

use GuzzleHttp\Client;
use Maps\Exceptions\DatabaseException;
use Maps\Exceptions\MapsException;
use Yandex\Geo\Api;
use Yandex\Geo\Exception;
use Yandex\Geo\Exception\MapsError;

/**
 * Основной класс для работы с картами
 */
class Maps extends Config
{

    /**
     * Экземпляр класса Yandex Geo API для работы с пакетом yandex/geo
     * @var Api
     */
    private $maps_api;
    private $guzzle;
    private $addresses;

    /**
     * Пустой конструктор
     */
    public function __construct()
    {
        parent::__construct();
        $this->maps_api = new Api();
        $this->guzzle = new Client([
            'http_errors' => false,
            'proxy' => 'http://51.15.38.26:3128',
            'debug' => true,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:70.0) Gecko/20100101 Firefox/70.0'
            ]
        ]);
        $this->addresses = new Addresses();
    }

    /**
     * @param int $eventRowId
     * @return array
     * @throws DatabaseException
     * @throws MapsError
     * @throws DatabaseException
     */
    public function getEventLocation(int $eventRowId): array
    {
        $eventRowId = $this->mysqli->escape_string($eventRowId);

        $eventData = $this->mysqli->query("SELECT `name`, `comment`, `person`, `address`, `shooting` FROM `events` WHERE `row_id` = '$eventRowId' LIMIT 1");

        if ($eventData->num_rows > 0) {
            $eventData = $eventData->fetch_assoc();
            $address = $eventData['address'];

            $addressData = $this->addresses->getData($address);
            $eventData['data'] = $addressData;

            $letter = substr($eventData['shooting'], -1, 1);
            $company = $this->mysqli->query("SELECT `name` FROM `numbers` WHERE letter = '$letter' LIMIT 1");
            $company = $company->fetch_assoc();

            $eventData['company'] = $company['name'];

            return $eventData;
        } else{
            throw new DatabaseException('Данная съёмка не найдена в базе данных', 0);
        }
    }

    /**
     * Добавить карту с массивом точек на ней
     * $array[] Это масив масивов точек
     *  $array[]['address'] Адрес
     *  $array[]['persons'] Контактные лица
     *  $array[]['description'] Любое описание
     * Если в массиве $array только 1 объект, то он добавляется со всеми данными в каждую колонку + JSON точки в locations. Если же точек больше одной, тогда весь $array конвертируется в JSON и всё это кладётся в колонку locations. Это сделано для того, чтобы избежать повторений в добавлении новых точек через API и искать добавленные точки по адресу, названию и описанию.
     * @param array $array Массив точек параметров (см. выше)
     * @return bool|int FALSE при ошибке добавления, иначе - ID новой карты
     */
    public function Add(array $array)
    {
        if (count($array) == 1){ //Если в массиве только 1 элемент, то нужно получить его данные
            $address = mysqli_escape_string($this->mysqli, $array[0]['address']);
            $name = mysqli_escape_string($this->mysqli, $array[0]['persons']);
            $description = mysqli_escape_string($this->mysqli, $array[0]['description']);
        }
        $array = mysqli_escape_string($this->mysqli, json_encode($array)); //В любом случае, добавляем в JSON весь массив

        if (!empty($address) AND !empty($name) AND !empty($description)) { //Если в массиве был только 1 элемент, то добавляем так же и дополнительные данные
            $result = $this->mysqli->query("INSERT INTO maps (locations, address, name, description) VALUES ('$array', '$address', '$name', '$description')");
        } else{ //Иначе просто кладём всё, что есть в JSON в locations
            $result = $this->mysqli->query("INSERT INTO maps (locations) VALUES ('$array')");
        }

        if ($result) { //Если всё успешно
            return $this->mysqli->insert_id; //Возвращаем ID новой карты
        } else{ //Иначе
            return false; //Возвращаем false
        }

    }

    /**
     * Получение массива карты по её ID
     * @param int $id ID карты по базе
     * @return bool|array FALSE, если произошла ошибка или карты не сщуетсвует, если всё в порядке - врнётся массив точек, аналогичный тому, что передаётся в $this->Add().
     */
    public function Get(int $id)
    {
        $id = mysqli_real_escape_string($this->mysqli, $id);

        $result = $this->mysqli->query("SELECT locations FROM maps WHERE id = '$id' LIMIT 1"); //Пытаемся получить данные карты по ID

        if (mysqli_num_rows($result) > 0) { //Если получилось
            $result = mysqli_fetch_assoc($result);
            return json_decode($result['locations'], true); //Возвращаем массив, созданный из JSON полученной карты
        } else{ //Иначе
            return false; //Возвращаем false
        }
    }

    /**
     * Получение всех ТОЧЕК из базы данных в одном массиве
     * @return array|bool FALSE если ошибка, иначе - массив ВСЕХ точек
     */
    public function GetAll()
    {
        $result = $this->mysqli->query("SELECT locations FROM maps WHERE address IS NOT NULL GROUP BY address"); //Получаем все карты из массива

        if (mysqli_num_rows($result) > 0) { //Если получили хотя-бы одну карту

            $map = []; //Создаём массив

            while ($row = mysqli_fetch_assoc($result)) { //Объеденяем каждую строку с массивом $map
                $row = json_decode($row['locations'], true);
                $map = array_merge($map, $row);
            }

            return $map; //Возвращаем массив

        } else{ //Иначе
            return false; //Возвращаем false
        }
    }

    /**
     * Создаёт значения lat и long для указанной точки. Если в базе есть уже такая карта с теми же address, persons и description - вернёт её ID
     * @param array $point Массив, содержащий данные address, persons, description
     * @return array|int Готовый массив, который можно добавить через $this->Add() или ID уже сщуетсвующей карты
     * @throws Exception
     */
    public function GeneratePoint(array $point)
    {
        $originalAddress = $point['address'];
        $point['address'] = $this->addresses->normalizeAddress($point['address']);

        //Получаем данные из точки
        $address = mysqli_escape_string($this->mysqli, $point['address']);
        $persons = mysqli_escape_string($this->mysqli, $point['persons']);
        $description = mysqli_escape_string($this->mysqli, $point['description']);

        $repeat = $this->mysqli->query("SELECT id FROM maps WHERE address = '$address' AND name = '$persons' AND description = '$description' LIMIT 1"); //Проверяем, есть ли уже аналог этой точки

        if (mysqli_num_rows($repeat) > 0) { //Если есть
            $repeat = mysqli_fetch_assoc($repeat);
            return $repeat['id']; //Возвращаем его ID
        } else{ //Иначе

            try {
                $addressData = $this->addresses->getData($originalAddress);
            } catch (DatabaseException $e) {
                throw new Exception($e->getMessage(), $e->getCode());
            }

            //Добавляем коориднаты к точке
            $point['lat'] = $addressData->getLat();
            $point['long'] = $addressData->getLng();

            return $point; //Возвращаем точку с координатами
        }
    }

    /**
     * @param string $address
     * @return array
     * @throws Exception
     */
    public function getData(string $address): array
    {
        try {
            $location = $this->addresses->getData($address);
        } catch (MapsError $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        } catch (DatabaseException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }

        $result = [
            'address' => $location->getAddress(),
            'lat' => $location->getLat(),
            'lng' => $location->getLng(),
            'province' => $location->getProvince(),
            'area' => $location->getArea(),
            'city' => $location->getCity(),
            'street' => $location->getStreet(),
            'house' => $location->getHouse(),
            'zip' => $location->getZip()
        ];

        $axiomusAddress = '';
        $axiomusCityIndex = 0;
        $axiomusFromMkad = 0;

        if (mb_strtolower($result['city']) != 'москва' AND mb_strtolower($result['city']) != 'санкт-петербург') {
            $axiomusAddress = '';

            if (stripos($axiomusAddress, 'поселок ') !== false OR stripos($axiomusAddress, 'село ') !== false) $axiomusAddress .= 'г. ';
            $axiomusAddress .= "{$result['city']}, ";
            $axiomusFromMkad = 1;
        }
        if (mb_strtolower($result['city']) == 'санкт-петербург' OR mb_strtolower($result['area']) == 'санкт-петербург') {
            $axiomusCityIndex = 1;
        }

        $axiomusAddress .= "{$result['street']}, {$result['house']}";
        $axiomusAddress = trim($axiomusAddress);

        return [
            'address' => $result['address'],
            'coords' => [
                floatval($result['lat']),
                floatval($result['lng'])
            ],
            'province' => $result['province'],
            'area' => $result['area'],
            'city' => $result['city'],
            'street' => $result['street'],
            'house' => $result['house'],
            'zip' => $result['zip'],
            'axiomus' => [
                'address' => $axiomusAddress,
                'city' => $axiomusCityIndex,
                'mkad' => $axiomusFromMkad
            ]
        ];
    }

    /**
     * Получает массив координат [lat, long] для заданного адреса (используется в API)
     * @param string $address Адрес
     * @return array Массив координат
     * @throws \Exception
     */
    public function getCoords(string $address): array
    {
        $location = $this->addresses->getData($address);

        if (!empty($location)) {
            return [$location->getLat(), $location->getLng(), $address];
        } else {
            return [];
        }
    }

    /**
     * Добавляет любой текст к карте
     * @param int $id ID карты по базе
     * @param string $text Текст, который необходимо добавить
     * @return bool Успешное ли добавление
     */
    public function AddTextToMap(int $id, string $text): bool
    {
        $id = mysqli_escape_string($this->mysqli, $id);
        $text = mysqli_escape_string($this->mysqli, $text);

        if ($this->mysqli->query("UPDATE maps SET text = '$text' WHERE id = '$id' LIMIT 1")) { //Если успшно добавили
            return true; //Возвращаем TRUE
        } else{ //Иначе
            return false; //Возвращаем false
        }
    }

    public function updateSalesEvents(int $limit = 20): void
    {
        $result = $this->mysqli->query("SELECT `id`, `address` FROM `sales_events` WHERE `lat` IS NULL OR `lng` IS NULL LIMIT $limit");
        if ($result->num_rows > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $this->logRequest('updateSalesEvents', $row['address']);
                $id = $row['id'];

                try {
                    $coords = $this->getCoords($row['address']);

                    if (!empty($coords)) {
                        $lat = $this->mysqli->escape_string($coords[0]);
                        $lng = $this->mysqli->escape_string($coords[1]);

                        $this->mysqli->query("UPDATE `sales_events` SET `lat` = '$lat', `lng` = '$lng' WHERE `id` = '$id' LIMIT 1");
                    }
                } catch (Exception $e) {
                    if ($e->getCode() == 4) {
                        $this->mysqli->query("UPDATE `sales_events` SET `lat` = '55.5807481', `lng` = '36.8251304' WHERE `id` = '$id' LIMIT 1");
                    }
                }
            }
        }
    }

    public function getSales(int $userId = 0) {
        if ($userId != 0) {
            $result = $this->mysqli->query("SELECT s.id, s.name, e.status FROM sales s LEFT JOIN sales_events e ON s.id = e.sale WHERE s.id = '$userId' GROUP BY e.status ORDER BY s.name, s.id");
        } else{
            $result = $this->mysqli->query("SELECT s.id, s.name, e.status FROM sales s LEFT JOIN sales_events e ON s.id = e.sale GROUP BY e.status ORDER BY s.name, s.id");
        }

        $sales = [];
        $sales[0] = [
            'statuses' => []
        ];

        while ($row = mysqli_fetch_assoc($result)) {
            if (!empty($row['status'])) {
                if (empty($sales[$row['id']])) {
                    $sales[$row['id']] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'statuses' => []
                    ];
                }
                $sales[$row['id']]['statuses'][] = $row['status'];

                if (!in_array($row['status'], $sales[0]['statuses'])) $sales[0]['statuses'][] = $row['status'];
            }

        }

        return $sales;
    }

    public function getPoints(int $saleId = 0, string $status = '', string $date = ''): array
    {
        $where = [];
        if ($saleId > 0) {
            $saleId = $this->mysqli->escape_string($saleId);
            $where[] = "`sale` = '$saleId'";
        }
        if (!empty($status)) {
            $status = explode('|', $status);
            $statusWhere = [];
            foreach ($status as $s) {
                $s = $this->mysqli->escape_string($s);
                $statusWhere[] = "'$s'";
            }
            $where[] = '`status` IN ('.implode(', ', $statusWhere).')';
        }
        if (!empty($date)) {
            $date = $this->mysqli->escape_string($date);
            $where[] = "`date` = '$date'";
        }
        if (!empty($where)) {
            $where = ' WHERE '.implode(' AND ', $where);
        } else{
            $where = '';
        }

        $result = $this->mysqli->query("SELECT `id`, `name`, `person`, `comment`, `address`, `status`, `date`, `lat`, `lng` FROM `sales_events` $where");

        $return = [];

        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $return[] = $row;
            }
        }

        return $return;
    }

    public function getMap(int $saleId = 0, string $status = '', string $date = ''): array
    {
        $points = $this->getPoints($saleId, $status, $date);

        if (!empty($points)) {
            $map = [
                'type' => 'FeatureCollection',
                'features' => $this->normalizePoints($points)
            ];

            return $map;
        } else{
            return [];
        }
    }

    private function normalizePoints(array $points): array
    {
        foreach ($points as $index => $point) {
            $points[$index] = [
                'type' => 'Feature',
                'id' => $index,
                'geometry' => [
                    'type' => 'Point',
                    'coordinates' => [$point['lat'], $point['lng']]
                ],
                'point' => $point
            ];
        }
        return $points;
    }

    public function getAvalibleDates(int $saleId = 0, string $status = ''): array
    {
        $where = [];
        if ($saleId > 0) {
            $saleId = $this->mysqli->escape_string($saleId);
            $where[] = "`sale` = '$saleId'";
        }
        if (!empty($status)) {
            $status = explode('|', $status);
            $statusWhere = [];
            foreach ($status as $s) {
                $s = $this->mysqli->escape_string($s);
                $statusWhere[] = "'$s'";
            }
            $where[] = '`status` IN ('.implode(', ', $statusWhere).')';
        }
        if (!empty($where)) {
            $where = ' WHERE '.implode(' AND ', $where);
        } else{
            $where = '';
        }

        $result = $this->mysqli->query("SELECT `date` FROM `sales_events` $where GROUP BY `date`");

        $dates = [];
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dates[] = $row['date'];
            }
        }

        return $dates;
    }

}