<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$db = new mysqli('localhost', 'root', '', 'bob_auto_parts');
if ($db->connect_error) {
    die("Ошибка подключения: " . $db->connect_error);
}

$orderId = (int)($_GET['orderId'] ?? 0);
if ($orderId <= 0) {
    die("Неверный номер заказа");
}

// Получаем заказ
$order = $db->query("SELECT * FROM `order` WHERE id = $orderId")->fetch_assoc();
if (!$order) {
    die("Заказ не найден");
}

// Получаем товары заказа
$orderItems = [];
$res = $db->query("SELECT * FROM `orderItem` WHERE orderNumber = $orderId");
while ($row = $res->fetch_assoc()) {
    $row['sum'] = $row['quantity'] * $row['price'];
    $orderItems[] = $row;
}

$db->close();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Подтверждение заказа</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 900px;
            margin: 30px auto;
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        h1, h2, h3 {
            text-align: center;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
        }
        td {
            padding: 10px;
            text-align: center;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .summary {
            background: #f0f8ff;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .summary p {
            margin: 5px 0;
            font-size: 16px;
        }
        .btn {
            display: inline-block;
            background: #4CAF50;
            color: white;
            padding: 12px 20px;
            margin-top: 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
        }
        .btn:hover {
            background: #45a049;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Автозапчасти Боба Марли</h1>
    <h2>Спасибо за заказ!</h2>
    <p style="text-align:center;">
        <strong>Номер заказа:</strong> <?= $orderId ?><br>
        <strong>Дата заказа:</strong> <?= date("d.m.Y - H:i", strtotime($order['orderDate'])) ?>
    </p>

    <h3>Состав заказа</h3>
    <table>
        <tr>
            <th>Товар</th>
            <th>Цена (₽)</th>
            <th>Количество</th>
            <th>Сумма (₽)</th>
        </tr>
        <?php foreach ($orderItems as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['productName']) ?></td>
                <td><?= number_format($item['price'], 2, ',', ' ') ?></td>
                <td><?= $item['quantity'] ?></td>
                <td><?= number_format($item['sum'], 2, ',', ' ') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>

    <div class="summary">
        <p><strong>Общая стоимость:</strong> <?= number_format($order['subTotal'], 2, ',', ' ') ?> ₽</p>
        <p><strong>Скидка (10%):</strong> -<?= number_format($order['subTotal'] * $order['discount'], 2, ',', ' ') ?> ₽</p>
        <p><strong>Налог (10%):</strong> <?= number_format($order['tax'], 2, ',', ' ') ?> ₽</p>
        <p><strong>Итого к оплате:</strong> <?= number_format($order['totalAmount'], 2, ',', ' ') ?> ₽</p>
        <p><strong><?= htmlspecialchars($order['referralSourceId']) ?></strong></p>
    </div>

    <h3>Доставка</h3>
    <p><strong>Адрес:</strong> <?= htmlspecialchars($order['deliveryAddress']) ?></p>
    <p><strong>Дата:</strong> <?= htmlspecialchars($order['deliveryDate']) ?></p>
    <p><strong>Время:</strong> <?= htmlspecialchars($order['deliveryTime']) ?></p>
    <p><strong>Телефон:</strong> <?= htmlspecialchars($order['customerPhone']) ?></p>
    <p><strong>Email:</strong> <?= htmlspecialchars($order['customerEmail']) ?></p>

    <div style="text-align: center;">
        <a href="orderforms.php" class="btn">Сделать новый заказ</a>
    </div>
</div>
</body>
</html>
