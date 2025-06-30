<?php
/**
 * ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
 * Создаем соединение с MySQL базой данных
 */
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

/**
 * ПРОВЕРКА ОТПРАВКИ ФОРМЫ ЗАКАЗА
 * Если форма отправлена методом POST и это не форма добавления товара
 */
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['add_product'])) {
    /**
     * ПОЛУЧЕНИЕ ТЕКУЩИХ ОСТАТКОВ НА СКЛАДЕ
     * Запрашиваем из базы ID товаров, их названия и количество
     */
    $products = $db->query("SELECT orderId, productName, quantity FROM warehouse");
    $stock = array(); // Создаем массив для хранения данных о товарах

    // Заполняем массив данными из базы
    while($row = $products->fetch_assoc()) {
        $stock[$row['orderId']] = array(
            'quantity' => $row['quantity'], // Доступное количество
            'name' => $row['productName']   // Название товара
        );
    }

    /**
     * ПРОВЕРКА КОЛИЧЕСТВА ТОВАРОВ В ЗАКАЗЕ
     * Проходим по всем полям формы
     */
    foreach ($_POST as $key => $value) {
        // Ищем поля с названиями product_XXX (товары в заказе)
        if (strpos($key, 'product_') === 0 && $value > 0) {
            $productId = substr($key, 8); // Получаем ID товара из названия поля
            $orderedQuantity = (int)$value; // Количество, которое хотят заказать

            // Проверяем, есть ли такой товар на складе
            if (isset($stock[$productId])) {
                // Сравниваем заказанное количество с доступным
                if ($orderedQuantity > $stock[$productId]['quantity']) {
                    // Если товара недостаточно - выводим ошибку
                    die("Ошибка: Недостаточно товара '{$stock[$productId]['name']}' на складе. Доступно: {$stock[$productId]['quantity']} шт.");
                }
            }
        }
    }

    /**
     * ЕСЛИ ВСЕ ПРОВЕРКИ ПРОЙДЕНЫ
     * Здесь будет продолжаться обработка заказа
     * (оригинальный код обработки заказа)
     */
}

/**
 * ПОЛУЧЕНИЕ СПИСКА ТОВАРОВ ДЛЯ ОТОБРАЖЕНИЯ В ФОРМЕ
 * Запрашиваем все товары из базы, отсортированные по названию
 */
$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <!-- Мета-информация и заголовок страницы -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автозапчасти Боба Марли</title>

    <!-- Подключение внешних CSS и JavaScript файлов -->
    <link rel="stylesheet" href="orderform.css">
    <script src="/jquery-3.6.0.min.js"></script>
    <script src="/jquery.inputmask.min.js"></script>

    <style>
        /* СТИЛИ ДЛЯ КНОПОК */
        .blue, .red, .grey {
            padding: 6px 12px;
            font-size: 12px;
            border: none;
            cursor: pointer;
            color: white;
            border-radius: 4px;
            margin: 3px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 0 rgba(0,0,0,0.1);
            font-weight: bold;
            text-transform: uppercase;
        }
        /* Цвета и эффекты для разных типов кнопок */
        .blue { background-color: #4a6fa5; }
        .blue:hover { background-color: #3a5a8f; transform: translateY(-1px); }
        .red { background-color: #d9534f; }
        .red:hover { background-color: #c9302c; transform: translateY(-1px); }
        .grey { background-color: #5a6268; }
        .grey:hover { background-color: #4a5258; transform: translateY(-1px); }
        /* Стиль нажатой кнопки */
        .button-pressed {
            transform: translateY(3px) !important;
            box-shadow: none !important;
        }
        /* Стиль блока скидки */
        .discount-section {
            background-color: #fffacd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>

    <script>
        /**
         * ФУНКЦИЯ ПРОВЕРКИ КОЛИЧЕСТВА ПРИ ИЗМЕНЕНИИ ЗНАЧЕНИЯ
         * Не позволяет ввести значение больше, чем есть на складе
         */
        function validateQuantity(input, max) {
            if (parseInt(input.value) > max) {
                input.value = max; // Устанавливаем максимально допустимое значение
                alert('Нельзя заказать больше чем есть на складе! Максимум: ' + max);
            }
        }

        /**
         * ФУНКЦИЯ ПРОВЕРКИ СКИДОЧНОГО КОДА
         */
        function applyDiscount() {
            const discountCode = document.getElementById('discountCode').value;
            if (discountCode === 'BOB10') {
                alert('Скидка 10% применена!');
                return true;
            } else if (discountCode) {
                alert('Неверный код скидки');
                return false;
            }
            return true;
        }

        /**
         * ОБРАБОТЧИК ЗАГРУЗКИ СТРАНИЦЫ
         */
        document.addEventListener('DOMContentLoaded', function() {
            // Вешаем обработчик на отправку формы
            document.querySelector('form').addEventListener('submit', function(e) {
                // Проверяем скидочный код
                if (!applyDiscount()) {
                    e.preventDefault();
                    return;
                }

                // Проверяем, что выбран хотя бы один товар
                const quantityInputs = document.querySelectorAll('input[type="number"]');
                let hasItems = false;

                quantityInputs.forEach(input => {
                    if (parseInt(input.value) > 0) {
                        hasItems = true;
                    }
                });

                if (!hasItems) {
                    e.preventDefault();
                    alert('Пожалуйста, выберите хотя бы один товар!');
                }
            });
        });
    </script>
</head>
<body>

<!-- ЗАГОЛОВОК СТРАНИЦЫ -->
<div>
    <h1 style="font-family: cursive; font-size: 30px; color: black; text-align: center; font-style: italic;">
        Форма заказа
    </h1>
</div>

<!-- ФОРМА ЗАКАЗА -->
<form action="orderform.php" method="post">
    <table class="order-table">
        <!-- ШАПКА ТАБЛИЦЫ -->
        <tr bgcolor="#d3d3d3">
            <td class="center">Товар</td>
            <td class="center">Количество</td>
            <td class="center">Цена</td>
        </tr>

        <!-- СПИСОК ТОВАРОВ -->
        <?php
        // Массив цветов для чередования строк
        $rowColors = ['aqua-bg', 'gold-bg', 'lightgreen-bg'];
        $colorIndex = 0; // Индекс текущего цвета
        $tabIndex = 4;   // Начальный индекс табуляции

        // Выводим каждый товар в отдельной строке таблицы
        while($product = $products->fetch_assoc()):
            $fieldName = 'product_' . $product['orderId']; // Формируем имя поля
            ?>
            <tr class="<?= $rowColors[$colorIndex % count($rowColors)] ?>">
                <!-- Название товара -->
                <td><?= htmlspecialchars($product['productName']) ?></td>

                <!-- Поле для ввода количества -->
                <td>
                    <input style="font-family: cursive; font-size: 13px; color: firebrick;"
                           class="fill"
                           type="number"
                           name="<?= $fieldName ?>"
                           placeholder="На складе: <?= $product['quantity'] ?> шт."
                           min="0"
                           max="<?= $product['quantity'] ?>"
                           onchange="validateQuantity(this, <?= $product['quantity'] ?>)"
                           tabindex="<?= $tabIndex ?>">
                </td>

                <!-- Цена товара -->
                <td>
                    <input class="fill"
                           value="$ <?= number_format($product['price'], 2) ?>"
                           readonly>
                    <!-- Скрытое поле с ID товара -->
                    <input type="hidden" name="product_id_<?= $product['orderId'] ?>"
                           value="<?= $product['orderId'] ?>">
                </td>
            </tr>
            <?php
            $colorIndex++;
            $tabIndex++;
        endwhile;
        ?>

        <!-- ПОЛЯ ДЛЯ ВВОДА ДАННЫХ О ДОСТАВКЕ -->
        <tr class="gold-bg">
            <td>Адрес доставки</td>
            <td>
                <input class="fill" type="text" name="address" required autofocus tabindex="1">
            </td>
        </tr>
        <tr class="lightgreen-bg">
            <td>Дата доставки</td>
            <td>
                <input class="fill" type="date" name="deliveryDate" tabindex="7">
            </td>
        </tr>
        <tr class="gold-bg">
            <td>Время доставки</td>
            <td>
                <input class="fill" type="time" name="deliveryTime" tabindex="8">
            </td>
        </tr>

        <!-- КОНТАКТНАЯ ИНФОРМАЦИЯ -->
        <tr class="lightgreen-bg">
            <td>Ваш номер телефона</td>
            <td>
                <input class="fill" type="text" name="tel" id="phoneInput"
                       placeholder="+7-(XXX)-XXX-XX-XX" required tabindex="2">
            </td>
        </tr>
        <tr class="lightgreen-bg">
            <td>Ваша электронная почта</td>
            <td>
                <input class="fill" type="email" name="email" required tabindex="3">
            </td>
        </tr>

        <!-- ДОПОЛНИТЕЛЬНАЯ ИНФОРМАЦИЯ -->
        <tr>
            <td>Как вы нас нашли?</td>
            <td>
                <select class="fill" name="find" tabindex="9">
                    <option value="a">Вы постоянный клиент</option>
                    <option value="b">ТВ реклама</option>
                    <option value="c">Телефонный справочник</option>
                    <option value="d">Сарафанное радио</option>
                </select>
            </td>
        </tr>

        <!-- КНОПКИ УПРАВЛЕНИЯ -->
        <tr>
            <td colspan="2">
                <button class="blue" type="submit">Отправить заказ</button>
                <button class="red" type="reset">Сбросить</button>
            </td>
        </tr>
    </table>
</form>

<!-- КНОПКА ПЕРЕХОДА НА СТРАНИЦУ СТОИМОСТИ ДОСТАВКИ -->
<div style="text-align: center;">
    <button class="grey" onclick="window.location.href='order_delivery.php'">
        Стоимость доставки
    </button>
</div>

<script>
    /**
     * ИНИЦИАЛИЗАЦИЯ ПРИ ЗАГРУЗКЕ СТРАНИЦЫ
     */
    $(document).ready(function(){
        // Маска для ввода телефона
        $('#phoneInput').inputmask('+7-(999)-999-99-99');

        // Эффекты нажатия для кнопок
        const buttons = document.querySelectorAll('.blue, .red, .grey');
        buttons.forEach(button => {
            button.addEventListener('mousedown', () => button.classList.add('button-pressed'));
            button.addEventListener('mouseup', () => button.classList.remove('button-pressed'));
            button.addEventListener('mouseleave', () => button.classList.remove('button-pressed'));
            button.addEventListener('touchstart', () => button.classList.add('button-pressed'));
            button.addEventListener('touchend', () => button.classList.remove('button-pressed'));
        });
    });
</script>

<!-- ПОДКЛЮЧЕНИЕ ФАЙЛА С ВЫВОДОМ ВРЕМЕНИ -->
<?php include("time.php"); ?>
</body>
</html>