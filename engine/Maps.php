<?php
/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 21.08.2018
 * Time: 20:41
 */

/**
 * Основной класс для работы с картами
 */
class Maps extends Config
{

    /**
     * Экземпляр класса Yandex Geo API для работы с пакетом yandex/geo
     * @var \Yandex\Geo\Api
     */
    private $maps_api;

    /**
     * Пустой конструктор
     */
    public function __construct()
    {
        parent::__construct();
        $this->maps_api = new \Yandex\Geo\Api();
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

        if (!empty($address)) { //Если в массиве был только 1 элемент, то добавляем так же и дополнительные данные
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
     * @throws \Yandex\Geo\Exception
     */
    public function GeneratePoint(array $point)
    {

        if (stripos($point['address'], 'москва') === false AND stripos($point['address'], 'московская') === false) {
            $point['address'] = 'Москва, ' . $point['address'];
        } elseif (stripos($point['address'], 'московская') === false) {
            $point['address'] = 'Московская область, '.$point['address'];
        }

        $point['address'] = str_replace('(', '', $point['address']);
        $point['address'] = str_replace(')', '', $point['address']);

        //Получаем данные из точки
        $address = mysqli_escape_string($this->mysqli, $point['address']);
        $persons = mysqli_escape_string($this->mysqli, $point['persons']);
        $description = mysqli_escape_string($this->mysqli, $point['description']);

        $repeat = $this->mysqli->query("SELECT id FROM maps WHERE address = '$address' AND name = '$persons' AND description = '$description' LIMIT 1"); //Проверяем, есть ли уже аналог этой точки

        if (mysqli_num_rows($repeat) > 0) { //Если есть
            $repeat = mysqli_fetch_assoc($repeat);
            return $repeat['id']; //Возвращаем его ID
        } else{ //Иначе

            $location = $this->maps_api->setQuery($point['address'])->setLimit(1)->load()->getResponse()->getList()[0]; //Ищем по картам точку с указанным адресом

            //Добавляем коориднаты к точке
            $point['lat'] = $location->getLatitude();
            $point['long'] = $location->getLongitude();

            return $point; //Возвращаем точку с координатами
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

}