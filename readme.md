Парсер CommerceML2
==============

Библиотека для парсинга CommerceML2 файлов.
В основу была взята библиотека [zenwalker/commerceml](https://github.com/zenwalker/php-commerceml),
за что разработчику [zenwolker](https://zenwalker.me) отдельное спасибо.
### Установка

1. Обновите ваш composer.json файл.

```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/ArtemsWay/commerceml"
    }
],
"require": {

    "artemsway/commerceml": "dev-master"
},
```

2. Выполните команду ``` composer update ```.

### Использование

```php
use ArtemsWay\CommerceML\CommerceML;

class ExampleController extends Controller {

    public function exampleAction() {
        $importFile = storage_path('import0_1.xml');
        $offersFile = storage_path('offers0_1.xml');

        $reader = new CommerceML($importFile, $offersFile);
        $data = $reader->getData();
    }

}
```

### Выходные данные ``` var_dump($data) ```

```php
array:6 [▼
  "importTime" => "2016-03-01T01:03:19"
  "onlyChanges" => false
  "categories" => array:218 [▶]
  "properties" => array:608 [▶]
  "priceTypes" => array:1 [▶]
  "products" => array:4535 [▶]
]
```