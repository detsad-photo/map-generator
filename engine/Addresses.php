<?php

namespace Maps;

use Maps\Exceptions\DatabaseException;
use Maps\Types\Address;
use Yandex\Geo\Api;
use Yandex\Geo\Exception;
use Yandex\Geo\Exception\CurlError;
use Yandex\Geo\Exception\MapsError;
use Yandex\Geo\Exception\ServerError;

/**
 * Created by PhpStorm.
 * Project: yandex-maps
 * User: Anton Neverov <neverov12@gmail.com>
 * Date: 03.10.2019
 * Time: 2:15
 */

class Addresses extends Config
{
    private $maps;

    public function __construct()
    {
        parent::__construct();
        $this->maps = new Api();
    }

    /**
     * @param string $address
     * @return Address
     * @throws DatabaseException
     * @throws MapsError
     */
    public function getData(string $address): Address
    {
        $address = $this->normalizeAddress($address);

        try {
            return $this->getDataByHash(md5($address));
        } catch (DatabaseException $e) {
            unset($e);
        }

        $this->logRequest('get', $address);
        try {
            $location = $this->maps->setQuery($address)->setLimit(1)->load()->getResponse()->getFirst();
        } catch (MapsError $e) {
            throw new MapsError('Ошибка ответа от API Яндекс.Карт: ' . $e->getMessage(), 4);
        } catch (ServerError $e) {
            throw new MapsError('Ошибка сервера API Яндекс.Карт: ' . $e->getMessage(), 3);
        } catch (CurlError $e) {
            throw new MapsError('Ошибка сURL запроса в API Яндекс.Карт: ' . $e->getMessage(), 2);
        } catch (Exception $e) {
            throw new MapsError('Неизвестная ошибка API Яндекс.Карт: ' . $e->getMessage(), 1);
        }

        $street = (!empty($location->getThoroughfareName())) ? $location->getThoroughfareName() : $location->getDependentLocalityName();

        return $this->addAddress($address, [
            'address' => $location->getAddress(),
            'lat' => $location->getLatitude(),
            'lng' => $location->getLongitude(),
            'area' => $location->getAdministrativeAreaName(),
            'city' => $location->getLocalityName(),
            'street' => $street,
            'house' => $location->getPremiseNumber()
        ]);
    }

    /**
     * @param string $type
     * @param string $address
     */
    protected function logRequest(string $type, string $address) {
        $type = $this->mysqli->escape_string('a-' . $type);
        $address = $this->mysqli->escape_string($address);

        $this->mysqli->query("INSERT INTO `geocoding` (`type`, `address`) VALUES ('$type', '$address')");
    }

    /**
     * @param string $hash
     * @return Address
     * @throws DatabaseException
     */
    protected function getDataByHash(string $hash): Address
    {
        $hash = $this->mysqli->escape_string($hash);
        $result = $this->mysqli->query("SELECT * FROM `addresses` WHERE `hash` = '$hash' LIMIT 1");

        if (mysqli_num_rows($result) > 0) {
            return $this->convert(mysqli_fetch_assoc($result));
        } else{
            throw new DatabaseException("Хеша $hash в базе данных не найдено");
        }
    }

    /**
     * @param string $originalAddress
     * @param array $result
     * @return Address
     * @throws DatabaseException
     */
    protected function addAddress(string $originalAddress, array $result): Address
    {
        $addressHash = $this->mysqli->escape_string($originalAddress);

        foreach ($result as $key => $value) {
            $result[$key] = $this->mysqli->escape_string($value);
        }

        $this->mysqli->query("INSERT INTO `addresses`(`address`, `hash`, `lat`, `lng`, `area`, `city`, `street`, `house`) VALUES ('{$result['address']}', '{$addressHash}', '{$result['lat']}', '{$result['lng']}', '{$result['area']}', '{$result['city']}', '{$result['street']}', '{$result['house']}')");

        return $this->getDataByHash($addressHash);
    }

    /**
     * @param string $address
     * @return string
     */
    public function normalizeAddress(string $address): string
    {
        $address = str_replace('(', '', $address);
        $address = str_replace(')', '', $address);

        return $address;
    }

    /**
     * @param array $data
     * @return Address
     */
    protected function convert(array $data): Address
    {
        $address = new Address();

        $address->setId($data['id']);
        $address->setAddress($data['address']);
        $address->setHash($data['hash']);
        $address->setLat($data['lat']);
        $address->setLng($data['lng']);
        $address->setArea($data['area']);
        $address->setCity($data['city']);
        $address->setStreet($data['street']);
        $address->setHouse($data['house']);

        return $address;
    }

}