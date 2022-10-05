#photo-maps-generator
### Генерация и отображение карт с помощью Yandex Maps, а так же API для создания карт
Это приложение написано на PHP 7.2
#### Для обновления приложения нужно быть знакомым с пакетами и технологиями:

- [yandex/geo](https://github.com/yandex-php/php-yandex-geo) - класс для работы с API Яндекс.Геокодирование, чтобы конвертировать адреса в точки на карте со своими координатами
- [Яндекс.Карты JS API](https://tech.yandex.ru/maps/doc/jsapi/2.1/quick-start/index-docpage/) - JavaScript библиотека для отображения карт

#### Техонлогии, которые так же нужно знать:
- MySQLi - база данных
- PHP 7.2 - язык разработки
- JavaScript - для отображения карт
---
### Важно знать
Здесь я решил не использовать шаблонизатор. В будущем рекомендуется рефакторинг с использование Smarty или подобного шаблонизатора, так как логики на front-end'е достаточно много.

---
### Обновление документации из кода
```sh
$ php phpdox.phar
```
---

### Развёртывание Docker Compose
Для тестирования и разработки. Запуск происходит на http://localhost:8001/
```shell
docker-compose -f docker-compose.yaml -f docker-compose.dev.yaml up -d --build
```

Для продакшена:
```shell
docker-compose -f docker-compose.yaml up -d --build
```

---

### Тестирование GitHub Actions

```shell
act -P ubuntu-latest=node:16-buster-slim
```

---

### Разработал
Антон Неверов <neverov12@gmail.com>

Сайт [patriotovsky.ru](http://patriotovsky.ru/)

Для студии "Непоседы"