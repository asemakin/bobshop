<?php
/**
 * Подключение к базе данных MySQL
 * Параметры: (хост, пользователь, пароль, база данных)
 */
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    // Если не удалось подключиться — выводим ошибку и прерываем работу
    die("Ошибка подключения: " . $db->connect_error);
}

/**
 * Обработка отправки формы заказа
 */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Получаем список товаров на складе
    $products = $db->query("SELECT orderId, productName, quantity FROM warehouse");

    // Массив для хранения остатков товаров
    $stock = array();

    // Заполняем массив остатками и названиями товаров
    while($row = $products->fetch_assoc()) {
        $stock[$row['orderId']] = array(
            'quantity' => $row['quantity'],
            'name' => $row['productName']
        );
    }

    // Перебираем все данные, отправленные формой
    foreach ($_POST as $key => $value) {
        // Ищем поля, которые начинаются с "product_" и имеют количество > 0
        if (strpos($key, 'product_') === 0 && $value > 0) {
            $productId = substr($key, 8); // Получаем ID товара (после "product_")
            $orderedQuantity = (int)$value; // Приводим к целому

            // Проверяем, есть ли товар на складе
            if (isset($stock[$productId])) {
                // Если заказанное количество больше остатка — ошибка
                if ($orderedQuantity > $stock[$productId]['quantity']) {
                    die("Ошибка: Недостаточно товара '{$stock[$productId]['name']}' на складе. Доступно: {$stock[$productId]['quantity']} шт.");
                }
            }
        }
    }

    /**
     * Здесь можно добавить сохранение заказа:
     * 1. Записать данные в таблицу `orders`
     * 2. Уменьшить количество на складе в таблице `warehouse`
     */
}

// Запрашиваем все товары для вывода в форме
$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автозапчасти Боба Марли</title>

    <!-- Подключаем внешний CSS -->
    <link rel="stylesheet" href="orderform.css">

    <!-- Подключаем jQuery и маску ввода -->
    <script src="/jquery-3.6.0.min.js"></script>
    <script src="/jquery.inputmask.min.js"></script>

    <!-- Стили кнопок и некоторых элементов -->
    <style>
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
        .blue { background-color: #4a6fa5; }
        .blue:hover { background-color: #3a5a8f; transform: translateY(-1px); }
        .red { background-color: #d9534f; }
        .red:hover { background-color: #c9302c; transform: translateY(-1px); }
        .grey { background-color: #5a6268; }
        .grey:hover { background-color: #4a5258; transform: translateY(-1px); }
        .button-pressed {
            transform: translateY(3px) !important;
            box-shadow: none !important;
        }
        .discount-section {
            background-color: #fffacd;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
    </style>

    <script>
        // Функция проверки, чтобы нельзя было заказать больше, чем есть на складе
        function validateQuantity(input, max) {
            if (parseInt(input.value) > max) {
                input.value = max;
                alert('Нельзя заказать больше чем есть на складе! Максимум: ' + max);
            }
        }

        // Проверка скидочного кода
        function applyDiscount() {
            const discountCode = document.getElementById('discountCode')?.value;
            if (discountCode === 'BOB10') {
                alert('Скидка 10% применена!');
                return true;
            } else if (discountCode) {
                alert('Неверный код скидки');
                return false;
            }
            return true;
        }

        // Валидация перед отправкой формы
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('form').addEventListener('submit', function(e) {
                // Проверка кода скидки
                if (!applyDiscount()) {
                    e.preventDefault();
                    return;
                }

                // Проверка, выбран ли хотя бы один товар
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
<div>
    <h1 style="font-family: cursive; font-size: 30px; color: black; text-align: center; font-style: italic;">
        Форма заказа
    </h1>
</div>

<form action="orderconfirm.php" method="post">
    <table class="order-table">
        <tr bgcolor="#d3d3d3">
            <td class="center">Товар</td>
            <td class="center">Количество</td>
            <td class="center">Цена</td>
        </tr>
        <?php
        // Цвета для чередования строк
        $rowColors = ['aqua-bg', 'gold-bg', 'lightgreen-bg'];
        $colorIndex = 0;
        $tabIndex = 4;

        // Выводим список товаров
        while($product = $products->fetch_assoc()):
            $fieldName = 'product_' . $product['orderId'];
            ?>
            <tr class="<?= $rowColors[$colorIndex % count($rowColors)] ?>">
                <td><?= htmlspecialchars($product['productName']) ?></td>
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
                <td>
                    <input class="fill"
                           value="$ <?= number_format($product['price'], 2) ?>"
                           readonly>
                    <input type="hidden" name="product_id_<?= $product['orderId'] ?>"
                           value="<?= $product['orderId'] ?>">
                </td>
            </tr>
            <?php
            $colorIndex++;
            $tabIndex++;
        endwhile;
        ?>
        <!-- Блок ввода адреса -->
        <tr class="gold-bg">
            <td>Адрес доставки</td>
            <td>
                <input class="fill" type="text" name="address" required autofocus tabindex="1">
            </td>
        </tr>
        <!-- Дата доставки -->
        <tr class="lightgreen-bg">
            <td>Дата доставки</td>
            <td>
                <input class="fill" type="date" name="deliveryDate" tabindex="7">
            </td>
        </tr>
        <!-- Время доставки -->
        <tr class="gold-bg">
            <td>Время доставки</td>
            <td>
                <input class="fill" type="time" name="deliveryTime" tabindex="8">
            </td>
        </tr>
        <!-- Телефон -->
        <tr class="lightgreen-bg">
            <td>Ваш номер телефона</td>
            <td>
                <input class="fill" type="text" name="tel" id="phoneInput"
                       placeholder="+7-(XXX)-XXX-XX-XX" required tabindex="2">
            </td>
        </tr>
        <!-- Email -->
        <tr class="lightgreen-bg">
            <td>Ваша электронная почта</td>
            <td>
                <input class="fill" type="email" name="email" required tabindex="3">
            </td>
        </tr>
        <!-- Источник информации -->
        <tr>
            <td>Как вы нас нашли?</td>
            <td>
                <select multiple class="fill" name="find" tabindex="9">
                    <option value="a">Вы постоянный клиент</option>
                    <option value="b">ТВ реклама</option>
                    <option value="c">Телефонный справочник</option>
                    <option value="d">Сарафанное радио</option>
                </select>
            </td>
        </tr>
        <!-- Кнопки отправки -->
        <tr>
            <td colspan="2">
                <button class="blue" type="submit">Отправить заказ</button>
                <button class="red" type="reset">Сбросить</button>
            </td>
        </tr>
    </table>
</form>

<!-- Кнопка перехода на страницу стоимости доставки -->
<div style="text-align: center;">
    <button class="grey" onclick="window.location.href='order_delivery.php'">
        Стоимость доставки
    </button>
</div>

<script>
    $(document).ready(function(){
        // Маска ввода телефона
        $('#phoneInput').inputmask('+7-(999)-999-99-99');

        // Анимация нажатия кнопок
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

<?php include("time.php"); ?>
</body>
</html>
