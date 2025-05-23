
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Стоимость доставка</title>
    <link rel="stylesheet" href="orderform.css">


</head>
<body>
<h1 style="text-align: center;">Доставка</h1>
<table class="order-table">
    <thead> <!-- Раздел заголовков таблицы -->
    <tr bgcolor="#d3d3d3">
        <td>Расстояние в КМ</td>
        <td>Стоимость в $</td>
    </tr>
    </thead>
    <tbody>

    <?php

    for ($distance = 50; $distance <= 250; $distance = $distance + 50)
    {
        echo "<tr>\n<td>{$distance}</td>\n";
        echo "<td>" . ($distance / 10) . "</td>\n</tr>\n";
    }

    ?>

    </tbody>
</table>
<br>
<div style="text-align: center;">
    <button class="grey" onclick="window.location.href='orderforms.php'">
         К форме заказа
    </button>
</div>
</body>


<br>
<footer>

    <?php
    require_once("functions.php");
    echo get_currency_rates_2();
    include("time.php"); // Подключение внешнего файла с временем
    ?>

</footer>

</html>
