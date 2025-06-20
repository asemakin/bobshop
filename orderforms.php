
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="Автозапчасти">
    <meta name="description" content="Шины, Масла, Свечи зажигания">
    <title>Автозапчасти Боба Марли</title>
    <link rel="stylesheet" href="orderform.css">

    <!-- В head -->
    <script src="/jquery-3.6.0.min.js"></script>
    <script src="/jquery.inputmask.min.js"></script>
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

        /* Класс для активного состояния */
        .button-pressed {
            transform: translateY(3px) !important;
            box-shadow: none !important;
        }
    </style>

    <script>
        function validateQuantity(input, max) {
            if (parseInt(input.value) > max) {
                input.value = max;
                alert('Нельзя заказать больше чем есть на складе! Максимум: ' + max);
            }
        }
    </script>

</head>
<body>

<?php
// Подключение к базе данных MySQL
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');

// Проверка соединения с БД
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

// Получаем все товары из базы данных
$products = $db->query("SELECT *, orderId AS productID FROM warehouse ORDER BY productName");
?>

<div>

    <h1 style="font-family: cursive;
        font-size: 30px; color: black;
        text-align: center;
        font-style: italic;">Форма заказа</h1>

</div>

<form action="orderform.php" method="post">

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
        $tabIndex = 4; // Начинаем с 4, так как первые 3 - адрес, телефон, email

        while($product = $products->fetch_assoc()):
            // Используем ID товара вместо названия для формирования имени поля
            $fieldName = 'productName' . ($product['orderId'] ?? '');  // Новый формат
            ?>

            <tr class="<?= $rowColors[$colorIndex % count($rowColors)] ?>">
                <td><?= htmlspecialchars($product['productName']) ?></td>
                <td>
                    <input style="font-family: cursive; font-size: 13px; color: firebrick;" class="fill" type="number"
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
                           name="price_<?= ($product['orderId'] ?? '') ?>"
                           readonly>
                </td>
            </tr>
            <?php
            $colorIndex++;
            $tabIndex++;
        endwhile;
        ?>

        <tr class="gold-bg">
            <td>Адрес доставки</td>
            <td>
                <input class="fill" type="text" name="address" autocomplete="on" required autofocus tabindex="1">
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
        <tr class="lightgreen-bg">
            <td>Ваш номер телефона</td>
            <td>
                <input class="fill" type="text" name="tel" id="phoneInput"
                       placeholder="+7-(XXX)-XXX-XX-XX" autocomplete="on" required tabindex="2">
            </td>
        </tr>
        </tr>
        <tr class="lightgreen-bg">
            <td>Ваша электронная почта</td>
            <td>
                <input class="fill" type="email" name="email" autocomplete="on" required tabindex="3">
            </td>
        </tr>

        <tr>
            <td>Как ты нашёл Боба Марли ?</td>
            <td>
                <div class="custom-select">
                <select class="fill" name="find" tabindex="9">
                    <option value="a">Вы постоянный клиент</option>
                    <option value="b">ТВ реклама</option>
                    <option value="c">Телефонный справочник</option>
                    <option value="d">Сарафанное радио</option>
                </select>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <button class="blue" type="submit">Отправить заказ</button>
                <button class="red" type="reset">Сбросить</button>
            </td>
        </tr>
    </table>

</form>

<div style="text-align: center;">
    <button class="grey" onclick="window.location.href='order_delivery.php'">
        Стоимость доставки
    </button>
</div>

<!-- Инициализация в конце body -->
<script>
    $(document).ready(function(){
        $('#phoneInput').inputmask('+7-(999)-999-99-99', {
            'clearIncomplete': true,
            'showMaskOnHover': true
        });

        // Для отладки
        console.log('Inputmask initialized');
    });
</script>

<footer>

    <?php
    include("time.php"); // Подключение внешнего файла с временем
    require_once("functions.php");
    echo get_currency_rates_2();
    ?>

</footer>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Получаем все кнопки с нужными классами
        const buttons = document.querySelectorAll('.blue, .red, .grey');

        // Обработчики для мыши (ПК)
        buttons.forEach(button => {
            button.addEventListener('mousedown', () => {
                button.classList.add('button-pressed');
            });

            button.addEventListener('mouseup', () => {
                button.classList.remove('button-pressed');
            });

            button.addEventListener('mouseleave', () => {
                button.classList.remove('button-pressed');
            });
        });

        // Обработчики для тач-устройств (мобильные)
        buttons.forEach(button => {
            button.addEventListener('touchstart', () => {
                button.classList.add('button-pressed');
            });

            button.addEventListener('touchend', () => {
                button.classList.remove('button-pressed');
            });
        });
    });
</script>
</body>
</html>
