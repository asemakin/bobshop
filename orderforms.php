<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Автозапчасти Боба Марли</title>
    <link rel="stylesheet" href="orderform.css">
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
        function validateQuantity(input, max) {
            if (parseInt(input.value) > max) {
                input.value = max;
                alert('Нельзя заказать больше чем есть на складе! Максимум: ' + max);
            }
        }

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

        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('form').addEventListener('submit', function(e) {
                if (!applyDiscount()) {
                    e.preventDefault();
                    return;
                }

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

<?php
$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

$products = $db->query("SELECT * FROM warehouse ORDER BY productName");
?>

<div>
    <h1 style="font-family: cursive; font-size: 30px; color: black; text-align: center; font-style: italic;">
        Форма заказа
    </h1>
</div>

<form action="orderform.php" method="post">
    <table class="order-table">
        <tr bgcolor="#d3d3d3">
            <td class="center">Товар</td>
            <td class="center">Количество</td>
            <td class="center">Цена</td>
        </tr>

        <?php
        $rowColors = ['aqua-bg', 'gold-bg', 'lightgreen-bg'];
        $colorIndex = 0;
        $tabIndex = 4;

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

<script>
    $(document).ready(function(){
        $('#phoneInput').inputmask('+7-(999)-999-99-99');

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