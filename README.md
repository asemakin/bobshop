## bobshop — приложение заказов автозапчастей

Небольшое PHP‑приложение для оформления заказов автозапчастей с учётом складских остатков, страницей подтверждения заказа и виджетом курсов валют ЦБ РФ.

### Стек
- PHP 8+ (mysqli, местами PDO)
- MySQL 8+
- jQuery 3.6 + Inputmask
- Composer (phpmailer подключён, но сейчас не используется)

### Структура проекта
```
/
├── .gitignore
├── README.md
├── bob_auto_parts.sql            # Черновик SQL (содержит дубликаты/ошибки — см. «Известные несоответствия»)
├── composer.json                 # Зависимости Composer (phpmailer)
├── composer.lock
├── currency_cache.json           # Кэш виджета курсов валют
├── db_connect.php                # Подключение к БД (mysqli)
├── edit.js                       # JS для inline-редактирования (сейчас фактически не подключён)
├── functions.php                 # Виджет курсов валют + генератор номера заказа
├── jquery-3.6.0.min.js
├── jquery.inputmask.min.js
├── order_delivery.php            # Таблица стоимости доставки + виджет валют + часы
├── orderconfirm.php              # Обработка POST заказа, сохранение в БД, редирект на подтверждение
├── orderform.css                 # Стили формы заказа и кнопок
├── orderform.php                 # Страница подтверждения заказа (вывод состава/итогов)
├── orderforms.php                # Основная форма заказа (каталог со склада)
├── orders.csv                    # Пример/экспорт заказов
├── time.php                      # Мини‑виджет часов (целая HTML‑страница)
├── warehouse.css                 # Стили страницы склада
└── warehouse.php                 # Управление складом (CRUD + inline‑редактирование)
```

### Основные страницы и потоки
- Заказ:
  - `orderforms.php` → пользователь выбирает товары со склада
  - POST → `orderconfirm.php` (валидация, расчёт скидки/налога, запись в БД: `order`, `orderItem`, уменьшение остатков `warehouse`)
  - redirect → `orderform.php?orderId=...` (подтверждение заказа, печать)
- Склад:
  - `warehouse.php` (вывод списка, добавление, удаление, inline‑редактирование через fetch POST)
- Доставка:
  - `order_delivery.php` (расчётные цены по расстоянию) + подключение виджета курсов валют и мини‑часов

### Схема БД (нормализованный вариант под текущий код)
Рекомендуемая схема, согласованная с тем, как код уже обращается к данным:
```sql
CREATE DATABASE IF NOT EXISTS bob_auto_parts;
USE bob_auto_parts;

-- Склад
CREATE TABLE IF NOT EXISTS warehouse (
  orderId INT AUTO_INCREMENT PRIMARY KEY,
  productName VARCHAR(100) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL
);

-- Заказ (шапка)
CREATE TABLE IF NOT EXISTS `order` (
  id INT AUTO_INCREMENT PRIMARY KEY,
  orderDate DATETIME NOT NULL,
  subTotal DECIMAL(10,2) NOT NULL,
  discount DECIMAL(10,2) NOT NULL,
  tax DECIMAL(10,2) NOT NULL,
  totalAmount DECIMAL(10,2) NOT NULL,
  deliveryAddress VARCHAR(255),
  deliveryDate DATE,
  deliveryTime TIME,
  customerPhone VARCHAR(20),
  customerEmail VARCHAR(100),
  referralSourceId VARCHAR(50),
  createdAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Позиции заказа
CREATE TABLE IF NOT EXISTS orderItem (
  itemId INT AUTO_INCREMENT PRIMARY KEY,
  orderNumber INT NOT NULL,
  productId INT NOT NULL,
  productName VARCHAR(255) NOT NULL,
  quantity INT NOT NULL,
  price DECIMAL(10,2) NOT NULL,
  CONSTRAINT fk_orderItem_order
    FOREIGN KEY (orderNumber) REFERENCES `order`(id)
      ON DELETE CASCADE,
  CONSTRAINT fk_orderItem_warehouse
    FOREIGN KEY (productId) REFERENCES warehouse(orderId)
      ON DELETE RESTRICT
);

-- Дополнительно (опционально)
CREATE TABLE IF NOT EXISTS orderReferralInfo (
  id INT PRIMARY KEY,
  sourceCode CHAR(1) NOT NULL,
  sourceName VARCHAR(50) NOT NULL,
  CONSTRAINT fk_ori_order FOREIGN KEY (id) REFERENCES `order`(id)
);
```

Стартовые данные для склада:
```sql
INSERT INTO warehouse (productName, quantity, price) VALUES
  ('Шины', 100, 100.00),
  ('Масло', 50, 10.00),
  ('Свечи зажигания', 200, 4.00);
```

### Установка и запуск локально
- Требования: PHP 8+, MySQL 8+, Composer
- Установка зависимостей:
  - `composer install`
- Подготовка БД:
  - создайте БД и таблицы по «Схема БД» (см. выше)
- Конфигурация подключения:
  - в файлах используется `localhost`/`root`/пустой пароль/БД `bob_auto_parts`
  - при необходимости отредактируйте `db_connect.php` и места прямых подключений
- Запуск:
  - `php -S 127.0.0.1:8000 -t .`
  - откройте `http://127.0.0.1:8000/orderforms.php`

### Виджет курсов валют
Функция `get_currency_rates_2(array $config = [])` в `functions.php` получает курсы валют с сайта ЦБ РФ, кэширует их в `currency_cache.json` и рендерит HTML‑виджет. По умолчанию возвращает полноценную HTML-страницу. Рекомендуется адаптировать возврат к «фрагменту» (только контейнер виджета), чтобы корректно встраивать на страницу перед `</body>`.

Параметры `config`:
- `cache_file` — путь к кэшу (по умолчанию `./currency_cache.json`)
- `cache_time` — срок кэша в секундах (по умолчанию 3600)
- `currencies` — набор валют к отображению

### Известные несоответствия и технический долг
- Схема БД в `bob_auto_parts.sql` содержит дубли/опечатки/опасные команды (`DROP/TRUNCATE/DELETE`), а также несовпадающие имена таблиц/полей:
  - Код использует `warehouse.orderId` (PK), а в черновике SQL — `id`
  - Код использует таблицу `orderItem` и поле `orderNumber`, а в черновике встречается `order_items`
  - Есть опечатка `REFERENCES1` и дублирующиеся `CREATE TABLE orders`
- Встраиваемые файлы `functions.php::get_currency_rates_2()` и `time.php` возвращают целую HTML‑страницу, но подключаются внутрь других страниц, что нарушает структуру DOM. Нужно превратить их в «фрагменты».
- Валюта отображается неоднородно (`$` и `₽`). Следует унифицировать валюту и форматирование.
- Подключение к БД дублируется в разных файлах. Лучше централизовать через `db_connect.php`.
- Безопасность: нет CSRF‑защиты, валидация местами минимальная.
- `composer.json` содержит `phpmailer/phpmailer`, но он не применяется — либо удалить, либо добавить отправку письма.

### Дорожная карта улучшений
- Согласовать/мигрировать БД под «Схему БД» выше (или поправить код под текущую prod‑схему)
- Превратить валютный виджет и часы в «фрагменты» для корректного инклуда
- Унифицировать валюту и формат вывода цен/итогов
- Централизовать подключение к БД, использовать подготовленные выражения везде
- Включить отправку письма подтверждения (phpmailer) в `orderconfirm.php`
- Добавить CSRF‑защиту, серверную валидацию и basic‑аудит безопасности
- Написать e2e‑сценарии (Codeception/Pest) для основных потоков
- Опционально: Docker‑окружение (PHP‑FPM + Nginx + MySQL), Makefile, .env

### Быстрые ссылки по коду
- Форма заказа: `orderforms.php`
- Обработка заказа: `orderconfirm.php`
- Подтверждение заказа: `orderform.php`
- Склад: `warehouse.php`
- Виджет валют: `functions.php` (`get_currency_rates_2`)
- Подключение к БД: `db_connect.php`

Если понадобится, могу:
- привести SQL к рабочему состоянию под текущий код (и создать миграции),
- поправить виджет и часы на «фрагменты»,
- унифицировать валюту и оформление,
- добавить отправку на email после оформления заказа.