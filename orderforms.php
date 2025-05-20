
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
</head>
<body>
<div>
    <h1 style="font-family: Georgia;
        font-size: 35px; color: black;
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
        <tr class="aqua-bg">
            <td>Шины</td>
            <td>
                <input class="fill" type="number" name="tireqty" placeholder="Введите от 1 до 999"
                       tabindex="4"
                >
            </td>
            <td><input class="fill" value="$ 100" name="TIREPRICE" readonly></td>
        </tr>
        <tr class="gold-bg">
            <td>Масло</td>
            <td>
                <input class="fill" type="number" name="oilqty" placeholder="Введите от 1 до 999"
                       tabindex="5"
                >
            <td><input class="fill" value="$ 10" name="OILPRICE" readonly></td>
            </td>
        </tr>
        <tr class="lightgreen-bg">
            <td>Свечи зажигания</td>
            <td>
                <input class="fill" type="number" name="sparkqty" placeholder="Введите от 1 до 999"
                       tabindex="6"
                >
            <td><input class="fill" value="$ 4" name="SPARKPRICE" readonly></td>
            </td>
        </tr>
        <tr class="gold-bg">
            <td>Адрес доставки</td>
            <td>
                <input class="fill" type="text" name="address" autocomplete="on" required autofocus tabindex="1">
            </td>
        </tr>
        <tr class="lightgreen-bg">
            <td>Дата доставки</td>
            <td>
                <input class="fill" type="date" name="delivery_date" tabindex="7">
            </td>
        </tr>
        <tr class="gold-bg">
            <td>Время доставки</td>
            <td>
                <input class="fill" type="time" name="delivery_time" tabindex="8">
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
